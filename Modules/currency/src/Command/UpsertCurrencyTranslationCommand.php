<?php

declare(strict_types=1);

namespace Maatify\Currency\Command;

/**
 * Creates or updates the localised name for a (currency, language) pair.
 * INSERT … ON DUPLICATE KEY UPDATE semantics (upsert).
 */
final class UpsertCurrencyTranslationCommand
{
    public function __construct(
        public readonly int    $currencyId,
        public readonly int    $languageId,
        public readonly string $translatedName,
    ) {}
}
