<?php

declare(strict_types=1);

namespace Maatify\Geo\Command;

use Maatify\Geo\Exception\GeoInvalidArgumentException;

/** Removes a country translation for a specific language. Silent no-op if the row does not exist. */
final readonly class DeleteCountryTranslationCommand
{
    public function __construct(
        public int $countryId,
        public int $languageId,
    ) {
        if ($countryId < 1) {
            throw GeoInvalidArgumentException::invalidId('countryId');
        }
        if ($languageId < 1) {
            throw GeoInvalidArgumentException::invalidId('languageId');
        }
    }
}
