<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Rate\DTO;

final readonly class RateListItemDTO implements \JsonSerializable
{
    public function __construct(
        public int    $id,
        public int    $providerId,
        public string $providerName,
        public string $baseCurrencyCode,
        public string $targetCurrencyCode,
        public string $rate,
        public bool   $isActive,
        public int    $displayOrder,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     provider_id: int,
     *     provider_name: string,
     *     base_currency_code: string,
     *     target_currency_code: string,
     *     rate: string,
     *     is_active: bool,
     *     display_order: int,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id'                   => $this->id,
            'provider_id'          => $this->providerId,
            'provider_name'        => $this->providerName,
            'base_currency_code'   => $this->baseCurrencyCode,
            'target_currency_code' => $this->targetCurrencyCode,
            'rate'                 => $this->rate,
            'is_active'            => $this->isActive,
            'display_order'        => $this->displayOrder,
        ];
    }
}
