<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\RateHistory\Contract;

interface RateHistoryQueryRepositoryInterface
{
    /**
     * Paginated history entries for a single rate_id.
     *
     * @return array{data: list<\Maatify\ExchangeRates\Admin\RateHistory\DTO\RateHistoryListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function listByRateId(int $rateId, int $page, int $perPage): array;

    /**
     * Paginated history entries.
     *
     * @param array<string, mixed> $columnFilters
     * @return array{
     *     data: list<\Maatify\ExchangeRates\Admin\RateHistory\DTO\RateHistoryListItemDTO>,
     *     pagination: array{
     *         page: int,
     *         per_page: int,
     *         total: int,
     *         filtered: int
     *     }
     * }
     */
    public function list(
        int $page,
        int $perPage,
        ?string $globalSearch,
        array $columnFilters
    ): array;

    /**
     * Paginated history for a currency pair, optionally scoped to a provider.
     *
     * @return array{data: list<\Maatify\ExchangeRates\Admin\RateHistory\DTO\RateHistoryListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function listByPair(
        string  $baseCurrencyCode,
        string  $targetCurrencyCode,
        int     $page,
        int     $perPage,
        ?int    $providerId,
    ): array;

    /**
     * Return the rate value that was active at or before $atDatetime.
     * Returns null if no history row exists before that point.
     *
     * @param  string  $atDatetime  'Y-m-d H:i:s'
     */
    public function findRateAt(
        string  $baseCurrencyCode,
        string  $targetCurrencyCode,
        string  $atDatetime,
        ?int    $providerId,
    ): ?string;
}
