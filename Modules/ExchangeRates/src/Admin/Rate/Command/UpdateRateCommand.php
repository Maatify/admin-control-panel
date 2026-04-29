<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Rate\Command;

use Maatify\ExchangeRates\Exception\ExchangeRatesInvalidArgumentException;

/**
 * Self-validating command for updating a rate's value.
 *
 * Only the rate value changes — pair and provider are identity, never updated.
 * recordedAt is the provider publish time for the history archive.
 */
final readonly class UpdateRateCommand
{
    public function __construct(
        public int     $id,
        public string  $rate,
        public ?string $recordedAt = null,
    ) {
        if ($id < 1) {
            throw ExchangeRatesInvalidArgumentException::invalidId('id');
        }

        if (! preg_match('/^\d+(?:\.\d{1,10})?$/', $rate)) {
            throw ExchangeRatesInvalidArgumentException::invalidDecimal('rate', $rate);
        }
        if (bccomp($rate, '0', 10) !== 1) {
            throw ExchangeRatesInvalidArgumentException::rateMustBePositive($rate);
        }

        if ($recordedAt !== null) {
            try {
                $dt = new \DateTimeImmutable($recordedAt);
                if ($dt->format('Y-m-d H:i:s') !== $recordedAt) {
                    throw ExchangeRatesInvalidArgumentException::invalidDatetime('recorded_at', $recordedAt);
                }
            } catch (\Exception) {
                throw ExchangeRatesInvalidArgumentException::invalidDatetime('recorded_at', $recordedAt);
            }
        }
    }
}
