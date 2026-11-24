<?php

declare(strict_types=1);

namespace Nowo\TransactionContract;

use Throwable;

/**
 * Marks an exception as a lock conflict the client may simply retry. Anywhere in a failure's previous chain, it makes
 * a conflict responder (transaction-doctrine's, for example) answer the configured status (409 by default) with
 * `Retry-After`, on any route: hosts and bundles mark their own exceptions without the responder matching route
 * names.
 *
 * Framework-free: a bundle's domain or contract layer may implement it.
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
interface LockConflictExceptionInterface extends Throwable
{
}
