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
 * - When run() throws, no change made by the operation is pending in the backing unit of work: an ORM-backed runner
 *   clears the object manager, or closes it and resets it. A later flush, run() or request never commits part of a
 *   failed unit of work. A caller must not keep using entities it loaded before a failed run() as managed objects.
 *
 * Nesting: run() called inside a transaction the caller (or an outer runner) opened is not retried, because the
 * storage may have rolled back or aborted the whole transaction. Only the outermost run() may replay the operation.
 * The inner failure propagates (a transient one as TransientStorageFailure) and the caller's transaction may already
 * have been ended by the storage: after such a failure the caller must not assume its transaction is still open
 * (guard a rollback with "is a transaction active", or let the failure reach the outermost runner). A nested run
 * shares the caller's unit of work, so keeping "nothing pending" after its failure may discard the caller's own
 * pending changes too (an ORM runner clears the shared manager): after catching a failure from a nested run(), the
 * caller must not continue the same unit of work; let the failure propagate, or re-load and redo the work.
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
     * @throws TransientStorageFailure When the storage failed transiently and the operation was not completed (nothing of it is pending)
     *
     * @return T What the operation returned
     */
    public function run(callable $operation): mixed;
}
