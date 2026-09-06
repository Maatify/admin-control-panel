<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Exception;

use Maatify\Catalog\Exception\CatalogExceptionInterface;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\System\SystemMaatifyException;
use Throwable;

/** Represents Catalog-owned persistence and storage-shape failures. */
final class CategoryPersistenceException extends SystemMaatifyException
    implements CatalogExceptionInterface
{
    protected function defaultErrorCode(): ErrorCodeEnum
    {
        return ErrorCodeEnum::MAATIFY_ERROR;
    }

    public static function queryFailed(string $resource): self
    {
        return new self(sprintf('Catalog query failed for %s.', $resource));
    }

    public static function invalidAutoIncrementIdentity(): self
    {
        return new self('Catalog Category AUTO_INCREMENT did not return a valid identity.');
    }

    public static function invalidStorageValue(string $column, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('Catalog storage column "%s" contains an invalid value.', $column),
            0,
            $previous,
        );
    }

    public static function unexpectedColumnType(string $column): self
    {
        return new self(sprintf('Catalog storage column "%s" has an unexpected type.', $column));
    }
}
