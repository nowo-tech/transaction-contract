# Transaction Contract

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.2-777bb3.svg)](https://www.php.net/)

A framework-free **transaction port** for application and domain code: `TransactionRunnerInterface::run(callable)`
and the `TransientStorageFailure` exception, which carries a Retry-After hint. Interfaces and one exception only:
**no dependencies beyond PHP**, no Doctrine, no Symfony.

A package (a bundle's application layer, a DDD module) requires **this** package and type-hints the port. The host
binds an adapter, for example the one shipped by
[`nowo-tech/doctrine-deadlock-retry-bundle`](https://github.com/nowo-tech/DoctrineDeadlockRetryBundle) 3.x, which
retries deadlocks, lock wait timeouts and serialization failures with backoff and repairs the connection after a
failure.

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

## What is here

| Class | Role |
|-------|------|
| `TransactionRunnerInterface` | The port: `run(callable(): T): T` |
| `TransientStorageFailure` | `RuntimeException` with `retryAfterSeconds(): ?int` and `fromPrevious(Throwable, ?int)` |

## Documentation

- [Changelog](docs/CHANGELOG.md)
- [Contributing](docs/CONTRIBUTING.md)
- [Security](docs/SECURITY.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)

## License

MIT. See [LICENSE](LICENSE).
