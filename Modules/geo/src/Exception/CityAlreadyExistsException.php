<?php

declare(strict_types=1);

namespace Maatify\Geo\Exception;

use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;

/**
 * Thrown when a city with the same name already exists in the same country.
 *
 * Family  : Conflict
 * HTTP    : 409
 * Category: CONFLICT
 */
final class CityAlreadyExistsException extends GenericConflictMaatifyException
    implements GeoExceptionInterface
{
    public static function withNameAndCountryId(string $name, int $countryId): self
    {
        return new self(
            sprintf('City "%s" already exists in country #%d.', $name, $countryId)
        );
    }
}

