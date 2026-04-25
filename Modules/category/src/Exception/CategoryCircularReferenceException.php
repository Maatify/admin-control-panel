<?php

declare(strict_types=1);

namespace Maatify\Category\Exception;

use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;

/**
 * Thrown when a parentId change on update would create a circular reference.
 *
 * Example:
 *   Category A (root) has sub-category B.
 *   Attempting to set A's parent to B would make B both a child and
 *   an ancestor of A — a circular reference.
 *
 * This rule is enforced by CategoryCommandService.
 *
 * Family  : Conflict
 * HTTP    : 409
 * Category: CONFLICT
 */
final class CategoryCircularReferenceException extends GenericConflictMaatifyException
    implements CategoryExceptionInterface
{
    public static function detected(int $categoryId, int $targetParentId): self
    {
        return new self(sprintf(
            'Setting category id %d as parent of category id %d would create a circular reference.',
            $targetParentId,
            $categoryId,
        ));
    }
}

