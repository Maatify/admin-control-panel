<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\List;

final class PermissionAdminsQueryCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
        // ─────────────────────────────
        // Global search
        // ─────────────────────────────
            supportsGlobalSearch: true,
            searchableColumns: [
                'admin_display_name',
            ],

            // ─────────────────────────────
            // Column filters
            // ─────────────────────────────
            supportsColumnFilters: true,
            filterableColumns: [
                'admin_id'   => 'admin_id',
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
