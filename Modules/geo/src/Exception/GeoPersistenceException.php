<?php

declare(strict_types=1);

namespace Maatify\Geo\Exception;

use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\System\SystemMaatifyException;

/**
 * Thrown when a low-level database operation fails inside the Geo
 * infrastructure layer.
 *
 * Family  : System
 * HTTP    : 500
 * Category: SYSTEM
 * Code    : DATABASE_CONNECTION_FAILED
 */
final class GeoPersistenceException extends SystemMaatifyException
    implements GeoExceptionInterface
{
    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::DATABASE_CONNECTION_FAILED;
    }

    public static function prepareFailed(string $sql): self
    {
        return new self(sprintf('PDO::prepare() failed for query: %s', $sql));
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