<?php

declare(strict_types=1);

namespace Maatify\Geo\Exception;

use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

/**
 * Thrown when a city translation row cannot be located for a
 * given (city_id, language_id) pair.
 *
 * Family  : NotFound
 * HTTP    : 404
 * Category: NOT_FOUND
 */
final class CityTranslationNotFoundException extends ResourceNotFoundMaatifyException
    implements GeoExceptionInterface
{
    public static function for(int $cityId, int $languageId): self
    {
        return new self(
            sprintf(
                'Translation for city id %d in language id %d not found.',
                $cityId,
                $languageId,
            ),
        );
    }
}

