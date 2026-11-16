# Installation

```bash
composer require nowo-tech/transaction-contract
```

Requirements: PHP 8.2 – 8.5. No other dependency: no Symfony, no Doctrine, no PSR package.

The package ships only the port (`TransactionRunnerInterface`) and the exception (`TransientStorageFailure`). To run
units of work you also need an adapter, for example:

- [`nowo-tech/transaction-doctrine`](https://github.com/nowo-tech/transaction-doctrine): plain Doctrine DBAL runner,
  repairs the connection after a deadlock or lock wait, no retries, no Symfony;
- [`nowo-tech/doctrine-deadlock-retry-bundle`](https://github.com/nowo-tech/DoctrineDeadlockRetryBundle) 3.x: Symfony
  adapter that retries deadlocks, lock wait timeouts and serialization failures with backoff.

Bind the interface to the adapter's runner in your container; see the adapter's documentation.
