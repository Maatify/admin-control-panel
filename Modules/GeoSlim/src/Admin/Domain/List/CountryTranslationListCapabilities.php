<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Domain\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class CountryTranslationListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: [
                'language_code',
                'language_name',
                'name'
            ],
            supportsColumnFilters: true,
            filterableColumns: [
                'language_id'       => 'language_id',
                'language_code'     => 'language_code',
                'language_name'     => 'language_name',
                'name'              => 'name',
                'has_translation'   => 'has_translation',
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}

