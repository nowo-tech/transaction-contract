# Usage

## Table of contents

- [Type-hint the port](#type-hint-the-port)
- [Rules for the operation](#rules-for-the-operation)
- [Transient failures at the edges](#transient-failures-at-the-edges)
- [Conflict types](#conflict-types)
- [Implementing a runner](#implementing-a-runner)
- [Worker mode](#worker-mode)

## Type-hint the port

Application and domain code depends on `Nowo\TransactionContract\TransactionRunnerInterface` only:

```php
$result = $this->transactions->run(function () use ($command): OrderPaid {
    $order = $this->orders->get($command->orderId);

    return $order->pay($command->amount);
});
```

`run()` opens one transaction, runs the operation, commits (an ORM-backed runner flushes first) and returns what the
operation returned.

## Rules for the operation

- **Replayable.** A runner may run the operation more than once after a transient failure. Load everything you change
  inside the callable.
- **No side effects inside.** Send mails, messages and events after `run()` has returned, so a retried attempt never
  sends twice.
- **No nested runners of your own.** Call `run()` once per unit of work.

## Transient failures at the edges

When the runner gives up, it throws `TransientStorageFailure`:

```php
try {
    $handler($command);
} catch (TransientStorageFailure $e) {
    $retryAfter = $e->retryAfterSeconds(); // null or seconds
    // HTTP: 503 + Retry-After; Messenger: redeliver after the delay
}
```

`TransientStorageFailure::fromPrevious($exception, $seconds)` wraps the original storage error, which stays available
through `getPrevious()`.

## Conflict types

From 1.1, the conflict vocabulary of REQ-RUNTIME-003 rule 6 is here too, framework-free, so a bundle's domain or
contract layer can name it:

```php
use Nowo\TransactionContract\ConcurrencyConflict;
use Nowo\TransactionContract\ConflictCode;
use Nowo\TransactionContract\LockConflictExceptionInterface;
use Nowo\TransactionContract\TransientFailureKind;

try {
    $this->subscriptions->save($subscription);
} catch (ConcurrencyConflict $conflict) {
    // $conflict->attempts, ->kind (TransientFailureKind), ->committed, ->resetFailure, ->retryAfterSeconds()
    $code = $conflict->committed ? ConflictCode::Committed : ConflictCode::RetrySafe;

    throw new MyBundleConflictException($code, $conflict); // implements LockConflictExceptionInterface
}
```

- `ConflictCode::RetrySafe` (`CONFLICT_001`): nothing was committed; the answer may carry `Retry-After`.
- `ConflictCode::Committed` (`CONFLICT_002`): part of the work may be committed; read again, never retry blindly.
- `LockConflictExceptionInterface` marks any exception as a lock conflict a conflict responder answers with 409.

Deriving the code of an arbitrary failure (request watermark, Messenger wrappers, lost savepoints) and building the
HTTP answer is adapter work: transaction-doctrine >= 1.10 has `ConflictCodeResolver` and `ConflictResponsePolicy`,
and its old type names are aliases of these (migrate with `use Nowo\TransactionContract\…`; see
[UPGRADING](UPGRADING.md#to-110)).

## Implementing a runner

An adapter implements `run(callable $operation): mixed`. It must commit only once, roll back on any exception, and
throw `TransientStorageFailure` (or a subclass) only for failures worth retrying later. The full contract is the
docblock of the interface and the table in the README.

## Worker mode

No element holds state: the interface has no implementation here, the exceptions are immutable and the enums are
values. The package is
safe under FrankenPHP worker mode with the kernel kept alive (`FRANKENPHP_RESET_KERNEL=false`); PHPStan runs the
`nowo-tech/phpstan-frankenphp` classic and worker rulesets and `composer igor` audits it.
