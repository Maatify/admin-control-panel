<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Provider\Contract;

use Maatify\ExchangeRates\Admin\Provider\DTO\ProviderDTO;

interface ProviderQueryRepositoryInterface
{
    /**
     * Find a provider by id. Includes soft-deleted rows.
     * Returns null only when the provider id does not exist.
     * Callers must check $dto->deletedAt to determine deletion status.
     */
    public function findById(int $id): ?ProviderDTO;

    /**
     * Find a provider by code. Includes inactive providers, excludes soft-deleted rows.
     * Returns null only when no non-deleted provider with that code exists.
     */
    public function findByCode(string $code): ?ProviderDTO;

    /**
     * Paginated list with optional global search and column filters.
     *
     * @param  array<string, string|int>  $columnFilters
     * @return array{data: list<\Maatify\ExchangeRates\Admin\Provider\DTO\ProviderListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function list(
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
    ): array;
}
