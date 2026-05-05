<?php

declare(strict_types=1);

namespace Maatify\Geo\Infrastructure\Repository;

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
use Maatify\Geo\Exception\GeoExceptionInterface;
use Maatify\Geo\Exception\GeoInvalidArgumentException;
use Maatify\Geo\Exception\GeoPersistenceException;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use PDO;
use PDOStatement;
use Throwable;

final readonly class PdoGeoCommandRepository implements GeoCommandRepositoryInterface
{
    public function __construct(
        private PDO                      $pdo,
        private GeoQueryReaderInterface  $queryReader,
        private ScopedOrderingManager    $orderingManager = new ScopedOrderingManager(),
    ) {}

    // ================================================================== //
    //  Country — CREATE
    // ================================================================== //

    public function createCountry(CreateCountryCommand $command): CountryDTO
    {
        $displayOrder = $this->orderingManager->getNextPosition(
            $this->pdo,
            $this->countryOrderingConfig(),
            null,
        );

        $stmt = $this->prepareOrFail(
            'INSERT INTO `geo_countries` (`code`, `name`, `icon`, `is_active`, `display_order`)
             VALUES (:code, :name, :icon, :is_active, :display_order)',
        );

        try {
            $stmt->execute([
                ':code'          => strtoupper($command->code),
                ':name'          => $command->name,
                ':icon'          => $command->icon,
                ':is_active'     => $command->isActive ? 1 : 0,
                ':display_order' => $displayOrder,
            ]);
        } catch (\PDOException $e) {
            if ($this->isDuplicateKeyError($e)) {
                throw CountryCodeAlreadyExistsException::withCode($command->code);
            }
            throw GeoPersistenceException::fromPdoException($e);
        }

        return $this->fetchCountryOrFail((int) $this->pdo->lastInsertId());
    }

    // ================================================================== //
    //  Country — UPDATE
    // ================================================================== //

    public function updateCountry(UpdateCountryCommand $command): CountryDTO
    {
        try {
            $stmt = $this->prepareOrFail(
                'UPDATE `geo_countries`
                 SET `code` = :code, `name` = :name, `icon` = :icon, `is_active` = :is_active
                 WHERE `id` = :id',
            );
            $stmt->execute([
                ':code'      => strtoupper($command->code),
                ':name'      => $command->name,
                ':icon'      => $command->icon,
                ':is_active' => $command->isActive ? 1 : 0,
                ':id'        => $command->id,
            ]);
        } catch (\PDOException $e) {
            if ($this->isDuplicateKeyError($e)) {
                throw CountryCodeAlreadyExistsException::withCode($command->code);
            }
            throw GeoPersistenceException::fromPdoException($e);
        } catch (Throwable $e) {
            if ($e instanceof GeoExceptionInterface) { throw $e; }
            throw GeoPersistenceException::fromThrowable($e);
        }

        return $this->fetchCountryOrFail($command->id);
    }

    // ================================================================== //
    //  Country — UPDATE STATUS
    // ================================================================== //

    public function updateCountryStatus(UpdateCountryStatusCommand $command): CountryDTO
    {
        $stmt = $this->prepareOrFail(
            'UPDATE `geo_countries` SET `is_active` = :is_active WHERE `id` = :id',
        );
        $stmt->execute([':is_active' => $command->isActive ? 1 : 0, ':id' => $command->id]);

        return $this->fetchCountryOrFail($command->id);
    }

    // ================================================================== //
    //  Country — REORDER
    // ================================================================== //

    public function reorderCountry(int $id, int $newOrder): void
    {
        try {
            $moved = $this->orderingManager->moveWithinScope(
                $this->pdo,
                $this->countryOrderingConfig(),
                null,
                $id,
                $newOrder,
            );

            if (!$moved) { throw CountryNotFoundException::withId($id); }
        } catch (Throwable $e) {
            if ($e instanceof GeoExceptionInterface) { throw $e; }
            throw GeoPersistenceException::fromThrowable($e);
        }
    }

    // ================================================================== //
    //  Country translations
    // ================================================================== //

    public function upsertCountryTranslation(UpsertCountryTranslationCommand $command): CountryTranslationDTO
    {
        $stmt = $this->prepareOrFail(
            'INSERT INTO `geo_country_translations` (`country_id`, `language_id`, `name`)
             VALUES (:country_id, :language_id, :insert_name)
             ON DUPLICATE KEY UPDATE `name` = :update_name',
        );

        try {
            $stmt->execute([
                ':country_id'   => $command->countryId,
                ':language_id'  => $command->languageId,
                ':insert_name'  => $command->translatedName,
                ':update_name'  => $command->translatedName,
            ]);
        } catch (\PDOException $e) {
            if ($this->isForeignKeyViolation($e)) {
                throw GeoInvalidArgumentException::invalidLanguageId($command->languageId);
            }
            throw GeoPersistenceException::fromPdoException($e);
        }

        $dto = $this->queryReader->findCountryTranslation($command->countryId, $command->languageId);
        if ($dto === null) {
            throw GeoPersistenceException::fromThrowable(
                new \RuntimeException("Country translation ({$command->countryId}/{$command->languageId}) not found after upsert.")
            );
        }

        return $dto;
    }

    public function deleteCountryTranslation(DeleteCountryTranslationCommand $command): void
    {
        $stmt = $this->prepareOrFail(
            'DELETE FROM `geo_country_translations` WHERE `country_id` = ? AND `language_id` = ?',
        );
        $stmt->execute([$command->countryId, $command->languageId]);
    }

    // ================================================================== //
    //  City — CREATE
    // ================================================================== //

    public function createCity(CreateCityCommand $command): CityDTO
    {
        $displayOrder = $this->orderingManager->getNextPosition(
            $this->pdo,
            $this->cityOrderingConfig($command->countryId),
            $command->countryId,
        );

        $stmt = $this->prepareOrFail(
            'INSERT INTO `geo_cities` (`country_id`, `code`, `name`, `is_active`, `display_order`)
             VALUES (:country_id, :code, :name, :is_active, :display_order)',
        );

        try {
            $stmt->execute([
                ':country_id'    => $command->countryId,
                ':code'          => $command->code,
                ':name'          => $command->name,
                ':is_active'     => $command->isActive ? 1 : 0,
                ':display_order' => $displayOrder,
            ]);
        } catch (\PDOException $e) {
            if ($this->isForeignKeyViolation($e)) {
                throw CountryNotFoundException::withId($command->countryId);
            }
            throw GeoPersistenceException::fromPdoException($e);
        }

        return $this->fetchCityOrFail((int) $this->pdo->lastInsertId());
    }

    // ================================================================== //
    //  City — UPDATE
    // ================================================================== //

    public function updateCity(UpdateCityCommand $command): CityDTO
    {
        try {
            $stmt = $this->prepareOrFail(
                'UPDATE `geo_cities`
                 SET `code` = :code, `name` = :name, `is_active` = :is_active
                 WHERE `id` = :id',
            );
            $stmt->execute([
                ':code'      => $command->code,
                ':name'      => $command->name,
                ':is_active' => $command->isActive ? 1 : 0,
                ':id'        => $command->id,
            ]);
        } catch (Throwable $e) {
            if ($e instanceof GeoExceptionInterface) { throw $e; }
            throw GeoPersistenceException::fromThrowable($e);
        }

        return $this->fetchCityOrFail($command->id);
    }

    // ================================================================== //
    //  City — UPDATE STATUS
    // ================================================================== //

    public function updateCityStatus(UpdateCityStatusCommand $command): CityDTO
    {
        $stmt = $this->prepareOrFail(
            'UPDATE `geo_cities` SET `is_active` = :is_active WHERE `id` = :id',
        );
        $stmt->execute([':is_active' => $command->isActive ? 1 : 0, ':id' => $command->id]);

        return $this->fetchCityOrFail($command->id);
    }

    // ================================================================== //
    //  City — REORDER (scoped per country)
    // ================================================================== //

    public function reorderCity(int $id, int $newOrder): void
    {
        // Fetch the city to know its country_id for scoped ordering.
        $city = $this->queryReader->findCityById($id);
        if ($city === null) { throw CityNotFoundException::withId($id); }

        try {
            $moved = $this->orderingManager->moveWithinScope(
                $this->pdo,
                $this->cityOrderingConfig($city->countryId),
                $city->countryId,
                $id,
                $newOrder,
            );

            if (!$moved) { throw CityNotFoundException::withId($id); }
        } catch (Throwable $e) {
            if ($e instanceof GeoExceptionInterface) { throw $e; }
            throw GeoPersistenceException::fromThrowable($e);
        }
    }

    // ================================================================== //
    //  City translations
    // ================================================================== //

    public function upsertCityTranslation(UpsertCityTranslationCommand $command): CityTranslationDTO
    {
        $stmt = $this->prepareOrFail(
            'INSERT INTO `geo_city_translations` (`city_id`, `language_id`, `name`)
             VALUES (:city_id, :language_id, :insert_name)
             ON DUPLICATE KEY UPDATE `name` = :update_name',
        );

        try {
            $stmt->execute([
                ':city_id'      => $command->cityId,
                ':language_id'  => $command->languageId,
                ':insert_name'  => $command->translatedName,
                ':update_name'  => $command->translatedName,
            ]);
        } catch (\PDOException $e) {
            if ($this->isForeignKeyViolation($e)) {
                throw GeoInvalidArgumentException::invalidLanguageId($command->languageId);
            }
            throw GeoPersistenceException::fromPdoException($e);
        }

        $dto = $this->queryReader->findCityTranslation($command->cityId, $command->languageId);
        if ($dto === null) {
            throw GeoPersistenceException::fromThrowable(
                new \RuntimeException("City translation ({$command->cityId}/{$command->languageId}) not found after upsert.")
            );
        }

        return $dto;
    }

    public function deleteCityTranslation(DeleteCityTranslationCommand $command): void
    {
        $stmt = $this->prepareOrFail(
            'DELETE FROM `geo_city_translations` WHERE `city_id` = ? AND `language_id` = ?',
        );
        $stmt->execute([$command->cityId, $command->languageId]);
    }

    // ================================================================== //
    //  Private — ordering configs
    // ================================================================== //

    private function countryOrderingConfig(): ScopedOrderingConfig
    {
        return new ScopedOrderingConfig(
            table:          'geo_countries',
            scopeColumn:    null,
            idColumn:       'id',
            orderColumn:    'display_order',
            deletedAtColumn: null,
        );
    }

    private function cityOrderingConfig(int $countryId): ScopedOrderingConfig
    {
        return new ScopedOrderingConfig(
            table:          'geo_cities',
            scopeColumn:    'country_id',
            idColumn:       'id',
            orderColumn:    'display_order',
            deletedAtColumn: null,
        );
    }

    // ================================================================== //
    //  Private — DB helpers
    // ================================================================== //

    private function fetchCountryOrFail(int $id): CountryDTO
    {
        $stmt = $this->prepareOrFail('SELECT * FROM `geo_countries` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || !is_array($row)) {
            throw CountryNotFoundException::withId($id);
        }

        /** @var array<string, mixed> $row */
        return CountryDTO::fromRow($row);
    }

    private function fetchCityOrFail(int $id): CityDTO
    {
        $stmt = $this->prepareOrFail('SELECT * FROM `geo_cities` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || !is_array($row)) {
            throw CityNotFoundException::withId($id);
        }

        /** @var array<string, mixed> $row */
        return CityDTO::fromRow($row);
    }

    private function prepareOrFail(string $sql): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw GeoPersistenceException::prepareFailed($sql);
        }

        return $stmt;
    }

    private function isDuplicateKeyError(\PDOException $e): bool
    {
        return $e->getCode() === '23000' && str_contains($e->getMessage(), '1062');
    }

    private function isForeignKeyViolation(\PDOException $e): bool
    {
        return $e->getCode() === '23000' && str_contains($e->getMessage(), '1452');
    }
}
