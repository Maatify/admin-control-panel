<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Exception;

use Maatify\Catalog\Exception\CatalogExceptionInterface;
use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;

final class CategoryCodeAlreadyExistsException extends GenericConflictMaatifyException
    implements CatalogExceptionInterface
{
    public static function withCode(string $code): self
    {
        return new self(sprintf('Category code "%s" already exists.', $code));
    }
}
