<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\RateHistory\Service;

use Maatify\ExchangeRates\Admin\RateHistory\Contract\RateHistoryQueryRepositoryInterface;
use Maatify\ExchangeRates\Exception\ExchangeRatesInvalidArgumentException;

final class RateHistoryQueryService
{
    public function __construct(
        private readonly RateHistoryQueryRepositoryInterface $queryRepo,
    ) {}

    /**
     * @return array{data: list<\Maatify\ExchangeRates\Admin\RateHistory\DTO\RateHistoryListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function listByRateId(int $rateId, int $page = 1, int $perPage = 20): array
    {
        return $this->queryRepo->listByRateId($rateId, $page, $perPage);
    }

    /**
     * @return array{data: list<\Maatify\ExchangeRates\Admin\RateHistory\DTO\RateHistoryListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function listByPair(
        string  $baseCurrencyCode,
        string  $targetCurrencyCode,
        int     $page       = 1,
        int     $perPage    = 30,
        ?int    $providerId = null,
    ): array {
        return $this->queryRepo->listByPair(
            $baseCurrencyCode,
            $targetCurrencyCode,
            $page,
            $perPage,
            $providerId
        );
    }

    /**
     * Return the rate at a specific moment in time.
     *
     * @param  string  $atDatetime  Format: 'Y-m-d H:i:s'
     */
    public function findRateAt(
        string  $baseCurrencyCode,
        string  $targetCurrencyCode,
        string  $atDatetime,
        ?int    $providerId = null,
    ): ?string {
        // Validate datetime format before hitting DB
        try {
            $dt = new \DateTimeImmutable($atDatetime);
            if ($dt->format('Y-m-d H:i:s') !== $atDatetime) {
                throw ExchangeRatesInvalidArgumentException::invalidDatetime('at_datetime', $atDatetime);
            }
        } catch (ExchangeRatesInvalidArgumentException $e) {
            throw $e;
        } catch (\Exception) {
            throw ExchangeRatesInvalidArgumentException::invalidDatetime('at_datetime', $atDatetime);
        }

        return $this->queryRepo->findRateAt(
            $baseCurrencyCode,
            $targetCurrencyCode,
            $atDatetime,
            $providerId
        );
    }
}
