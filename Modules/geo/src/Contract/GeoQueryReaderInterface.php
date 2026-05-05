<?php

declare(strict_types=1);

namespace Maatify\Geo\Contract;

use Maatify\Geo\DTO\CityDTO;
use Maatify\Geo\DTO\CityTranslationDTO;
use Maatify\Geo\DTO\CountryDTO;
use Maatify\Geo\DTO\CountryTranslationDTO;

/**
 * Read side — all queries on countries, cities, and their translations.
 *
 * language_id = null  → No translation JOIN. DTO::$translatedName is null.
 * language_id = int   → LEFT JOIN + COALESCE. translatedName is always non-null.
 *
 * This module does NOT know about the `languages` table.
 * language_id is a plain INT. The admin execution layer joins language
 * metadata independently.
 */
interface GeoQueryReaderInterface
{
    // ================================================================== //
    //  Countries — admin list (paginated, searchable, filterable)
    // ================================================================== //

    /**
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

    // ================================================================== //
    //  Countries — public list (active only, no pagination)
    // ================================================================== //

    /** @return list<CountryDTO> */
    public function listActiveCountries(?int $languageId = null): array;

    // ================================================================== //
    //  Countries — single-record lookups
    // ================================================================== //

    public function findCountryById(int $id, ?int $languageId = null): ?CountryDTO;

    public function findCountryByCode(string $code, ?int $languageId = null): ?CountryDTO;

    // ================================================================== //
    //  Country translations — admin
    // ================================================================== //

    public function findCountryTranslation(int $countryId, int $languageId): ?CountryTranslationDTO;

    /**
     * Paginated list of existing translation rows for a country.
     * No JOIN to languages table — language_id is a plain INT.
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
    //  Cities — admin list (paginated, searchable, filterable)
    // ================================================================== //

    /**
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

    // ================================================================== //
    //  Cities — public list (active only, by country)
    // ================================================================== //

    /** @return list<CityDTO> */
    public function listActiveCitiesByCountryId(int $countryId, ?int $languageId = null): array;

    /** @return list<CityDTO> */
    public function listActiveCitiesByCountryCode(string $countryCode, ?int $languageId = null): array;

    // ================================================================== //
    //  Cities — single-record lookup
    // ================================================================== //

    public function findCityById(int $id, ?int $languageId = null): ?CityDTO;

    // ================================================================== //
    //  City translations — admin
    // ================================================================== //

    public function findCityTranslation(int $cityId, int $languageId): ?CityTranslationDTO;

    /**
     * Paginated list of existing translation rows for a city.
     * No JOIN to languages table — language_id is a plain INT.
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
    //  Aggregates — needed by the write side
    // ================================================================== //

    public function maxCountryDisplayOrder(): int;

    public function maxCityDisplayOrder(int $countryId): int;
}
