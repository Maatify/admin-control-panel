<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Customer\Rate\Contract;

use Maatify\ExchangeRates\Customer\Rate\DTO\CustomerRateCollectionDTO;

interface CustomerRateQueryRepositoryInterface
{
    /**
     * Return the current rate string for a pair.
     * Returns null when no active rate is found.
     *
     * @param  string  $baseCurrencyCode    ISO 4217
     * @param  string  $targetCurrencyCode  ISO 4217
     * @param  int|null $providerId          null = any provider (ordered by display_order)
     */
    public function findCurrentRate(
        string  $baseCurrencyCode,
        string  $targetCurrencyCode,
        ?int    $providerId,
    ): ?string;

    /**
     * Return all active rates for a base currency.
     */
    public function listActiveForBase(
        string  $baseCurrencyCode,
        ?int    $providerId,
    ): CustomerRateCollectionDTO;
}
