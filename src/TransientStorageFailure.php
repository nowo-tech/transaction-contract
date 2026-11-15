<?php

declare(strict_types=1);

namespace Nowo\TransactionContract;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * The storage failed transiently (deadlock, lock wait timeout, serialization failure) and the operation was not
 * completed: the same request can succeed later. A failure whose outcome is unknown (a connection lost while the
 * commit was in flight) is not transient: replaying it could apply the operation twice.
 *
 * It carries an optional Retry-After hint in seconds, so an HTTP edge can answer 503 with Retry-After and a
 * message consumer can delay the redelivery. Its message is generic on purpose: the storage error, which can contain
 * SQL and parameters, stays in the previous exception, for the logs only. Never put storage details in the message,
 * and never render the exception's string form ((string) $failure, getTraceAsString()) to a client: it includes the
 * previous chain.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
class TransientStorageFailure extends RuntimeException
{
    public const DEFAULT_MESSAGE = 'The storage is temporarily unavailable. Retry later.';

    private readonly ?int $retryAfterSeconds;

    /**
     * @param int|null $retryAfterSeconds Seconds after which a retry is sensible; null = no hint
     * @param string $message Generic, client-safe text (no SQL, no parameters, no driver message)
     * @param Throwable|null $previous The storage error
     *
     * @throws InvalidArgumentException When $retryAfterSeconds is negative
     */
    public function __construct(?int $retryAfterSeconds = null, string $message = self::DEFAULT_MESSAGE, ?Throwable $previous = null)
    {
        if ($retryAfterSeconds !== null && $retryAfterSeconds < 0) {
            throw new InvalidArgumentException('retryAfterSeconds must be null or zero or more.');
        }

        parent::__construct($message, 0, $previous);
        $this->retryAfterSeconds = $retryAfterSeconds;
    }

    /**
     * Wraps a storage error with the generic message.
     */
    public static function fromPrevious(Throwable $previous, ?int $retryAfterSeconds = null): self
    {
        return new self($retryAfterSeconds, self::DEFAULT_MESSAGE, $previous);
    }

    /**
     * @return int|null Seconds after which a retry is sensible, or null when the runner gives no hint
     */
    public function retryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }
}
