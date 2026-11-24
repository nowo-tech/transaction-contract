<?php

declare(strict_types=1);

namespace Nowo\TransactionContract;

/**
 * Kind of transient storage failure: replaying the whole transaction later can succeed.
 *
 * The values are stable (configuration and logs use them). An adapter maps its storage's errors to a kind
 * (transaction-doctrine's TransientFailureClassifier for Doctrine DBAL).
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
enum TransientFailureKind: string
{
    /** MySQL / MariaDB 1213, PostgreSQL 40P01. */
    case Deadlock = 'deadlock';

    /** MySQL / MariaDB 1205, PostgreSQL 55P03 (lock_timeout, NOWAIT). */
    case LockWaitTimeout = 'lock_wait_timeout';

    /** SQLSTATE 40001 without a more precise code (PostgreSQL), MariaDB 1020 (snapshot isolation conflict). */
    case SerializationFailure = 'serialization_failure';

    /** Any other failure the storage layer reports as retryable (Doctrine DBAL: RetryableException). */
    case Retryable = 'retryable';
}
