<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Currency\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class CurrencyTranslationListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: ['language_name', 'language_code', 'translated_name'],
            supportsColumnFilters: true,
            filterableColumns: [
                'language_id' => 'int',
                'language_code' => 'string',
                'language_name' => 'string',
                'name' => 'string',
                'has_translation' => 'string'
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}
