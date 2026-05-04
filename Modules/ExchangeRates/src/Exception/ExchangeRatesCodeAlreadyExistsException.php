<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Exception;

final class ExchangeRatesCodeAlreadyExistsException extends \RuntimeException
    implements ExchangeRatesExceptionInterface
{
    public static function withCode(string $code): self
    {
        return new self("ExchangeRates: provider code [{$code}] already exists.");
    }

    public static function withPair(string $base, string $target, int $providerId): self
    {
        return new self(
            "ExchangeRates: a rate for pair [{$base}/{$target}] already exists for provider_id [{$providerId}]."
        );
    }
}
