<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Rate\Command;

use Maatify\ExchangeRates\Exception\ExchangeRatesInvalidArgumentException;

/**
 * Self-validating command for creating a new exchange rate.
 *
 * Rate convention: 1 base = rate target units
 * e.g. base=USD, target=EGP, rate='48.7500000000' → 1 USD = 48.75 EGP
 *
 * Rate is stored as a string throughout — never cast to float.
 * bcmath is used for all arithmetic.
 *
 * display_order is NOT in this command — auto-assigned on create.
 */
final readonly class CreateRateCommand
{
    public string $baseCurrencyCode;
    public string $targetCurrencyCode;

    public function __construct(
        public int    $providerId,
        string        $baseCurrencyCode,
        string        $targetCurrencyCode,
        public string $rate,
        public ?string $recordedAt = null,
    ) {
        if ($providerId < 1) {
            throw ExchangeRatesInvalidArgumentException::invalidId('provider_id');
        }

        $base   = strtoupper(trim($baseCurrencyCode));
        $target = strtoupper(trim($targetCurrencyCode));

        if (! preg_match('/^[A-Z]{3}$/', $base)) {
            throw ExchangeRatesInvalidArgumentException::invalidCurrencyCode('base_currency_code', $baseCurrencyCode);
        }
        if (! preg_match('/^[A-Z]{3}$/', $target)) {
            throw ExchangeRatesInvalidArgumentException::invalidCurrencyCode('target_currency_code', $targetCurrencyCode);
        }
        if ($base === $target) {
            throw ExchangeRatesInvalidArgumentException::sameCurrencyPair();
        }

        $this->baseCurrencyCode   = $base;
        $this->targetCurrencyCode = $target;

        // Validate decimal format — up to 10 decimal places
        if (! preg_match('/^\d+(?:\.\d{1,10})?$/', $rate)) {
            throw ExchangeRatesInvalidArgumentException::invalidDecimal('rate', $rate);
        }
        if (bccomp($rate, '0', 10) !== 1) {
            throw ExchangeRatesInvalidArgumentException::rateMustBePositive($rate);
        }

        if ($recordedAt !== null) {
            try {
                $dt = new \DateTimeImmutable($recordedAt);
                // Validate exact format
                if ($dt->format('Y-m-d H:i:s') !== $recordedAt) {
                    throw ExchangeRatesInvalidArgumentException::invalidDatetime('recorded_at', $recordedAt);
                }
            } catch (\Exception) {
                throw ExchangeRatesInvalidArgumentException::invalidDatetime('recorded_at', $recordedAt);
            }
        }
    }
}
