<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ExchangeRates\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class RateListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: false,
            searchableColumns: [],
            supportsColumnFilters: true,
            filterableColumns: [
                'id' => 'id',
                'provider_id' => 'provider_id',
                'base_currency_code' => 'base_currency_code',
                'target_currency_code' => 'target_currency_code',
                'is_active' => 'is_active',
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}
