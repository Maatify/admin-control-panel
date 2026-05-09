<?php

declare(strict_types=1);

namespace Maatify\Geo\Service;

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
use Maatify\Geo\Contract\CityRepositoryInterface;
use Maatify\Geo\Contract\CityTranslationRepositoryInterface;
use Maatify\Geo\Contract\CountryRepositoryInterface;
use Maatify\Geo\Contract\CountryTranslationRepositoryInterface;
use Maatify\Geo\DTO\CityDTO;
use Maatify\Geo\DTO\CityTranslationDTO;
use Maatify\Geo\DTO\CountryDTO;
use Maatify\Geo\DTO\CountryTranslationDTO;
use Maatify\Geo\Exception\CityAlreadyExistsException;
use Maatify\Geo\Exception\CityNotFoundException;
use Maatify\Geo\Exception\CountryCodeAlreadyExistsException;
use Maatify\Geo\Exception\CountryNotFoundException;

/**
 * Write-side service — enforces all business rules before delegating
 * to the focused entity repositories.
 */
final class GeoCommandService
{
    public function __construct(
        private readonly CountryRepositoryInterface            $countryRepo,
        private readonly CountryTranslationRepositoryInterface $countryTranslationRepo,
        private readonly CityRepositoryInterface               $cityRepo,
        private readonly CityTranslationRepositoryInterface    $cityTranslationRepo,
    ) {}

    // ================================================================== //
    //  Country CRUD
    // ================================================================== //

    /**
     * @throws CountryCodeAlreadyExistsException
     */
    public function createCountry(CreateCountryCommand $command): CountryDTO
    {
        $code = strtoupper($command->code);
        $this->assertCountryCodeIsUnique($code, excludeId: null);

        return $this->countryRepo->createCountry(new CreateCountryCommand(
            code:     $code,
            name:     $command->name,
            icon:     $command->icon,
            isActive: $command->isActive,
        ));
    }

    /**
     * @throws CountryNotFoundException
     * @throws CountryCodeAlreadyExistsException
     */
    public function updateCountry(UpdateCountryCommand $command): CountryDTO
    {
        $this->assertCountryExists($command->id);

        $code = strtoupper($command->code);
        $this->assertCountryCodeIsUnique($code, excludeId: $command->id);

        return $this->countryRepo->updateCountry(new UpdateCountryCommand(
            id:       $command->id,
            code:     $code,
            name:     $command->name,
            currency: $command->currency,
            icon:     $command->icon,
            isActive: $command->isActive,
        ));
    }

    /**
     * @throws CountryNotFoundException
     */
    public function updateCountryStatus(UpdateCountryStatusCommand $command): CountryDTO
    {
        $this->assertCountryExists($command->id);

        return $this->countryRepo->updateCountryStatus($command);
    }

    /**
     * @throws CountryNotFoundException
     * @throws \InvalidArgumentException  when $newOrder < 1
     */
    public function reorderCountry(int $id, int $newOrder): void
    {
        if ($newOrder < 1) {
            throw new \InvalidArgumentException(
                sprintf('display_order must be >= 1, got %d.', $newOrder),
            );
        }

        $this->assertCountryExists($id);
        $this->countryRepo->reorderCountry($id, $newOrder);
    }

    // ================================================================== //
    //  Country translations
    // ================================================================== //

    /**
     * @throws CountryNotFoundException
     */
    public function upsertCountryTranslation(UpsertCountryTranslationCommand $command): CountryTranslationDTO
    {
        $this->assertCountryExists($command->countryId);

        return $this->countryTranslationRepo->upsertCountryTranslation($command);
    }

    /**
     * @throws CountryNotFoundException
     */
    public function deleteCountryTranslation(DeleteCountryTranslationCommand $command): void
    {
        $this->assertCountryExists($command->countryId);

        $this->countryTranslationRepo->deleteCountryTranslation($command);
    }

    // ================================================================== //
    //  City CRUD
    // ================================================================== //

    /**
     * @throws CountryNotFoundException  when the country does not exist
     * @throws CityAlreadyExistsException when a city with the same name exists in that country
     */
    public function createCity(CreateCityCommand $command): CityDTO
    {
        $this->assertCountryExists($command->countryId);
        $this->assertCityNameIsUnique($command->name, $command->countryId, excludeId: null);

        return $this->cityRepo->createCity($command);
    }

    /**
     * @throws CityNotFoundException
     * @throws CityAlreadyExistsException when the new name conflicts with another city in the same country
     */
    public function updateCity(UpdateCityCommand $command): CityDTO
    {
        $this->assertCityExists($command->id);

        // Fetch current city to get its country_id for name-uniqueness check
        $current = $this->cityRepo->findCityById($command->id);
        if ($current !== null) {
            $this->assertCityNameIsUnique($command->name, $current->countryId, excludeId: $command->id);
        }

        return $this->cityRepo->updateCity($command);
    }

    /**
     * @throws CityNotFoundException
     */
    public function updateCityStatus(UpdateCityStatusCommand $command): CityDTO
    {
        $this->assertCityExists($command->id);

        return $this->cityRepo->updateCityStatus($command);
    }

    /**
     * @throws CityNotFoundException
     * @throws \InvalidArgumentException  when $newOrder < 1
     */
    public function reorderCity(int $id, int $newOrder): void
    {
        if ($newOrder < 1) {
            throw new \InvalidArgumentException(
                sprintf('display_order must be >= 1, got %d.', $newOrder),
            );
        }

        $this->assertCityExists($id);
        $this->cityRepo->reorderCity($id, $newOrder);
    }

    // ================================================================== //
    //  City translations
    // ================================================================== //

    /**
     * @throws CityNotFoundException
     */
    public function upsertCityTranslation(UpsertCityTranslationCommand $command): CityTranslationDTO
    {
        $this->assertCityExists($command->cityId);

        return $this->cityTranslationRepo->upsertCityTranslation($command);
    }

    /**
     * @throws CityNotFoundException
     */
    public function deleteCityTranslation(DeleteCityTranslationCommand $command): void
    {
        $this->assertCityExists($command->cityId);

        $this->cityTranslationRepo->deleteCityTranslation($command);
    }

    // ================================================================== //
    //  Private guards
    // ================================================================== //

    /**
     * @throws CountryNotFoundException
     */
    private function assertCountryExists(int $id): void
    {
        if ($this->countryRepo->findCountryById($id) === null) {
            throw CountryNotFoundException::withId($id);
        }
    }

    /**
     * @throws CityNotFoundException
     */
    private function assertCityExists(int $id): void
    {
        if ($this->cityRepo->findCityById($id) === null) {
            throw CityNotFoundException::withId($id);
        }
    }

    /**
     * @throws CountryCodeAlreadyExistsException
     */
    private function assertCountryCodeIsUnique(string $code, ?int $excludeId): void
    {
        $existing = $this->countryRepo->findCountryByCode($code);

        if ($existing === null) { return; }

        // Allow updating a row with its own existing code
        if ($excludeId !== null && $existing->id === $excludeId) { return; }

        throw CountryCodeAlreadyExistsException::withCode($code);
    }

    /**
     * @throws CityAlreadyExistsException
     */
    private function assertCityNameIsUnique(string $name, int $countryId, ?int $excludeId): void
    {
        $existing = $this->cityRepo->findCityByNameAndCountryId($name, $countryId);

        if ($existing === null) { return; }

        // Allow updating a city with its own existing name
        if ($excludeId !== null && $existing->id === $excludeId) { return; }

        throw CityAlreadyExistsException::withNameAndCountryId($name, $countryId);
    }
}
