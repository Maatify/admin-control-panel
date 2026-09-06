<?php

declare(strict_types=1);

namespace Maatify\Catalog\Category\Exception;

use Maatify\Catalog\Exception\CatalogExceptionInterface;
use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;

final class CategoryInvalidArgumentException extends InvalidArgumentMaatifyException
    implements CatalogExceptionInterface
{
    public static function emptyField(string $field): self
    {
        return new self(sprintf('Field "%s" must not be empty.', $field));
    }

    public static function fieldTooLong(string $field, int $maxLength): self
    {
        return new self(sprintf('Field "%s" must not exceed %d characters.', $field, $maxLength));
    }

    public static function invalidId(string $field): self
    {
        return new self(sprintf('Field "%s" must be a canonical positive integer.', $field));
    }

    public static function nonPositiveId(string $field): self
    {
        return new self(sprintf('Field "%s" must be a positive integer.', $field));
    }

    public static function invalidDisplayOrder(int $displayOrder): self
    {
        return new self(sprintf('Display order must be a positive integer, got %d.', $displayOrder));
    }

    public static function selfParent(int $categoryId): self
    {
        return new self(sprintf('Category [%d] cannot be its own parent.', $categoryId));
    }
}
