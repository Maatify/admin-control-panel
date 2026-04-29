<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Rate\Contract;

use Maatify\ExchangeRates\Admin\Rate\DTO\RateDTO;

interface RateQueryRepositoryInterface
{
    /**
     * Find a rate by id. Returns null if not found (active or deleted).
     */
    public function findById(int $id): ?RateDTO;

    /**
     * Paginated list with optional global search and column filters.
     *
     * @param  array<string, string|int>  $columnFilters
     * @return array{data: list<\Maatify\ExchangeRates\Admin\Rate\DTO\RateListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function list(
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
    ): array;

    /**
     * Find the raw rate row by id. Used internally by command repo for display_order scope.
     *
     * @return array<string, mixed>|null
     */
    public function findRawById(int $id): ?array;
}
