<?php

declare(strict_types=1);

namespace Maatify\Geo\Command;

use Maatify\Geo\Exception\GeoInvalidArgumentException;

/** Removes a city translation for a specific language. Silent no-op if the row does not exist. */
final readonly class DeleteCityTranslationCommand
{
    public function __construct(
        public int $cityId,
        public int $languageId,
    ) {
        if ($cityId < 1) {
            throw GeoInvalidArgumentException::invalidId('cityId');
        }
        if ($languageId < 1) {
            throw GeoInvalidArgumentException::invalidId('languageId');
        }
    }
}
