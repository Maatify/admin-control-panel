<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Exception;

use Maatify\Catalog\Exception\CatalogExceptionInterface;
use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

final class CategoryNotFoundException extends ResourceNotFoundMaatifyException
    implements CatalogExceptionInterface
{
    public static function withId(int $categoryId): self
    {
        return new self(sprintf('Category with id %d was not found.', $categoryId));
    }
}
