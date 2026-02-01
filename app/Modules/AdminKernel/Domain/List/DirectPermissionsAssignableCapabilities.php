<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\List;

final class DirectPermissionsAssignableCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: [
                'name',
                'display_name',
                'description',
                'group',
            ],

            supportsColumnFilters: true,
            filterableColumns: [
                'id'        => 'id',
                'name'      => 'name',
                'group'     => 'group',
                'assigned'  => 'assigned', // 1 = has direct entry, 0 = none
            ],

            supportsDateFilter: false,
            dateColumn: null
        );
    }
}
