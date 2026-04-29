<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\RateHistory\DTO;

final readonly class RateHistoryListItemDTO implements \JsonSerializable
{
    public function __construct(
        public int    $id,
        public int    $rateId,
        public int    $providerId,
        public string $providerName,
        public string $baseCurrencyCode,
        public string $targetCurrencyCode,
        public string $rate,
        public string $recordedAt,
        public string $createdAt,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     rate_id: int,
     *     provider_id: int,
     *     provider_name: string,
     *     base_currency_code: string,
     *     target_currency_code: string,
     *     rate: string,
     *     recorded_at: string,
     *     created_at: string,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id'                   => $this->id,
            'rate_id'              => $this->rateId,
            'provider_id'          => $this->providerId,
            'provider_name'        => $this->providerName,
            'base_currency_code'   => $this->baseCurrencyCode,
            'target_currency_code' => $this->targetCurrencyCode,
            'rate'                 => $this->rate,
            'recorded_at'          => $this->recordedAt,
            'created_at'           => $this->createdAt,
        ];
    }
}
