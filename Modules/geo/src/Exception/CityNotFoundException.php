<?php

declare(strict_types=1);

namespace Maatify\Geo\Exception;

use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

/**
 * Thrown when a city row cannot be located by id.
 *
 * Family  : NotFound
 * HTTP    : 404
 * Category: NOT_FOUND
 */
final class CityNotFoundException extends ResourceNotFoundMaatifyException
    implements GeoExceptionInterface
{
    public static function withId(int $id): self
    {
        return new self(sprintf('City with id %d not found.', $id));
    }
}
