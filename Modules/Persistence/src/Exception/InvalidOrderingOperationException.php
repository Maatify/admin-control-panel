<?php

declare(strict_types=1);

namespace Maatify\Persistence\Exception;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\Validation\ValidationMaatifyException;

/**
 * Thrown when an ordering operation is called with invalid runtime arguments.
 *
 * Examples:
 * - Invalid row id.
 * - Invalid target/current ordering position.
 * - Scope value provided without a scope column.
 * - Scope column configured but no scope value provided.
 */
final class InvalidOrderingOperationException extends ValidationMaatifyException implements PersistenceException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ErrorCodeEnum::INVALID_ARGUMENT;
    }
}
