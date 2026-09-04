<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\List;

final class DirectPermissionsCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
        // ─────────────────────────────
        // Global search
        // ─────────────────────────────
            supportsGlobalSearch: true,
            searchableColumns: [
                'name',
                'display_name',
                'description',
                'group',
            ],

            // ─────────────────────────────
            // Column filters
            // ─────────────────────────────
            supportsColumnFilters: true,
            filterableColumns: [
                'id'         => 'id',
                'name'       => 'name',
                'group'      => 'group',
                'is_allowed' => 'is_allowed',
            ],

            // ─────────────────────────────
            // Date filter
            // ─────────────────────────────
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}
