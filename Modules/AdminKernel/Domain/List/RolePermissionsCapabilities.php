<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\List;

class RolePermissionsCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
        // Global search
            supportsGlobalSearch: true,
            searchableColumns: [
                'name',
            ],

            // Column filters
            supportsColumnFilters: true,
            filterableColumns: [
                'id'       => 'id',
                'name'     => 'name',
                'group'    => 'group',     // derived from name
                'assigned' => 'assigned',  // logical (0/1)
            ],

            // Date range
            supportsDateFilter: false,
            dateColumn: 'created_at'
        );
    }
}
