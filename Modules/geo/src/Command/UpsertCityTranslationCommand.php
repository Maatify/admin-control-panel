<?php

declare(strict_types=1);

namespace Maatify\Geo\Command;

use Maatify\Geo\Exception\GeoInvalidArgumentException;

/**
 * Upserts a translated name for a city in a specific language.
 * Uses INSERT … ON DUPLICATE KEY UPDATE — safe to call for create or update.
 */
final readonly class UpsertCityTranslationCommand
{
    public function __construct(
        public int    $cityId,
        public int    $languageId,
        public string $translatedName,
    ) {
        if ($cityId < 1) {
            throw GeoInvalidArgumentException::invalidId('cityId');
        }
        if ($languageId < 1) {
            throw GeoInvalidArgumentException::invalidId('languageId');
        }
        if (trim($translatedName) === '') {
            throw GeoInvalidArgumentException::emptyField('translatedName');
        }
    }
}
