<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Category\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class CategoryTranslationListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: ['language_name', 'language_code', 'translated_name', 'description'],
            supportsColumnFilters: true,
            filterableColumns: [
                'language_id'          => 'int',
                'language_code'        => 'string',
                'language_name'        => 'string',
                'name'                 => 'string',
                'description'          => 'string',
                'has_translation'      => 'string',
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}

