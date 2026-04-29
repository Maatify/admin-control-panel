<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Rate\DTO;

/** Full detail DTO — admin edit screen and findById. */
final readonly class RateDTO implements \JsonSerializable
{
    public function __construct(
        public int     $id,
        public int     $providerId,
        public string  $providerName,
        public string  $providerCode,
        public string  $baseCurrencyCode,
        public string  $targetCurrencyCode,
        public string  $rate,           // decimal string — never float
        public bool    $isActive,
        public int     $displayOrder,
        public string  $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     provider_id: int,
     *     provider_name: string,
     *     provider_code: string,
     *     base_currency_code: string,
     *     target_currency_code: string,
     *     rate: string,
     *     is_active: bool,
     *     display_order: int,
     *     created_at: string,
     *     updated_at: string|null,
     *     deleted_at: string|null,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id'                   => $this->id,
            'provider_id'          => $this->providerId,
            'provider_name'        => $this->providerName,
            'provider_code'        => $this->providerCode,
            'base_currency_code'   => $this->baseCurrencyCode,
            'target_currency_code' => $this->targetCurrencyCode,
            'rate'                 => $this->rate,
            'is_active'            => $this->isActive,
            'display_order'        => $this->displayOrder,
            'created_at'           => $this->createdAt,
            'updated_at'           => $this->updatedAt,
            'deleted_at'           => $this->deletedAt,
        ];
    }
}
