<?php

declare(strict_types=1);

namespace Maatify\Currency\Exception;

use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;

/**
 * Thrown when an argument supplied to a currency operation is semantically invalid.
 *
 * Examples:
 *   - display_order < 1 in reorder()
 *   - a non-numeric or non-string value in a DTO row-casting helper
 *
 * Family  : Validation
 * HTTP    : 400
 * Category: VALIDATION
 */
final class CurrencyInvalidArgumentException extends InvalidArgumentMaatifyException
    implements CurrencyExceptionInterface
{
    public static function invalidDisplayOrder(int $given): self
    {
        return new self(
            sprintf('display_order must be >= 1, got %d.', $given),
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
