<?php

declare(strict_types=1);

namespace Nowo\TransactionContract;

/**
 * Port: runs a unit of work atomically, in one storage transaction.
 *
 * Application and domain code type-hint this interface; the host binds an adapter (for example the one of
 * nowo-tech/doctrine-deadlock-retry-bundle, which retries deadlocks and repairs the connection). The contract:
 *
 * - The operation runs inside a transaction that the runner opens and commits. When the runner is backed by an
 *   object manager (Doctrine ORM), pending changes are flushed before the commit.
 * - The runner MAY run the operation more than once after a transient failure, so it must be replayable: load what
 *   it changes inside the callable, and keep side effects (mails, messages, events) until run() has returned.
 * - When the storage fails transiently and the runner does not (or may no longer) retry, run() throws
 *   TransientStorageFailure. Any other failure of the operation is rethrown unchanged.
 * - When run() returns or throws, the runner has left no transaction of its own open.
 *
 * Implementations must hold no per-call state, so that one instance can serve every request of a long-running
 * worker.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface TransactionRunnerInterface
{
    /**
     * @template T
     *
     * @param callable(): T $operation The unit of work; replayable
     *
     * @throws TransientStorageFailure When the storage failed transiently and the operation was not completed
     *
     * @return T What the operation returned
     */
    public function run(callable $operation): mixed;
}
