<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\List\I18n;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class I18nScopeDomainsListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: [
                'code',
                'name',
                'description',
            ],
            supportsColumnFilters: true,
            filterableColumns: [
                'id' => 'id',
                'code' => 'code',
                'name' => 'name',
                'is_active' => 'is_active',
                'assigned' => 'assigned',
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}
