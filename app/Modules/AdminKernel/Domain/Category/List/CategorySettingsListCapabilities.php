<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Category\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class CategorySettingsListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch:  true,
            searchableColumns:     ['key', 'value'],
            supportsColumnFilters: true,
            filterableColumns:     [
                'key'   => 'key',
                'value' => 'value',
            ],
            supportsDateFilter: false,
            dateColumn:         null,
        );
    }
}

