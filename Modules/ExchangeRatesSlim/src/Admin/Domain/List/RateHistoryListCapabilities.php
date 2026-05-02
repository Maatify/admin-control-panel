<?php

declare(strict_types=1);

namespace Maatify\ExchangeRatesSlim\Admin\Domain\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class RateHistoryListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: [
                'base_currency_code',
                'target_currency_code'
            ],
            supportsColumnFilters: true,
            filterableColumns: [
                'id'                    => 'id',
                'rate_id'               => 'rate_id',
                'provider_id'           => 'provider_id',
                'base_currency_code'    => 'base_currency_code',
                'target_currency_code'  => 'target_currency_code',
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}
