<?php

declare(strict_types=1);

namespace Maatify\Category\Exception;

use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;

/**
 * Thrown when an argument supplied to a category operation is semantically invalid.
 *
 * Examples:
 *   - display_order < 1 in reorder()
 *   - a non-numeric or non-string value in a DTO row-casting helper
 *   - an invalid parent_id reference
 *
 * Family  : Validation
 * HTTP    : 400
 * Category: VALIDATION
 */
final class CategoryInvalidArgumentException extends InvalidArgumentMaatifyException
    implements CategoryExceptionInterface
{
    public static function invalidDisplayOrder(int $given): self
    {
        return new self(
            sprintf('display_order must be >= 1, got %d.', $given),
        );
    }

    public static function invalidLanguageId(int $languageId): self
    {
        return new self(
            sprintf('Language id %d does not exist in the languages table.', $languageId),
        );
    }

    public static function invalidParentId(int $parentId): self
    {
        return new self(
            sprintf('Parent category id %d does not exist.', $parentId),
        );
    }

    public static function unexpectedType(string $field, mixed $value): self
    {
        return new self(
            sprintf(
                'Field "%s" has unexpected type %s.',
                $field,
                get_debug_type($value),
            ),
        );
    }
}
