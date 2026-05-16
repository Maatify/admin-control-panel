<?php

declare(strict_types=1);

namespace Maatify\Geo\Infrastructure\Repository;

use Maatify\Geo\Command\CreateCountryCommand;
use Maatify\Geo\Command\UpdateCountryCommand;
use Maatify\Geo\Command\UpdateCountryStatusCommand;
use Maatify\Geo\Contract\CountryDropdownRepositoryInterface;
use Maatify\Geo\Contract\CountryRepositoryInterface;
use Maatify\Geo\DTO\CountryDTO;
use Maatify\Geo\Exception\CountryCodeAlreadyExistsException;
use Maatify\Geo\Exception\CountryNotFoundException;
use Maatify\Geo\Exception\GeoExceptionInterface;
use Maatify\Geo\Exception\GeoPersistenceException;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use PDO;
use PDOStatement;
use Throwable;

/**
 * PDO implementation for all country read and write operations.
 *
 * Implements CountryRepositoryInterface (admin list, lookup, CRUD, reorder)
 * and CountryDropdownRepositoryInterface (active-only list for dropdowns).
 *
 * NEVER references the `languages` table.
 */
final class PdoCountryRepository implements CountryRepositoryInterface, CountryDropdownRepositoryInterface
{
    public function __construct(
        private readonly PDO                   $pdo,
        private readonly ScopedOrderingManager $orderingManager = new ScopedOrderingManager(),
    ) {}

    // ================================================================== //
    //  CountryRepositoryInterface — Queries
    // ================================================================== //

    /**
     * @param  array<string, int|string> $columnFilters
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
    ): array {
        $page   = max(1, $page);
        $limit  = max(1, min(200, $perPage));
        $offset = ($page - 1) * $limit;

        $where  = [];
        $params = [];

        if ($globalSearch !== null && trim($globalSearch) !== '') {
            $globalText = '%' . $this->escapeLike(trim($globalSearch)) . '%';

            $where[] = '(c.`code` LIKE :global_text_code OR c.`name` LIKE :global_text_name)';
            $params['global_text_code'] = $globalText;
            $params['global_text_name'] = $globalText;
        }
        if (isset($columnFilters['is_active'])) {
            $where[]             = 'c.`is_active` = :is_active';
            $params['is_active'] = (int) $columnFilters['is_active'];
        }
        if (isset($columnFilters['code'])) {
            $where[]        = 'c.`code` = :code';
            $params['code'] = strtoupper(trim((string) $columnFilters['code']));
        }
        if (isset($columnFilters['name'])) {
            $where[]        = 'c.`name` LIKE :name';
            $params['name'] = '%' . $this->escapeLike(trim((string) $columnFilters['name'])) . '%';
        }
        if (isset($columnFilters['id'])) {
            $where[]      = 'c.`id` = :id';
            $params['id'] = (int) $columnFilters['id'];
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        [$selectExtra, $join, $joinParams] = $this->buildTranslationJoin($languageId);

        $total    = $this->scalarInt('SELECT COUNT(*) FROM `geo_countries`');
        $filtered = $this->scalarFiltered("SELECT COUNT(*) FROM `geo_countries` AS c {$whereSql}", $params);

        $stmt = $this->prepareOrFail("
            SELECT c.*, {$selectExtra}
            FROM   `geo_countries` AS c
            {$join}
            {$whereSql}
            ORDER BY c.`display_order` ASC, c.`id` ASC
            LIMIT :limit OFFSET :offset
        ");

        $pos = 1;
        foreach ($joinParams as $v) { $stmt->bindValue($pos++, $v, PDO::PARAM_INT); }
        foreach ($params as $key => $value) { $stmt->bindValue(':' . $key, $value); }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<CountryDTO> $data */
        $data = array_map(
            static fn (array $row): CountryDTO => CountryDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );

