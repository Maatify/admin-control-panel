<?php

declare(strict_types=1);

namespace Maatify\Geo\Exception;

use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;

/**
 * Thrown when an argument supplied to a geo operation is semantically invalid.
 *
 * Family  : Validation
 * HTTP    : 400
 * Category: VALIDATION
 */
final class GeoInvalidArgumentException extends InvalidArgumentMaatifyException
    implements GeoExceptionInterface
{
    public static function emptyField(string $field): self
    {
        return new self(sprintf('Field [%s] must not be empty.', $field));
    }

    public static function invalidId(string $field): self
    {
        return new self(sprintf('Field [%s] must be a positive integer >= 1.', $field));
    }

    public static function invalidCode(string $code): self
    {
        return new self(
            sprintf('Country code [%s] must be a 2-character ISO alpha-2 string.', $code),
        );
    }

    public static function invalidDisplayOrder(int $given): self
    {
        return new self(
            sprintf('display_order must be >= 1, got %d.', $given),
        );
    }

    public static function invalidLanguageId(int $languageId): self
    {
        return new self(
            sprintf('Language id %d does not exist.', $languageId),
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
