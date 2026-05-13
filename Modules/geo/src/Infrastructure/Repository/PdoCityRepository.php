<?php

declare(strict_types=1);

namespace Maatify\Geo\Infrastructure\Repository;

use Maatify\Geo\Command\CreateCityCommand;
use Maatify\Geo\Command\UpdateCityCommand;
use Maatify\Geo\Command\UpdateCityStatusCommand;
use Maatify\Geo\Contract\CityDropdownRepositoryInterface;
use Maatify\Geo\Contract\CityRepositoryInterface;
use Maatify\Geo\DTO\CityDTO;
use Maatify\Geo\Exception\CityAlreadyExistsException;
use Maatify\Geo\Exception\CityNotFoundException;
use Maatify\Geo\Exception\CountryNotFoundException;
use Maatify\Geo\Exception\GeoExceptionInterface;
use Maatify\Geo\Exception\GeoPersistenceException;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use PDO;
use PDOStatement;
use Throwable;

/**
 * PDO implementation for all city read and write operations.
 *
 * Implements CityRepositoryInterface (admin list, lookup, CRUD, reorder)
 * and CityDropdownRepositoryInterface (active-only list for dropdowns).
 *
 * NEVER references the `languages` table.
 */
final class PdoCityRepository implements CityRepositoryInterface, CityDropdownRepositoryInterface
{
    public function __construct(
        private readonly PDO                   $pdo,
        private readonly ScopedOrderingManager $orderingManager = new ScopedOrderingManager(),
    ) {}

    // ================================================================== //
    //  CityRepositoryInterface — Queries
    // ================================================================== //

    /**
     * @param  array<string, int|string> $columnFilters
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
    ): array {
        $page   = max(1, $page);
        $limit  = max(1, min(200, $perPage));
        $offset = ($page - 1) * $limit;

        $where  = [];
        $params = [];

        if ($globalSearch !== null && trim($globalSearch) !== '') {
            $globalText = '%' . $this->escapeLike(trim($globalSearch)) . '%';

            $where[] = '(ci.`code` LIKE :global_text_code OR ci.`name` LIKE :global_text_name)';
            $params['global_text_code'] = $globalText;
            $params['global_text_name'] = $globalText;
        }
        if (isset($columnFilters['is_active'])) {
            $where[]             = 'ci.`is_active` = :is_active';
            $params['is_active'] = (int) $columnFilters['is_active'];
        }
        if (isset($columnFilters['country_id'])) {
            $where[]              = 'ci.`country_id` = :country_id';
            $params['country_id'] = (int) $columnFilters['country_id'];
        }
        if (isset($columnFilters['code'])) {
            $where[]        = 'ci.`code` = :code';
            $params['code'] = trim((string) $columnFilters['code']);
        }
        if (isset($columnFilters['name'])) {
            $where[]        = 'ci.`name` LIKE :name';
            $params['name'] = '%' . $this->escapeLike(trim((string) $columnFilters['name'])) . '%';
        }
        if (isset($columnFilters['id'])) {
            $where[]      = 'ci.`id` = :id';
            $params['id'] = (int) $columnFilters['id'];
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        [$selectExtra, $join, $joinParams] = $this->buildTranslationJoin($languageId);

        $total    = $this->scalarInt('SELECT COUNT(*) FROM `geo_cities`');
        $filtered = $this->scalarFiltered("SELECT COUNT(*) FROM `geo_cities` AS ci {$whereSql}", $params);

        $stmt = $this->prepareOrFail("
            SELECT ci.*, {$selectExtra}
            FROM   `geo_cities` AS ci
            {$join}
            {$whereSql}
            ORDER BY ci.`display_order` ASC, ci.`id` ASC
            LIMIT :limit OFFSET :offset
        ");

        $pos = 1;
        foreach ($joinParams as $v) { $stmt->bindValue($pos++, $v, PDO::PARAM_INT); }
        foreach ($params as $key => $value) { $stmt->bindValue(':' . $key, $value); }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<CityDTO> $data */
        $data = array_map(
            static fn (array $row): CityDTO => CityDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return $this->paginatedResult($data, $page, $limit, $total, $filtered);
    }

