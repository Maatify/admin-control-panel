<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Domain\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class CityListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: ['name', 'code'],
            supportsColumnFilters: true,
            filterableColumns: [
                'id'         => 'id',
                'name'       => 'name',
                'code'       => 'code',
                'country_id' => 'int',
                'is_active'  => 'is_active',
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}

