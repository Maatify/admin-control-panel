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
            searchableColumns: ['name', 'code'],
            supportsColumnFilters: true,
            filterableColumns: [
                'name' => 'name',
                'code' => 'code',
                'is_active' => 'is_active',
                'deleted'   => 'deleted',
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}
