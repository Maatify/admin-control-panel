<?php

declare(strict_types=1);

namespace Maatify\Persistence\Exception;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\Unsupported\UnsupportedMaatifyException;

/**
 * Thrown when ScopedOrderingManager cannot safely own the transaction.
 *
 * ScopedOrderingManager::moveWithinScope() is intentionally self-transactional.
 * It must be called outside an active PDO transaction to avoid ambiguous
 * transaction ownership and partial ordering updates.
 */
final class OrderingTransactionException extends UnsupportedMaatifyException implements PersistenceException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ErrorCodeEnum::UNSUPPORTED_OPERATION;
    }

    protected function defaultIsSafe(): bool
    {
        return false;
    }
}
