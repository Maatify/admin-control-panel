<?php

declare(strict_types=1);

namespace Maatify\Geo\Exception;

use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

/**
 * Thrown when a country row cannot be located by id or code.
 *
 * Family  : NotFound
 * HTTP    : 404
 * Category: NOT_FOUND
 */
final class CountryNotFoundException extends ResourceNotFoundMaatifyException
    implements GeoExceptionInterface
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Country with id %d not found.', $id));
    }

    public static function withCode(string $code): self
    {
        return new self(sprintf('Country with code "%s" not found.', $code));
    }
}
