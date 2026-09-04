<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\List;

final class RoleAdminsCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: [
                'display_name',
                'status',
            ],

            supportsColumnFilters: true,
            filterableColumns: [
                'id'       => 'id',
                'status'   => 'status',
                'assigned' => 'assigned',
            ],

            supportsDateFilter: false,
            dateColumn: 'created_at'
        );
    }
}
