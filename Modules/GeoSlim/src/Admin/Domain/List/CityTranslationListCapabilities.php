<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Domain\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class CityTranslationListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: ['name'],              // geo_city_translations.name is the only searchable column
            supportsColumnFilters: true,
            filterableColumns: [
                'language_id' => 'int',              // handled: `language_id` = :language_id
                'name'        => 'string',           // handled: `name` LIKE :trans_name
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}

