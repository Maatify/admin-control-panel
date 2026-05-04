<?php

declare(strict_types=1);

namespace Maatify\ExchangeRatesSlim\Admin\Domain\List;

use Maatify\AdminKernel\Domain\List\ListCapabilities;

final class RateListCapabilities
{
    public static function define(): ListCapabilities
    {
        return new ListCapabilities(
            supportsGlobalSearch: true,
            searchableColumns: ['base_currency_code', 'target_currency_code'],
            supportsColumnFilters: true,
            filterableColumns: [
                'provider_id' => 'provider_id',
                'is_active' => 'is_active',
                'base_currency_code' => 'base_currency_code',
                'deleted' => 'deleted',
            ],
            supportsDateFilter: false,
            dateColumn: null
        );
    }
}
