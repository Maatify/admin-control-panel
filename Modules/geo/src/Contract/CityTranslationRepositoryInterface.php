<?php

declare(strict_types=1);

namespace Maatify\Geo\Contract;

use Maatify\Geo\Command\DeleteCityTranslationCommand;
use Maatify\Geo\Command\UpsertCityTranslationCommand;
use Maatify\Geo\DTO\CityTranslationDTO;

/**
 * All query and command operations scoped to the `geo_city_translations` table.
 *
 * This module does NOT join the `languages` table.
 * language_id is a plain INT — the admin execution layer joins language
 * metadata independently.
 */
interface CityTranslationRepositoryInterface
{
    // ================================================================== //
    //  Queries
    // ================================================================== //

    public function findCityTranslation(int $cityId, int $languageId): ?CityTranslationDTO;

    /**
     * Paginated list of existing translation rows for a city.
     *
     * @param  array<string, int|string> $columnFilters  Allowed: language_id, name
     * @return array{
     *     data:       list<CityTranslationDTO>,
     *     pagination: array{page: int, per_page: int, total: int, filtered: int}
     * }
     */
    public function listTranslationsForCityPaginated(
        int     $cityId,
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
    ): array;

    // ================================================================== //
    //  Commands
    // ================================================================== //

    /** Creates or updates the translation row; returns the persisted DTO. */
    public function upsertCityTranslation(UpsertCityTranslationCommand $command): CityTranslationDTO;

    /** Silent no-op if the row does not exist. */
    public function deleteCityTranslation(DeleteCityTranslationCommand $command): void;
}

