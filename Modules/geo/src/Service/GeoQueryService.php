<?php

declare(strict_types=1);

namespace Maatify\Geo\Service;

use Maatify\Geo\Contract\GeoQueryReaderInterface;
use Maatify\Geo\DTO\CityDTO;
use Maatify\Geo\DTO\CityTranslationDTO;
use Maatify\Geo\DTO\CountryDTO;
use Maatify\Geo\DTO\CountryTranslationDTO;
use Maatify\Geo\Exception\CityNotFoundException;
use Maatify\Geo\Exception\CountryNotFoundException;

/**
 * Read-side service.
 *
 * All public methods accept an optional $languageId:
 *   null → base name only, translatedName is null in the DTO.
 *   int  → COALESCE join: translatedName is always a non-null string.
 *
 * ── Module independence ────────────────────────────────────────────────
 * This service never touches the `languages` table directly.
 * The admin execution layer supplies language_id values and
 * joins language metadata onto the results independently.
 */
final class GeoQueryService
{
    public function __construct(
        private readonly GeoQueryReaderInterface $reader,
    ) {}

    // ================================================================== //
    //  Countries — admin
    // ================================================================== //

    /**
     * @param  array<string, int|string> $columnFilters
     * @return array{
     *     data:       list<CountryDTO>,
     *     pagination: array{page: int, per_page: int, total: int, filtered: int}
     * }
     */
    public function paginateCountries(
        int     $page          = 1,
        int     $perPage       = 20,
        ?string $globalSearch  = null,
        array   $columnFilters = [],
        ?int    $languageId    = null,
    ): array {
        return $this->reader->listCountries($page, $perPage, $globalSearch, $columnFilters, $languageId);
    }

    // ================================================================== //
    //  Countries — public (active list)
    // ================================================================== //

    /**
     * Returns all active countries, ordered by display_order.
     *
     * @return list<CountryDTO>
     */
    public function activeCountries(?int $languageId = null): array
    {
        return $this->reader->listActiveCountries($languageId);
    }

    // ================================================================== //
    //  Countries — single-record
    // ================================================================== //

    /**
     * @throws CountryNotFoundException
     */
    public function getCountryById(int $id, ?int $languageId = null): CountryDTO
    {
        $dto = $this->reader->findCountryById($id, $languageId);
        if ($dto === null) {
            throw CountryNotFoundException::withId($id);
        }

        return $dto;
    }

    /**
     * @throws CountryNotFoundException
     */
    public function getCountryByCode(string $code, ?int $languageId = null): CountryDTO
    {
        $dto = $this->reader->findCountryByCode($code, $languageId);
        if ($dto === null) {
            throw CountryNotFoundException::withCode($code);
        }

        return $dto;
    }

    // ================================================================== //
    //  Country translations — admin
    // ================================================================== //

    public function findCountryTranslation(int $countryId, int $languageId): ?CountryTranslationDTO
    {
        return $this->reader->findCountryTranslation($countryId, $languageId);
    }

    /**
     * @param  array<string, int|string> $columnFilters
     * @return array{
     *     data:       list<CountryTranslationDTO>,
     *     pagination: array{page: int, per_page: int, total: int, filtered: int}
     * }
     */
    public function listCountryTranslationsPaginated(
        int     $countryId,
        int     $page          = 1,
        int     $perPage       = 20,
        ?string $globalSearch  = null,
        array   $columnFilters = [],
    ): array {
        return $this->reader->listTranslationsForCountryPaginated(
            $countryId, $page, $perPage, $globalSearch, $columnFilters,
        );
    }

    // ================================================================== //
    //  Cities — admin
    // ================================================================== //

    /**
     * @param  array<string, int|string> $columnFilters
     * @return array{
     *     data:       list<CityDTO>,
     *     pagination: array{page: int, per_page: int, total: int, filtered: int}
     * }
     */
    public function paginateCities(
        int     $page          = 1,
        int     $perPage       = 20,
        ?string $globalSearch  = null,
        array   $columnFilters = [],
        ?int    $languageId    = null,
    ): array {
        return $this->reader->listCities($page, $perPage, $globalSearch, $columnFilters, $languageId);
    }

    // ================================================================== //
    //  Cities — public (active list by country)
    // ================================================================== //

    /**
     * Returns active cities for a country identified by its numeric id.
     *
     * @return list<CityDTO>
     */
    public function activeCitiesByCountryId(int $countryId, ?int $languageId = null): array
    {
        return $this->reader->listActiveCitiesByCountryId($countryId, $languageId);
    }

    /**
     * Returns active cities for a country identified by its ISO alpha-2 code.
     *
     * @return list<CityDTO>
     */
    public function activeCitiesByCountryCode(string $countryCode, ?int $languageId = null): array
    {
        return $this->reader->listActiveCitiesByCountryCode($countryCode, $languageId);
    }

    // ================================================================== //
    //  Cities — single-record
    // ================================================================== //

    /**
     * @throws CityNotFoundException
     */
    public function getCityById(int $id, ?int $languageId = null): CityDTO
    {
        $dto = $this->reader->findCityById($id, $languageId);
        if ($dto === null) {
            throw CityNotFoundException::withId($id);
        }

        return $dto;
    }

    // ================================================================== //
    //  City translations — admin
    // ================================================================== //

    public function findCityTranslation(int $cityId, int $languageId): ?CityTranslationDTO
    {
        return $this->reader->findCityTranslation($cityId, $languageId);
    }

    /**
     * @param  array<string, int|string> $columnFilters
     * @return array{
     *     data:       list<CityTranslationDTO>,
     *     pagination: array{page: int, per_page: int, total: int, filtered: int}
     * }
     */
    public function listCityTranslationsPaginated(
        int     $cityId,
        int     $page          = 1,
        int     $perPage       = 20,
        ?string $globalSearch  = null,
        array   $columnFilters = [],
    ): array {
        return $this->reader->listTranslationsForCityPaginated(
            $cityId, $page, $perPage, $globalSearch, $columnFilters,
        );
    }
}
