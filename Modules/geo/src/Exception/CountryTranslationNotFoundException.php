<?php

declare(strict_types=1);

namespace Maatify\Geo\Exception;

use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

/**
 * Thrown when a country translation row cannot be located for a
 * given (country_id, language_id) pair.
 *
 * Family  : NotFound
 * HTTP    : 404
 * Category: NOT_FOUND
 */
final class CountryTranslationNotFoundException extends ResourceNotFoundMaatifyException
    implements GeoExceptionInterface
{
    public static function for(int $countryId, int $languageId): self
    {
        return new self(
            sprintf(
                'Translation for country id %d in language id %d not found.',
                $countryId,
                $languageId,
            ),
        );
    }
}

