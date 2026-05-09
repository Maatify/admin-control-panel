<?php

declare(strict_types=1);

namespace Maatify\Geo\Contract;

use Maatify\Geo\Command\CreateCountryCommand;
use Maatify\Geo\Command\UpdateCountryCommand;
use Maatify\Geo\Command\UpdateCountryStatusCommand;
use Maatify\Geo\DTO\CountryDTO;

/**
 * All query and command operations scoped to the `geo_countries` table.
 *
 * language_id = null  → No translation JOIN. DTO::$translatedName is null.
 * language_id = int   → LEFT JOIN + COALESCE. translatedName is always non-null.
 */
interface CountryRepositoryInterface
{
    // ================================================================== //
    //  Queries
    // ================================================================== //

    /**
     * Paginated, searchable, filterable admin list.
     *
     * @param  array<string, int|string> $columnFilters  Allowed: is_active, code, name, id
     * @return array{
     *     data:       list<CountryDTO>,
     *     pagination: array{page: int, per_page: int, total: int, filtered: int}
     * }
     */
    public function listCountries(
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
        ?int    $languageId = null,
    ): array;

    public function findCountryById(int $id, ?int $languageId = null): ?CountryDTO;

    public function findCountryByCode(string $code, ?int $languageId = null): ?CountryDTO;

    /** Returns the highest current display_order value (0 when table is empty). */
    public function maxCountryDisplayOrder(): int;

    // ================================================================== //
    //  Commands
    // ================================================================== //

    public function createCountry(CreateCountryCommand $command): CountryDTO;

    public function updateCountry(UpdateCountryCommand $command): CountryDTO;

    public function updateCountryStatus(UpdateCountryStatusCommand $command): CountryDTO;

    /** Re-sorts all affected rows atomically within the global scope. */
    public function reorderCountry(int $id, int $newOrder): void;
}

