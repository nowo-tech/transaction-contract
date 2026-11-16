# Usage

## Table of contents

- [Type-hint the port](#type-hint-the-port)
- [Rules for the operation](#rules-for-the-operation)
- [Transient failures at the edges](#transient-failures-at-the-edges)
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

## Implementing a runner

An adapter implements `run(callable $operation): mixed`. It must commit only once, roll back on any exception, and
throw `TransientStorageFailure` (or a subclass) only for failures worth retrying later. The full contract is the
docblock of the interface and the table in the README.

## Worker mode

Neither element holds state: the interface has no implementation here and the exception is immutable. The package is
safe under FrankenPHP worker mode with the kernel kept alive (`FRANKENPHP_RESET_KERNEL=false`); PHPStan runs the
`nowo-tech/phpstan-frankenphp` classic and worker rulesets and `composer igor` audits it.
