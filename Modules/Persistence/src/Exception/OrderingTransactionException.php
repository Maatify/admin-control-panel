<?php

declare(strict_types=1);

namespace Maatify\Persistence\Exception;

use RuntimeException;

/**
 * Thrown when ScopedOrderingManager cannot safely own the transaction.
 *
 * ScopedOrderingManager::moveWithinScope() is intentionally self-transactional.
 * It must be called outside an active PDO transaction to avoid ambiguous
 * transaction ownership and partial ordering updates.
 */
final class OrderingTransactionException extends RuntimeException implements PersistenceException
{
}
