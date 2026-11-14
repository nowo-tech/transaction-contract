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
| Transient failure | Deadlock, lock wait timeout, serialization failure, lost connection: when the runner does not, or may no longer, retry, it throws `TransientStorageFailure` with the storage error as `previous`. |
| Other failures | Rethrown unchanged. |
| Clean exit | When `run()` returns or throws, the runner has left no transaction of its own open. |
| Worker mode | Implementations hold no per-call state: one instance serves every request of a long-running worker (FrankenPHP worker mode, RoadRunner, Messenger consumers). |

`TransientStorageFailure`'s message is generic on purpose. The storage error, which can contain SQL and parameters,
stays in `getPrevious()`, for the logs only.

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
