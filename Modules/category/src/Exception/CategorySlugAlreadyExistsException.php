<?php

declare(strict_types=1);

namespace Maatify\Category\Exception;

use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;

/**
 * Thrown when attempting to create or update a category with a slug that is
 * already used by another row (UNIQUE KEY violation on categories.slug).
 *
 * Family  : Conflict
 * HTTP    : 409
 * Category: CONFLICT
 */
final class CategorySlugAlreadyExistsException extends GenericConflictMaatifyException
    implements CategoryExceptionInterface
{
    public static function withSlug(string $slug): self
    {
        return new self(sprintf('A category with slug "%s" already exists.', $slug));
    }
}

