<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Domain\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class CountryListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: ['code', 'name'],     // SQL: c.code LIKE :global_text OR c.name LIKE :global_text
            supportsColumnFilters: true,
            filterableColumns: [
                'id'                  => 'id',
                'name'                => 'name',
                'code'                => 'code',
                'is_active'           => 'is_active',
                'is_state_required'   => 'is_state_required',
                'is_postcode_required'=> 'is_postcode_required',
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}

