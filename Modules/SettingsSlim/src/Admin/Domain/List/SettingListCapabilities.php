<?php

declare(strict_types=1);

namespace Maatify\SettingsSlim\Admin\Domain\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class SettingListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: ['key', 'admin_note'],
            supportsColumnFilters: true,
            filterableColumns: [
                'key' => 'key',
                'admin_note' => 'admin_note',
                'value_type' => 'value_type',
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}
