<?php

declare(strict_types=1);

namespace Maatify\Category\Exception;

use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

/**
 * Thrown when a category row cannot be located by id or slug.
 *
 * Family  : NotFound
 * HTTP    : 404
 * Category: NOT_FOUND
 */
final class CategoryNotFoundException extends ResourceNotFoundMaatifyException
    implements CategoryExceptionInterface
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Category with id %d not found.', $id));
    }

    public static function withSlug(string $slug): self
    {
        return new self(sprintf('Category with slug "%s" not found.', $slug));
    }
}

