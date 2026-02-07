<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\List;

final class I18nScopesListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            // 🔍 Global search (free text)
            supportsGlobalSearch : true,
            searchableColumns    : [
                'code',
                'name',
                'description',
            ],

            // 🎯 Explicit column filters
            supportsColumnFilters: true,
            filterableColumns    : [
                'id' => 'id',
                'code' => 'code',
                'name' => 'name',
                'is_active' => 'is_active',
            ],

            supportsDateFilter   : false,
            dateColumn           : null
        );
    }
}

