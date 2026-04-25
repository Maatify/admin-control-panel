<?php

declare(strict_types=1);

namespace Maatify\Category\Exception;

use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\System\SystemMaatifyException;

/**
 * Thrown when a low-level database operation fails inside the category
 * infrastructure layer (PDO prepare failure, unexpected column type, etc.).
 *
 * Family  : System
 * HTTP    : 500
 * Category: SYSTEM
 */
final class CategoryPersistenceException extends SystemMaatifyException
    implements CategoryExceptionInterface
{
    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::DATABASE_CONNECTION_FAILED;
    }

    public static function prepareFailed(string $sql): self
    {
        return new self(sprintf('PDO::prepare() failed for query: %s', $sql));
    }

    public static function unexpectedColumnType(string $column, int $id): self
    {
        return new self(
            sprintf('Column "%s" for category id %d has an unexpected type.', $column, $id),
        );
    }

    public static function fromPdoException(\PDOException $e): self
    {
        return new self($e->getMessage(), 0, $e);
    }

    public static function fromThrowable(\Throwable $e): self
    {
        return new self($e->getMessage(), 0, $e);
    }
}

