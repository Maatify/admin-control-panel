<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Customer\Rate\Service;

use Maatify\ExchangeRates\Customer\Rate\Contract\CustomerRateQueryRepositoryInterface;
use Maatify\ExchangeRates\Customer\Rate\DTO\CustomerRateCollectionDTO;
use Maatify\ExchangeRates\Exception\ExchangeRatesInvalidArgumentException;

/**
 * Primary public API for exchange-rate lookups and conversion.
 *
 * Rate arithmetic uses bcmath — never native float.
 * All monetary values are handled as strings throughout.
 *
 * Usage examples:
 *
 *   $rate = $service->currentRate('USD', 'EGP');
 *   // '48.7500000000' or null
 *
 *   $egp = $service->convert('100.00', 'USD', 'EGP', null, 2);
 *   // '4875.00' or null
 */
final class CustomerRateQueryService
{
    public function __construct(
        private readonly CustomerRateQueryRepositoryInterface $queryRepo,
    ) {}

    /**
     * Return the current rate string for a currency pair.
     *
     * @param  string   $baseCurrencyCode    ISO 4217 e.g. 'USD'
     * @param  string   $targetCurrencyCode  ISO 4217 e.g. 'EGP'
     * @param  int|null $providerId          null = first active provider by display_order
     * @return string|null                   Decimal string or null if no rate found
     */
    public function currentRate(
        string  $baseCurrencyCode,
        string  $targetCurrencyCode,
        ?int    $providerId = null,
    ): ?string {
        return $this->queryRepo->findCurrentRate(
            $baseCurrencyCode,
            $targetCurrencyCode,
            $providerId
        );
    }

    /**
     * Convert an amount from one currency to another.
     *
     * @param  string   $amount              Decimal string e.g. '100.00'
     * @param  string   $baseCurrencyCode
     * @param  string   $targetCurrencyCode
     * @param  int|null $providerId
     * @param  int      $scale               Decimal places in result (default 2)
     * @return string|null                   Converted amount as decimal string, or null if no rate
     *
     * @throws ExchangeRatesInvalidArgumentException  When amount format is invalid
     */
    public function convert(
        string  $amount,
        string  $baseCurrencyCode,
        string  $targetCurrencyCode,
        ?int    $providerId = null,
        int     $scale      = 2,
    ): ?string {
        if ($scale < 0) {
            throw ExchangeRatesInvalidArgumentException::invalidScale($scale);
        }

        if (! preg_match('/^\d+(?:\.\d+)?$/', $amount)) {
            throw ExchangeRatesInvalidArgumentException::invalidDecimal('amount', $amount);
        }

        $rate = $this->queryRepo->findCurrentRate(
            $baseCurrencyCode,
            $targetCurrencyCode,
            $providerId
        );

        if ($rate === null) {
            return null;
        }

        return bcmul($amount, $rate, $scale);
    }

    /**
     * Return all active rates for a base currency.
     */
    public function activeRatesForBase(
        string  $baseCurrencyCode,
        ?int    $providerId = null,
    ): CustomerRateCollectionDTO {
        return $this->queryRepo->listActiveForBase($baseCurrencyCode, $providerId);
    }
}
