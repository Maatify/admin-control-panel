<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Customer\Rate\DTO;

/** Minimal DTO exposed to customer-facing layers. */
final readonly class CustomerRateDTO implements \JsonSerializable
{
    public function __construct(
        public string $baseCurrencyCode,
        public string $targetCurrencyCode,
        public string $rate,
        public int    $providerId,  // internal use — not exposed in jsonSerialize()
    ) {}

    /**
     * @return array{
     *     base_currency_code: string,
     *     target_currency_code: string,
     *     rate: string,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'base_currency_code'   => $this->baseCurrencyCode,
            'target_currency_code' => $this->targetCurrencyCode,
            'rate'                 => $this->rate,
        ];
    }
}
