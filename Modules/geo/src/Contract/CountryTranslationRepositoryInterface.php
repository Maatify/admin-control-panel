<?php

declare(strict_types=1);

namespace Maatify\Geo\Contract;

use Maatify\Geo\Command\DeleteCountryTranslationCommand;
use Maatify\Geo\Command\UpsertCountryTranslationCommand;
use Maatify\Geo\DTO\CountryTranslationDTO;

/**
 * All query and command operations scoped to the `geo_country_translations` table.
 *
 * This module does NOT join the `languages` table.
 * language_id is a plain INT — the admin execution layer joins language
 * metadata independently.
 */
interface CountryTranslationRepositoryInterface
{
    // ================================================================== //
    //  Queries
    // ================================================================== //

    public function findCountryTranslation(int $countryId, int $languageId): ?CountryTranslationDTO;

    /**
     * Paginated list of existing translation rows for a country.
     *
     * @param  array<string, int|string> $columnFilters  Allowed: language_id, name
     * @return array{
     *     data:       list<CountryTranslationDTO>,
     *     pagination: array{page: int, per_page: int, total: int, filtered: int}
     * }
     */
    public function listTranslationsForCountryPaginated(
        int     $countryId,
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
    ): array;

    // ================================================================== //
    //  Commands
    // ================================================================== //

    /** Creates or updates the translation row; returns the persisted DTO. */
    public function upsertCountryTranslation(UpsertCountryTranslationCommand $command): CountryTranslationDTO;

    /** Silent no-op if the row does not exist. */
    public function deleteCountryTranslation(DeleteCountryTranslationCommand $command): void;
}

