<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ExchangeRates\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class ProviderListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: ['name', 'code', 'description'],
            supportsColumnFilters: true,
            filterableColumns: [
                'id' => 'id',
                'name' => 'name',
                'code' => 'code',
                'is_active' => 'is_active',
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}
