<?php

declare(strict_types=1);

namespace Maatify\Currency\Exception;

use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\System\SystemMaatifyException;

/**
 * Thrown when a low-level database operation fails inside the currency
 * infrastructure layer (PDO prepare failure, unexpected column type,
 * DI container misconfiguration, etc.).
 *
 * Extends SystemMaatifyException (abstract) directly — the concrete sibling
 * DatabaseConnectionMaatifyException is final and cannot be inherited.
 *
 * Family  : System
 * HTTP    : 500
 * Category: SYSTEM
 * Code    : DATABASE_CONNECTION_FAILED
 */
final class CurrencyPersistenceException extends SystemMaatifyException
    implements CurrencyExceptionInterface
{
    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::DATABASE_CONNECTION_FAILED;
    }

    // ------------------------------------------------------------------ //
    //  Named factory methods
    // ------------------------------------------------------------------ //

    public static function prepareFailed(string $sql): self
    {
        return new self(sprintf('PDO::prepare() failed for query: %s', $sql));
    }

    public static function unexpectedColumnType(string $column, int $id): self
    {
        return new self(
            sprintf('Column "%s" for currency id %d has an unexpected type.', $column, $id),
        );
    }

    public static function containerTypeMismatch(string $expected): self
    {
        return new self(
            sprintf('DI container did not return an instance of %s.', $expected),
        );
    }

    /**
     * Wraps a raw \PDOException that was not handled by domain-specific logic.
     */
    public static function fromPdoException(\PDOException $e): self
    {
        return new self($e->getMessage(), 0, $e);
    }

    /**
     * Wraps any unexpected \Throwable that escaped the infrastructure layer.
     * Only call this after confirming the exception is NOT a CurrencyExceptionInterface.
     */
    public static function fromThrowable(\Throwable $e): self
    {
        return new self($e->getMessage(), 0, $e);
    }
}
