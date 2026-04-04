<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Currency\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class CurrencyListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: ['name', 'code', 'symbol'],
            supportsColumnFilters: true,
            filterableColumns: [
                'is_active' => 'bool',
                'code' => 'string'
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}
