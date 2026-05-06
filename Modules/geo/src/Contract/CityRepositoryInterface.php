<?php

declare(strict_types=1);

namespace Maatify\Geo\Contract;

use Maatify\Geo\Command\CreateCityCommand;
use Maatify\Geo\Command\UpdateCityCommand;
use Maatify\Geo\Command\UpdateCityStatusCommand;
use Maatify\Geo\DTO\CityDTO;

/**
 * All query and command operations scoped to the `geo_cities` table.
 *
 * language_id = null  → No translation JOIN. DTO::$translatedName is null.
 * language_id = int   → LEFT JOIN + COALESCE. translatedName is always non-null.
 */
interface CityRepositoryInterface
{
    // ================================================================== //
    //  Queries
    // ================================================================== //

    /**
     * Paginated, searchable, filterable admin list.
     *
     * @param  array<string, int|string> $columnFilters  Allowed: is_active, code, country_id, name, id
     * @return array{
     *     data:       list<CityDTO>,
     *     pagination: array{page: int, per_page: int, total: int, filtered: int}
     * }
     */
    public function listCities(
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
        ?int    $languageId = null,
    ): array;

    public function findCityById(int $id, ?int $languageId = null): ?CityDTO;

    /** Returns an existing city row matching name (case-insensitive) within a country. */
    public function findCityByNameAndCountryId(string $name, int $countryId): ?CityDTO;

    /** Returns the highest current display_order value within the given country (0 when empty). */
    public function maxCityDisplayOrder(int $countryId): int;

    // ================================================================== //
    //  Commands
    // ================================================================== //

    public function createCity(CreateCityCommand $command): CityDTO;

    public function updateCity(UpdateCityCommand $command): CityDTO;

    public function updateCityStatus(UpdateCityStatusCommand $command): CityDTO;

    /** Re-sorts all affected rows atomically within the city's country scope. */
    public function reorderCity(int $id, int $newOrder): void;
}