        return $this->paginatedResult($data, $page, $limit, $total, $filtered);
    }

    public function findCountryById(int $id, ?int $languageId = null): ?CountryDTO
    {
        [$selectExtra, $join, $joinParams] = $this->buildTranslationJoin($languageId);

        $stmt = $this->prepareOrFail("
            SELECT c.*, {$selectExtra}
            FROM   `geo_countries` AS c
            {$join}
            WHERE  c.`id` = ?
            LIMIT  1
        ");

        $pos = 1;
        foreach ($joinParams as $v) { $stmt->bindValue($pos++, $v, PDO::PARAM_INT); }
        $stmt->bindValue($pos, $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $this->fetchAssoc($stmt);
        return $row !== null ? CountryDTO::fromRow($row) : null;
    }

    public function findCountryByCode(string $code, ?int $languageId = null): ?CountryDTO
    {
        [$selectExtra, $join, $joinParams] = $this->buildTranslationJoin($languageId);

        $stmt = $this->prepareOrFail("
            SELECT c.*, {$selectExtra}
            FROM   `geo_countries` AS c
            {$join}
            WHERE  c.`code` = ?
            LIMIT  1
        ");

        $pos = 1;
        foreach ($joinParams as $v) { $stmt->bindValue($pos++, $v, PDO::PARAM_INT); }
        $stmt->bindValue($pos, strtoupper($code));
        $stmt->execute();

        $row = $this->fetchAssoc($stmt);
        return $row !== null ? CountryDTO::fromRow($row) : null;
    }

    public function maxCountryDisplayOrder(): int
    {
        return $this->scalarInt('SELECT COALESCE(MAX(`display_order`), 0) FROM `geo_countries`');
    }

    // ================================================================== //
    //  CountryDropdownRepositoryInterface
    // ================================================================== //

    /** @return list<CountryDTO> */
    public function listActiveCountries(?int $languageId = null): array
    {
        [$selectExtra, $join, $joinParams] = $this->buildTranslationJoin($languageId);

        $stmt = $this->prepareOrFail("
            SELECT c.*, {$selectExtra}
            FROM   `geo_countries` AS c
            {$join}
            WHERE  c.`is_active` = 1
            ORDER BY c.`display_order` ASC, c.`id` ASC
        ");

        $pos = 1;
        foreach ($joinParams as $v) { $stmt->bindValue($pos++, $v, PDO::PARAM_INT); }
        $stmt->execute();

        /** @var list<CountryDTO> */
        return array_map(
            static fn (array $row): CountryDTO => CountryDTO::fromRow($row),
            $this->fetchAllAssoc($stmt),
        );
    }

    // ================================================================== //
    //  CountryRepositoryInterface — Commands
    // ================================================================== //

    public function createCountry(CreateCountryCommand $command): CountryDTO
    {
        $displayOrder = $this->orderingManager->getNextPosition(
            $this->pdo,
            $this->orderingConfig(),
            null,
        );

        $stmt = $this->prepareOrFail(
            'INSERT INTO `geo_countries` (`code`, `name`, `phone_code`, `currency`, `icon`, `is_active`, `is_state_required`, `is_postcode_required`, `display_order`)
             VALUES (:code, :name, :phone_code, :currency, :icon, :is_active, :is_state_required, :is_postcode_required, :display_order)',
        );

        try {
            $stmt->execute([
                ':code'                => strtoupper($command->code),
                ':name'                => $command->name,
                ':phone_code'          => $command->phoneCode,
                ':currency'            => $command->currency,
                ':icon'                => $command->icon,
                ':is_active'           => $command->isActive ? 1 : 0,
                ':is_state_required'   => $command->isStateRequired ? 1 : 0,
                ':is_postcode_required'=> $command->isPostcodeRequired ? 1 : 0,
                ':display_order'       => $displayOrder,
            ]);
        } catch (\PDOException $e) {
            if ($this->isDuplicateKeyError($e)) {
                throw CountryCodeAlreadyExistsException::withCode($command->code);
            }
            throw GeoPersistenceException::fromPdoException($e);
        }

        return $this->fetchOrFail((int) $this->pdo->lastInsertId());
    }

    public function updateCountry(UpdateCountryCommand $command): CountryDTO
    {
        $sets   = [
            '`code` = :code',
            '`name` = :name',
            '`phone_code` = :phone_code',
            '`currency` = :currency',
            '`icon` = :icon',
            '`is_active` = :is_active',
        ];
        $params = [
            ':code'       => strtoupper($command->code),
            ':name'       => $command->name,
            ':phone_code' => $command->phoneCode,
            ':currency'   => $command->currency,
            ':icon'       => $command->icon,
            ':is_active'  => $command->isActive ? 1 : 0,
            ':id'         => $command->id,
        ];

        if ($command->isStateRequired !== null) {
            $sets[]                            = '`is_state_required` = :is_state_required';
            $params[':is_state_required']      = $command->isStateRequired ? 1 : 0;
        }

        if ($command->isPostcodeRequired !== null) {
            $sets[]                                = '`is_postcode_required` = :is_postcode_required';
            $params[':is_postcode_required']       = $command->isPostcodeRequired ? 1 : 0;
        }

        try {
            $stmt = $this->prepareOrFail(
                'UPDATE `geo_countries` SET ' . implode(', ', $sets) . ' WHERE `id` = :id',
            );
            $stmt->execute($params);
        } catch (\PDOException $e) {
            if ($this->isDuplicateKeyError($e)) {
                throw CountryCodeAlreadyExistsException::withCode($command->code);
            }
            throw GeoPersistenceException::fromPdoException($e);
        } catch (Throwable $e) {
            if ($e instanceof GeoExceptionInterface) { throw $e; }
            throw GeoPersistenceException::fromThrowable($e);
        }

        return $this->fetchOrFail($command->id);
    }

    public function updateCountryStatus(UpdateCountryStatusCommand $command): CountryDTO
    {
        $stmt = $this->prepareOrFail(
            'UPDATE `geo_countries` SET `is_active` = :is_active WHERE `id` = :id',
        );
        $stmt->execute([':is_active' => $command->isActive ? 1 : 0, ':id' => $command->id]);

        return $this->fetchOrFail($command->id);
    }

    public function reorderCountry(int $id, int $newOrder): void
    {
        try {
            $moved = $this->orderingManager->moveWithinScope(
                $this->pdo,
                $this->orderingConfig(),
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
    //  Private — helpers
    // ================================================================== //

    private function fetchOrFail(int $id): CountryDTO
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

    private function orderingConfig(): ScopedOrderingConfig
    {
        return new ScopedOrderingConfig(
            table:           'geo_countries',
            scopeColumn:     null,
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
            'COALESCE(ct.`name`, c.`name`) AS `translated_name`, ? AS `translation_language_id`',
            'LEFT JOIN `geo_country_translations` ct ON ct.`country_id` = c.`id` AND ct.`language_id` = ?',
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

    private function isDuplicateKeyError(\PDOException $e): bool
    {
        return $e->getCode() === '23000' && str_contains($e->getMessage(), '1062');
    }
}

