<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\List;

final class AppSettingsListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            // 🔍 Global search (free text)
            supportsGlobalSearch : true,
            searchableColumns    : [
                'setting_group',
                'setting_key',
                'setting_value',
            ],

            // 🎯 Explicit column filters
            supportsColumnFilters: true,
            filterableColumns    : [
                'id' => 'id',
                'setting_group' => 'setting_group',
                'setting_key' => 'setting_key',
                'is_active' => 'is_active',
            ],

            supportsDateFilter   : false,
            dateColumn           : null
        );
    }
}

