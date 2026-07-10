<?php

declare(strict_types=1);

namespace Maatify\Persistence\Exception;

use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\System\SystemMaatifyException;

/**
 * Thrown when an ordering configuration is invalid.
 *
 * Examples:
 * - Invalid table identifier.
 * - Invalid column identifier.
 * - Unsafe SQL identifier containing unsupported characters.
 *
 * This exception is intended for configuration/programming mistakes, not for
 * user-input validation failures.
 */
final class InvalidOrderingConfigurationException extends SystemMaatifyException implements PersistenceException
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ErrorCodeEnum::MAATIFY_ERROR;
    }
}
