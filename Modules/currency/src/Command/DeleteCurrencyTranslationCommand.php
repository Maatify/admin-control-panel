<?php

declare(strict_types=1);

namespace Maatify\Currency\Command;

/**
 * Removes the localised name for a specific (currency, language) pair.
 * The COALESCE fallback in queries ensures the base name is served
 * transparently after deletion.
 */
final class DeleteCurrencyTranslationCommand
{
    public function __construct(
        public readonly int $currencyId,
        public readonly int $languageId,
    ) {}
}
