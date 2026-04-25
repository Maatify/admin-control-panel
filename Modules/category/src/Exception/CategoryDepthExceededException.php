<?php

declare(strict_types=1);

namespace Maatify\Category\Exception;

use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;

/**
 * Thrown when attempting to nest a category deeper than the allowed max depth.
 *
 * Max allowed depth: 1 (root = 0, sub-category = 1).
 * Attempting to create a sub-category under another sub-category would
 * produce depth 2, which this module does not support.
 *
 * This rule is enforced by CategoryCommandService — not by the database.
 *
 * Family  : Conflict
 * HTTP    : 409
 * Category: CONFLICT
 */
final class CategoryDepthExceededException extends GenericConflictMaatifyException
    implements CategoryExceptionInterface
{
    public static function parentIsAlreadySubCategory(int $parentId): self
    {
        return new self(sprintf(
            'Category id %d is already a sub-category. Cannot nest further. Max depth is 1.',
            $parentId,
        ));
    }
}