    public function findCityById(int $id, ?int $languageId = null): ?CityDTO
    {
        [$selectExtra, $join, $joinParams] = $this->buildTranslationJoin($languageId);

        $stmt = $this->prepareOrFail("
            SELECT ci.*, {$selectExtra}
            FROM   `geo_cities` AS ci
            {$join}
            WHERE  ci.`id` = ?
            LIMIT  1
        ");

        $pos = 1;
        foreach ($joinParams as $v) { $stmt->bindValue($pos++, $v, PDO::PARAM_INT); }
        $stmt->bindValue($pos, $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $this->fetchAssoc($stmt);
        return $row !== null ? CityDTO::fromRow($row) : null;
    }

    public function findCityByNameAndCountryId(string $name, int $countryId): ?CityDTO
    {
        $stmt = $this->prepareOrFail(
            'SELECT * FROM `geo_cities`
             WHERE `country_id` = ? AND LOWER(`name`) = LOWER(?)
             LIMIT 1',
        );
        $stmt->execute([$countryId, $name]);

        $row = $this->fetchAssoc($stmt);
        return $row !== null ? CityDTO::fromRow($row) : null;
    }

    public function maxCityDisplayOrder(int $countryId): int
    {
        return $this->scalarInt(
            'SELECT COALESCE(MAX(`display_order`), 0) FROM `geo_cities` WHERE `country_id` = ?',
            [$countryId],
        );
    }

    // ================================================================== //
    //  CityDropdownRepositoryInterface
    // ================================================================== //

    /** @return list<CityDTO> */
    public function listActiveCitiesByCountryId(int $countryId, ?int $languageId = null): array
    {
        [$selectExtra, $join, $joinParams] = $this->buildTranslationJoin($languageId);

        $stmt = $this->prepareOrFail("
            SELECT ci.*, {$selectExtra}
            FROM   `geo_cities` AS ci
            {$join}
            WHERE  ci.`country_id` = ? AND ci.`is_active` = 1
            ORDER BY ci.`display_order` ASC, ci.`id` ASC
        ");

        $pos = 1;
        foreach ($joinParams as $v) { $stmt->bindValue($pos++, $v, PDO::PARAM_INT); }
        $stmt->bindValue($pos, $countryId, PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<CityDTO> */
        return array_map(
            static fn (array $row): CityDTO => CityDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );
    }

    /** @return list<CityDTO> */
    public function listActiveCitiesByCountryCode(string $countryCode, ?int $languageId = null): array
    {
        [$selectExtra, $join, $joinParams] = $this->buildTranslationJoin($languageId);

        $stmt = $this->prepareOrFail("
            SELECT ci.*, {$selectExtra}
            FROM   `geo_cities` AS ci
            INNER JOIN `geo_countries` gc ON gc.`id` = ci.`country_id`
            {$join}
            WHERE  gc.`code` = ? AND ci.`is_active` = 1 AND gc.`is_active` = 1
            ORDER BY ci.`display_order` ASC, ci.`id` ASC
        ");

        $pos = 1;
        foreach ($joinParams as $v) { $stmt->bindValue($pos++, $v, PDO::PARAM_INT); }
        $stmt->bindValue($pos, strtoupper($countryCode));
        $stmt->execute();

        /** @var list<CityDTO> */
        return array_map(
            static fn (array $row): CityDTO => CityDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );
    }

    // ================================================================== //
    //  CityRepositoryInterface — Commands
    // ================================================================== //

    public function createCity(CreateCityCommand $command): CityDTO
    {
        $displayOrder = $this->orderingManager->getNextPosition(
            $this->pdo,
            $this->orderingConfig($command->countryId),
            $command->countryId,
        );

        $stmt = $this->prepareOrFail(
            'INSERT INTO `geo_cities` (`country_id`, `code`, `name`, `time_zone`, `is_active`, `display_order`)
             VALUES (:country_id, :code, :name, :time_zone, :is_active, :display_order)',
        );

        try {
            $stmt->execute([
                ':country_id'    => $command->countryId,
                ':code'          => $command->code,
                ':name'          => $command->name,
                ':time_zone'     => $command->timeZone,
                ':is_active'     => $command->isActive ? 1 : 0,
                ':display_order' => $displayOrder,
            ]);
        } catch (\PDOException $e) {
            if ($this->isForeignKeyViolation($e)) {
                throw CountryNotFoundException::withId($command->countryId);
            }
            if ($this->isDuplicateKeyError($e)) {
                throw CityAlreadyExistsException::withNameAndCountryId($command->name, $command->countryId);
            }
            throw GeoPersistenceException::fromPdoException($e);
        }

        return $this->fetchOrFail((int) $this->pdo->lastInsertId());
    }

    public function updateCity(UpdateCityCommand $command): CityDTO
    {
        try {
            $stmt = $this->prepareOrFail(
                'UPDATE `geo_cities`
                 SET `code` = :code, `name` = :name, `time_zone` = :time_zone, `is_active` = :is_active
                 WHERE `id` = :id',
            );
            $stmt->execute([
                ':code'      => $command->code,
                ':name'      => $command->name,
                ':time_zone' => $command->timeZone,
                ':is_active' => $command->isActive ? 1 : 0,
                ':id'        => $command->id,
            ]);
        } catch (\PDOException $e) {
            if ($this->isDuplicateKeyError($e)) {
                $current = $this->fetchOrFail($command->id);

                throw CityAlreadyExistsException::withNameAndCountryId(
                    $command->name,
                    $current->countryId
                );
            }
            throw GeoPersistenceException::fromPdoException($e);
        } catch (Throwable $e) {
            if ($e instanceof GeoExceptionInterface) { throw $e; }
            throw GeoPersistenceException::fromThrowable($e);
        }

        return $this->fetchOrFail($command->id);
    }

    public function updateCityStatus(UpdateCityStatusCommand $command): CityDTO
    {
        $stmt = $this->prepareOrFail(
            'UPDATE `geo_cities` SET `is_active` = :is_active WHERE `id` = :id',
        );
        $stmt->execute([':is_active' => $command->isActive ? 1 : 0, ':id' => $command->id]);

        return $this->fetchOrFail($command->id);
    }

    public function reorderCity(int $id, int $newOrder): void
    {
        // Fetch the city first to know its country_id for scoped ordering.
        $city = $this->findCityById($id);
        if ($city === null) { throw CityNotFoundException::withId($id); }

        try {
            $moved = $this->orderingManager->moveWithinScope(
                $this->pdo,
                $this->orderingConfig($city->countryId),
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
    //  Private — helpers
    // ================================================================== //

    private function fetchOrFail(int $id): CityDTO
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

    private function orderingConfig(int $countryId): ScopedOrderingConfig
    {
        return new ScopedOrderingConfig(
            table:           'geo_cities',
            scopeColumn:     'country_id',
            idColumn:        'id',
            orderColumn:     'display_order',
            deletedAtColumn: null,
        );
    }

    /**
     * @return array{0: string, 1: string, 2: list<int>}
     */
    private function buildTranslationJoin(?int $languageId): array
    {
        if ($languageId === null) {
            return ['NULL AS `translated_name`, NULL AS `translation_language_id`', '', []];
        }

        return [
            'COALESCE(ct.`name`, ci.`name`) AS `translated_name`, ? AS `translation_language_id`',
            'LEFT JOIN `geo_city_translations` ct ON ct.`city_id` = ci.`id` AND ct.`language_id` = ?',
            [$languageId, $languageId],
        ];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function prepareOrFail(string $sql): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            throw GeoPersistenceException::prepareFailed($sql);
        }
        return $stmt;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchAssoc(PDOStatement $stmt): ?array
    {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || !is_array($row)) { return null; }
        /** @var array<string, mixed> $row */
        return $row;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAllAssoc(PDOStatement $stmt): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }

    /**
     * @param list<int|string> $params
     */
    private function scalarInt(string $sql, array $params = []): int
    {
        $stmt = $this->prepareOrFail($sql);
        $stmt->execute($params);
        $val = $stmt->fetchColumn();
        return is_numeric($val) ? (int) $val : 0;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function scalarFiltered(string $sql, array $params): int
    {
        $stmt = $this->prepareOrFail($sql);
        foreach ($params as $key => $value) { $stmt->bindValue(':' . $key, $value); }
        $stmt->execute();
        $val = $stmt->fetchColumn();
        return is_numeric($val) ? (int) $val : 0;
    }

    /**
     * @template T
     * @param list<T> $data
     * @return array{data: list<T>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    private function paginatedResult(array $data, int $page, int $perPage, int $total, int $filtered): array
    {
        return [
            'data'       => $data,
            'pagination' => [
                'page'     => $page,
                'per_page' => $perPage,
                'total'    => $total,
                'filtered' => $filtered,
            ],
        ];
    }

    private function isForeignKeyViolation(\PDOException $e): bool
    {
        return $e->getCode() === '23000' && str_contains($e->getMessage(), '1452');
    }

    private function isDuplicateKeyError(\PDOException $e): bool
    {
        return $e->getCode() === '23000' && str_contains($e->getMessage(), '1062');
    }
}

