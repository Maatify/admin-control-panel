<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Exception;

use Maatify\Catalog\Exception\CatalogExceptionInterface;
use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;

final class CategoryInvalidArgumentException extends InvalidArgumentMaatifyException
    implements CatalogExceptionInterface
{
    public static function nonPositiveId(string $field): self
    {
        return new self(sprintf('Field "%s" must be a positive integer.', $field));
    }

    public static function selfParent(int $categoryId): self
    {
        return new self(sprintf('Category [%d] cannot be its own parent.', $categoryId));
    }
}
