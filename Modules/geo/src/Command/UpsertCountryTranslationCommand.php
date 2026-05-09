<?php

declare(strict_types=1);

namespace Maatify\Geo\Command;

use Maatify\Geo\Exception\GeoInvalidArgumentException;

/**
 * Upserts a translated name for a country in a specific language.
 * Uses INSERT … ON DUPLICATE KEY UPDATE — safe to call for create or update.
 */
final readonly class UpsertCountryTranslationCommand
{
    public function __construct(
        public int    $countryId,
        public int    $languageId,
        public string $translatedName,
    ) {
        if ($countryId < 1) {
            throw GeoInvalidArgumentException::invalidId('countryId');
        }
        if ($languageId < 1) {
            throw GeoInvalidArgumentException::invalidId('languageId');
        }
        if (trim($translatedName) === '') {
            throw GeoInvalidArgumentException::emptyField('translatedName');
        }
    }
}
