<?php

declare(strict_types=1);

namespace Maatify\Geo\Contract;

use Maatify\Geo\Command\CreateCityCommand;
use Maatify\Geo\Command\CreateCountryCommand;
use Maatify\Geo\Command\DeleteCityTranslationCommand;
use Maatify\Geo\Command\DeleteCountryTranslationCommand;
use Maatify\Geo\Command\UpdateCityCommand;
use Maatify\Geo\Command\UpdateCityStatusCommand;
use Maatify\Geo\Command\UpdateCountryCommand;
use Maatify\Geo\Command\UpdateCountryStatusCommand;
use Maatify\Geo\Command\UpsertCityTranslationCommand;
use Maatify\Geo\Command\UpsertCountryTranslationCommand;
use Maatify\Geo\DTO\CityDTO;
use Maatify\Geo\DTO\CityTranslationDTO;
use Maatify\Geo\DTO\CountryDTO;
use Maatify\Geo\DTO\CountryTranslationDTO;

/**
 * Write side — all mutations on countries, cities, and their translations.
 * Each mutating method returns the freshly persisted DTO — no second round-trip needed.
 */
interface GeoCommandRepositoryInterface
{
    // ================================================================== //
    //  Country CRUD
    // ================================================================== //

    public function createCountry(CreateCountryCommand $command): CountryDTO;

    public function updateCountry(UpdateCountryCommand $command): CountryDTO;

    public function updateCountryStatus(UpdateCountryStatusCommand $command): CountryDTO;

    /** Standalone position change — re-sorts all affected rows atomically. */
    public function reorderCountry(int $id, int $newOrder): void;

    // ================================================================== //
    //  Country translations
    // ================================================================== //

    public function upsertCountryTranslation(UpsertCountryTranslationCommand $command): CountryTranslationDTO;

    /** Silent no-op if the row does not exist. */
    public function deleteCountryTranslation(DeleteCountryTranslationCommand $command): void;

    // ================================================================== //
    //  City CRUD
    // ================================================================== //

    public function createCity(CreateCityCommand $command): CityDTO;

    public function updateCity(UpdateCityCommand $command): CityDTO;

    public function updateCityStatus(UpdateCityStatusCommand $command): CityDTO;

    /** Scoped within the city's country. */
    public function reorderCity(int $id, int $newOrder): void;

    // ================================================================== //
    //  City translations
    // ================================================================== //

    public function upsertCityTranslation(UpsertCityTranslationCommand $command): CityTranslationDTO;

    /** Silent no-op if the row does not exist. */
    public function deleteCityTranslation(DeleteCityTranslationCommand $command): void;
}
