<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\ExchangeRates\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class RateHistoryListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: false,
            searchableColumns: [],
            supportsColumnFilters: true,
            filterableColumns: [
                'id' => 'id',
                'rate_id' => 'rate_id',
                'provider_id' => 'provider_id',
                'base_currency_code' => 'base_currency_code',
                'target_currency_code' => 'target_currency_code',
            ],
            supportsDateFilter: true,
            dateColumn: 'recorded_at'
        );
    }
}
