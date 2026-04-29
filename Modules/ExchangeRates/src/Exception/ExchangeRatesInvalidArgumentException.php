<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Exception;

final class ExchangeRatesInvalidArgumentException extends \RuntimeException
    implements ExchangeRatesExceptionInterface
{
    public static function emptyField(string $field): self
    {
        return new self("ExchangeRates: field [{$field}] must not be empty.");
    }

    public static function invalidId(string $field): self
    {
        return new self("ExchangeRates: field [{$field}] must be a positive integer >= 1.");
    }

    public static function invalidDecimal(string $field, string $given): self
    {
        return new self("ExchangeRates: invalid decimal for [{$field}]: [{$given}]. Expected format: digits with up to 10 decimal places.");
    }

    public static function invalidCurrencyCode(string $field, string $given): self
    {
        return new self("ExchangeRates: [{$field}] must be a valid ISO 4217 code (3 uppercase A-Z letters). Got [{$given}].");
    }

    public static function sameCurrencyPair(): self
    {
        return new self("ExchangeRates: base_currency_code and target_currency_code must be different.");
    }

    public static function rateMustBePositive(string $given): self
    {
        return new self("ExchangeRates: rate must be greater than zero. Got [{$given}].");
    }

    public static function invalidDatetime(string $field, string $given): self
    {
        return new self("ExchangeRates: [{$field}] must be a valid datetime string (Y-m-d H:i:s). Got [{$given}].");
    }

    public static function fieldTooLong(string $field, int $max): self
    {
        return new self("ExchangeRates: field [{$field}] must not exceed {$max} characters.");
    }

    public static function invalidScale(int $given): self
    {
        return new self("ExchangeRates: [scale] must be >= 0. Got [{$given}].");
    }
}
