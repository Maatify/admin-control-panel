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
use Maatify\Geo\Contract\GeoCommandRepositoryInterface;
use Maatify\Geo\Contract\GeoQueryReaderInterface;
use Maatify\Geo\DTO\CityDTO;
use Maatify\Geo\DTO\CityTranslationDTO;
use Maatify\Geo\DTO\CountryDTO;
use Maatify\Geo\DTO\CountryTranslationDTO;
use Maatify\Geo\Exception\CityNotFoundException;
use Maatify\Geo\Exception\CountryCodeAlreadyExistsException;
use Maatify\Geo\Exception\CountryNotFoundException;

/**
 * Write-side service — enforces all business rules before delegating
 * to the command repository.
 */
final class GeoCommandService
{
    public function __construct(
        private readonly GeoCommandRepositoryInterface $commandRepo,
        private readonly GeoQueryReaderInterface       $queryReader,
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

        return $this->commandRepo->createCountry(new CreateCountryCommand(
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

        return $this->commandRepo->updateCountry(new UpdateCountryCommand(
            id:       $command->id,
            code:     $code,
            name:     $command->name,
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

        return $this->commandRepo->updateCountryStatus($command);
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
        $this->commandRepo->reorderCountry($id, $newOrder);
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

        return $this->commandRepo->upsertCountryTranslation($command);
    }

    /**
     * @throws CountryNotFoundException
     */
    public function deleteCountryTranslation(DeleteCountryTranslationCommand $command): void
    {
        $this->assertCountryExists($command->countryId);

        $this->commandRepo->deleteCountryTranslation($command);
    }

    // ================================================================== //
    //  City CRUD
    // ================================================================== //

    /**
     * @throws CountryNotFoundException  when the country does not exist
     */
    public function createCity(CreateCityCommand $command): CityDTO
    {
        $this->assertCountryExists($command->countryId);

        return $this->commandRepo->createCity($command);
    }

    /**
     * @throws CityNotFoundException
     */
    public function updateCity(UpdateCityCommand $command): CityDTO
    {
        $this->assertCityExists($command->id);

        return $this->commandRepo->updateCity($command);
    }

    /**
     * @throws CityNotFoundException
     */
    public function updateCityStatus(UpdateCityStatusCommand $command): CityDTO
    {
        $this->assertCityExists($command->id);

        return $this->commandRepo->updateCityStatus($command);
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
        $this->commandRepo->reorderCity($id, $newOrder);
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

        return $this->commandRepo->upsertCityTranslation($command);
    }

    /**
     * @throws CityNotFoundException
     */
    public function deleteCityTranslation(DeleteCityTranslationCommand $command): void
    {
        $this->assertCityExists($command->cityId);

        $this->commandRepo->deleteCityTranslation($command);
    }

    // ================================================================== //
    //  Private guards
    // ================================================================== //

    /**
     * @throws CountryNotFoundException
     */
    private function assertCountryExists(int $id): void
    {
        if ($this->queryReader->findCountryById($id) === null) {
            throw CountryNotFoundException::withId($id);
        }
    }

    /**
     * @throws CityNotFoundException
     */
    private function assertCityExists(int $id): void
    {
        if ($this->queryReader->findCityById($id) === null) {
            throw CityNotFoundException::withId($id);
        }
    }

    /**
     * @throws CountryCodeAlreadyExistsException
     */
    private function assertCountryCodeIsUnique(string $code, ?int $excludeId): void
    {
        $existing = $this->queryReader->findCountryByCode($code);

        if ($existing === null) { return; }

        // Allow updating a row with its own existing code
        if ($excludeId !== null && $existing->id === $excludeId) { return; }

        throw CountryCodeAlreadyExistsException::withCode($code);
    }
}
