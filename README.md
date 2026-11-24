# Transaction Contract

[![CI](https://github.com/nowo-tech/transaction-contract/actions/workflows/ci.yml/badge.svg)](https://github.com/nowo-tech/transaction-contract/actions/workflows/ci.yml) [![Packagist Version](https://img.shields.io/packagist/v/nowo-tech/transaction-contract.svg?style=flat)](https://packagist.org/packages/nowo-tech/transaction-contract) [![Packagist Downloads](https://img.shields.io/packagist/dt/nowo-tech/transaction-contract.svg)](https://packagist.org/packages/nowo-tech/transaction-contract) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE) [![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)](https://php.net) [![GitHub stars](https://img.shields.io/github/stars/nowo-tech/transaction-contract.svg?style=social&label=Star)](https://github.com/nowo-tech/transaction-contract) [![Coverage](https://img.shields.io/badge/Coverage-100%25-brightgreen)](#tests-and-coverage)

> ⭐ **Found this useful?** [Install from Packagist](https://packagist.org/packages/nowo-tech/transaction-contract) · Give it a **star** on [GitHub](https://github.com/nowo-tech/transaction-contract) so more developers can find it.

A framework-free **transaction port** for application and domain code: `TransactionRunnerInterface::run(callable)`
and the `TransientStorageFailure` exception, which carries a Retry-After hint, plus the conflict types a bundle's
domain or contract layer names (REQ-RUNTIME-003 rule 6): `ConflictCode`, `TransientFailureKind`,
`ConcurrencyConflict` and `LockConflictExceptionInterface` (1.1). **No dependencies beyond PHP**, no Doctrine, no
Symfony.

A package (a bundle's application layer, a DDD module) requires **this** package and type-hints the port. The host
binds an adapter, for example the one shipped by
[`nowo-tech/doctrine-deadlock-retry-bundle`](https://github.com/nowo-tech/DoctrineDeadlockRetryBundle) 3.x, which
retries deadlocks, lock wait timeouts and serialization failures with backoff and repairs the connection after a
failure.

![FrankenPHP friendly](docs/images/frankenphp-friendly.png)

This package is **FrankenPHP worker mode friendly**.

## Installation

```bash
composer require nowo-tech/transaction-contract
```

## Using the port

```php
use Nowo\TransactionContract\TransactionRunnerInterface;
use Nowo\TransactionContract\TransientStorageFailure;

final readonly class PayOrderHandler
{
    public function __construct(
        private TransactionRunnerInterface $transactions,
        private OrderRepository $orders,
        private EventBus $events,
    ) {
    }

    public function __invoke(PayOrder $command): void
    {
        // Replayable: everything the unit of work changes is loaded inside the callable.
        $paid = $this->transactions->run(function () use ($command): OrderPaid {
            $order = $this->orders->get($command->orderId);

            return $order->pay($command->amount);
        });

        // Side effects only after run() has returned, so a retried attempt never sends twice.
        $this->events->publish($paid);
    }
}
```

`TransientStorageFailure` reaching an HTTP edge means "503, retry later": `retryAfterSeconds()` is the hint for the
`Retry-After` header. A message consumer can delay the redelivery by the same amount.

## Conflict types (1.1)

A bundle's public conflict exception (REQ-RUNTIME-003 rule 6) can live in its `Domain` / `Contract` layer and still
pass REQ-ARCH-002 (Deptrac), because these types import nothing but PHP:

```php
use Nowo\TransactionContract\ConcurrencyConflict;
use Nowo\TransactionContract\ConflictCode;
use Nowo\TransactionContract\LockConflictExceptionInterface;
use Nowo\TransactionContract\TransientStorageFailure;

final class MyBundleConflictException extends TransientStorageFailure implements LockConflictExceptionInterface
{
    public function __construct(public readonly ConflictCode $conflictCode, ConcurrencyConflict $conflict)
    {
        parent::__construct($conflictCode->retrySafe() ? $conflict->retryAfterSeconds() : null, 'Retry later.', $conflict);
    }
}
```

| Type | Role |
|------|------|
| `ConflictCode` | Enum `RetrySafe` = `CONFLICT_001` (nothing committed, retry after `Retry-After`) / `Committed` = `CONFLICT_002` (read again, do not repeat); constants `RETRY_SAFE` / `COMMITTED`; `retrySafe()` |
| `TransientFailureKind` | Enum `Deadlock`, `LockWaitTimeout`, `SerializationFailure`, `Retryable` (stable string values) |
| `LockConflictExceptionInterface` | Marker: "a lock conflict the client may simply retry"; a conflict responder answers 409 + `Retry-After` |
| `ConcurrencyConflict` | Final `TransientStorageFailure` + `LockConflictExceptionInterface` with `attempts`, `kind`, `committed`, `resetFailure` and a constant client-safe `MESSAGE` |

They are the value types only. Working out the code of a failure (commit watermarks, Messenger wrappers, a lost
savepoint) and the HTTP answer stay in the adapter: with
[`nowo-tech/transaction-doctrine`](https://github.com/nowo-tech/transaction-doctrine) >= 1.10, use
`ConflictCodeResolver::forFailure()` / `forRequest()` and `Symfony\Conflict\ConflictResponsePolicy`. Its old names
`Nowo\TransactionDoctrine\TransientFailureKind`, `…\WriteUnit\ConcurrencyConflict` and
`…\WriteUnit\LockConflictExceptionInterface` are aliases of these types (same class), and its deprecated
`Nowo\TransactionDoctrine\ConflictCode` converts with `toContract()` / `fromContract()`.

**Migrating a bundle:** replace `use Nowo\TransactionDoctrine\{ConflictCode, TransientFailureKind,
WriteUnit\ConcurrencyConflict, WriteUnit\LockConflictExceptionInterface}` by `use Nowo\TransactionContract\…` (same
short names), and `ConflictCode::forFailure()` / `forRequest()` by `ConflictCodeResolver::forFailure()` /
`forRequest()`. See [UPGRADING](docs/UPGRADING.md#to-110).

## The contract

| Element | Rule |
|---------|------|
| `run(callable $operation): mixed` | Runs the operation in one transaction the runner opens and commits (an ORM-backed runner flushes first) and returns what the operation returned. |
| Replays | The runner **may run the operation more than once** after a transient failure. Load what you change inside the callable; keep mails, messages and events until `run()` has returned. |
| Transient failure | Deadlock, lock wait timeout, serialization failure: when the runner does not, or may no longer, retry, it throws `TransientStorageFailure` with the storage error as `previous`. A failure whose outcome is unknown (connection lost during the commit) is **not** transient: replaying it could apply the operation twice. |
| Other failures | Rethrown unchanged. |
| Clean exit | When `run()` returns or throws, the runner has left no transaction of its own open. |
| Nothing pending after a failure | When `run()` throws, no change made by the operation is pending in the backing unit of work: an ORM runner clears the manager, or closes and resets it. A later flush, `run()` or request never commits part of a failed unit of work. Do not keep using entities loaded before a failed `run()` as managed objects. |
| Nesting | Inside a transaction the caller (or an outer runner) opened, `run()` does not retry: the storage may have ended the whole transaction. Only the outermost `run()` replays. The failure propagates, and the caller's transaction may already be gone: never `catch { rollBack(); }` blindly; check that a transaction is active, or let the failure reach the outermost runner. A nested run shares the caller's unit of work, so its failure may discard the caller's pending changes too (an ORM runner clears the shared manager): after catching a failure from a nested `run()`, do not continue the same unit of work; let it propagate, or re-load and redo the work. |
| Worker mode | Implementations hold no per-call state: one instance serves every request of a long-running worker (FrankenPHP worker mode, RoadRunner, Messenger consumers). |

`TransientStorageFailure`'s message is generic on purpose. The storage error, which can contain SQL and parameters,
stays in `getPrevious()`, for the logs only. Never render the exception's string form (`(string) $e`, the trace) to a
client: it includes the previous chain.

## Backward-compatibility promise (1.x)

- The `TransactionRunnerInterface::run()` signature and the contract above are frozen for 1.x; only clarifications
  that no conforming implementation violates are added.
- `TransientStorageFailure`'s constructor parameter order (`retryAfterSeconds`, `message`, `previous`),
  `fromPrevious()`, `retryAfterSeconds()` and `DEFAULT_MESSAGE` are frozen for 1.x. The class is not final:
  subclasses are allowed and keep being caught as `TransientStorageFailure`.
- From 1.1: the cases and values of `ConflictCode` and `TransientFailureKind`, the `ConflictCode` constants,
  `LockConflictExceptionInterface`, and `ConcurrencyConflict`'s constructor parameter order (`attempts`, `kind`,
  `retryAfterSeconds`, `previous`, `committed`, `resetFailure`), public properties and `MESSAGE` are frozen for 1.x.

## What is here

| Class | Role |
|-------|------|
| `TransactionRunnerInterface` | The port: `run(callable(): T): T` |
| `TransientStorageFailure` | `RuntimeException` with `retryAfterSeconds(): ?int` and `fromPrevious(Throwable, ?int)` |
| `ConflictCode` | Conflict code enum (`CONFLICT_001` / `CONFLICT_002`), 1.1 |
| `TransientFailureKind` | Kind of transient storage failure, 1.1 |
| `LockConflictExceptionInterface` | Marker for retryable lock conflicts, 1.1 |
| `ConcurrencyConflict` | A write unit's last lock conflict (`TransientStorageFailure`), 1.1 |

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Configuration](docs/CONFIGURATION.md)
- [Usage](docs/USAGE.md)
- [Contributing](docs/CONTRIBUTING.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)
- [Changelog](docs/CHANGELOG.md)
- [Upgrading](docs/UPGRADING.md)
- [Release](docs/RELEASE.md)
- [Security](docs/SECURITY.md)
- [Engram](docs/ENGRAM.md)
- [Spec-driven development](docs/SPEC-DRIVEN-DEVELOPMENT.md)
- [GitHub Spec Kit](docs/SPEC-KIT.md)

### Additional documentation

- [PSR evaluation (REQ-CS-007)](docs/PSR.md)
- [Branching](docs/BRANCHING.md)

## Tests and coverage

- Tests: PHPUnit (`tests/Unit`), CI on PHP 8.2–8.5
- PHP: 100% (6/6 lines and 3/3 methods in CI; gate `make test-coverage-100`)
- TS/JS: N/A
- Python: N/A

## License

MIT. See [LICENSE](LICENSE).
