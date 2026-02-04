<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\List;

final class LanguageListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
        // 🔍 Global search (free text)
            supportsGlobalSearch : true,
            searchableColumns    : [
                'name',
                'code',
            ],

            // 🎯 Explicit column filters
            supportsColumnFilters: true,
            filterableColumns    : [
                'name' => 'name',
                'code' => 'code',
                'is_active' => 'is_active',
                'direction' => 'direction',
            ],

            supportsDateFilter   : false,
            dateColumn           : null
        );
    }
}

