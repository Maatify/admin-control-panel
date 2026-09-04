<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 00:41
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\List;

final class AdminListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            // Global search (logical fields only)
            supportsGlobalSearch: true,
            searchableColumns: [
                'email',
                'display_name',
                'status'
            ],

            // Column filters (alias => logical field)
            supportsColumnFilters: true,
            filterableColumns: [
                'id'    => 'id',
                'email' => 'email',
                'display_name' => 'display_name',
                'status' => 'status',
            ],

            // Date range
            supportsDateFilter: true,
            dateColumn: 'created_at'
        );
    }
}

