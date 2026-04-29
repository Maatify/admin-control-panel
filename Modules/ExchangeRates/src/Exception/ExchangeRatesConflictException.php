<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Exception;

final class ExchangeRatesConflictException extends \RuntimeException
    implements ExchangeRatesExceptionInterface
{
    public static function providerIsDeleted(int $providerId): self
    {
        return new self(
            "ExchangeRates: provider_id [{$providerId}] is soft-deleted and cannot be used for new rates."
        );
    }

    public static function providerIsInactive(int $providerId): self
    {
        return new self(
            "ExchangeRates: provider_id [{$providerId}] is inactive. Activate it before creating rates."
        );
    }

    public static function rateValueUnchanged(int $rateId): self
    {
        return new self(
            "ExchangeRates: rate_id [{$rateId}] already has the submitted value. No change recorded."
        );
    }
}
