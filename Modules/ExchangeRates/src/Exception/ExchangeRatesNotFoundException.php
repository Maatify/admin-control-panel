<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Exception;

final class ExchangeRatesNotFoundException extends \RuntimeException
    implements ExchangeRatesExceptionInterface
{
    public static function withId(int $id): self
    {
        return new self("ExchangeRates: record with id [{$id}] not found.");
    }

    public static function withCode(string $code): self
    {
        return new self("ExchangeRates: record with code [{$code}] not found.");
    }

    public static function withPair(string $base, string $target): self
    {
        return new self("ExchangeRates: no active rate found for pair [{$base}/{$target}].");
    }

    public static function withRateId(int $rateId): self
    {
        return new self("ExchangeRates: no history found for rate_id [{$rateId}].");
    }
}
