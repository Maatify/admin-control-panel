<?php

declare(strict_types=1);

namespace Maatify\Geo\Infrastructure\Repository;

use Maatify\Geo\Contract\GeoQueryReaderInterface;
use Maatify\Geo\DTO\CityDTO;
use Maatify\Geo\DTO\CityTranslationDTO;
use Maatify\Geo\DTO\CountryDTO;
use Maatify\Geo\DTO\CountryTranslationDTO;
use Maatify\Geo\Exception\GeoPersistenceException;
use PDO;
use PDOStatement;

/**
 * PDO-based implementation of GeoQueryReaderInterface.
 *
 * NEVER references the `languages` table.
 * language_id is stored and queried as a plain INT.
 * The admin execution layer joins language metadata independently.
 *
 * Translation COALESCE strategy:
 *   $languageId given  → LEFT JOIN geo_*_translations; COALESCE(t.name, e.name)
 *   $languageId null   → No JOIN; translatedName = NULL in DTO
 */
final class PdoGeoQueryReader implements GeoQueryReaderInterface
{
    public function __construct(private readonly PDO $pdo) {}

    // ================================================================== //
    //  Countries — admin list
    // ================================================================== //

    /**
     * {@inheritDoc}
     *
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
            $where[]               = '(c.`code` LIKE :global_text OR c.`name` LIKE :global_text)';
            $params['global_text'] = '%' . $this->escapeLike(trim($globalSearch)) . '%';
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

        [$selectExtra, $join, $joinParams] = $this->buildCountryTranslationJoin($languageId);

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

    // ================================================================== //
    //  Countries — public active list
    // ================================================================== //

    /** {@inheritDoc} */
    public function listActiveCountries(?int $languageId = null): array
    {
        [$selectExtra, $join, $joinParams] = $this->buildCountryTranslationJoin($languageId);

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
    //  Countries — single-record lookups
    // ================================================================== //

    /** {@inheritDoc} */
    public function findCountryById(int $id, ?int $languageId = null): ?CountryDTO
    {
        [$selectExtra, $join, $joinParams] = $this->buildCountryTranslationJoin($languageId);

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

    /** {@inheritDoc} */
    public function findCountryByCode(string $code, ?int $languageId = null): ?CountryDTO
    {
        [$selectExtra, $join, $joinParams] = $this->buildCountryTranslationJoin($languageId);

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

    // ================================================================== //
    //  Country translations — admin (geo tables only, no languages JOIN)
    // ================================================================== //

    /** {@inheritDoc} */
    public function findCountryTranslation(int $countryId, int $languageId): ?CountryTranslationDTO
    {
        $stmt = $this->prepareOrFail(
            'SELECT `id`, `country_id`, `language_id`, `name`, `created_at`, `updated_at`
             FROM `geo_country_translations`
             WHERE `country_id` = ? AND `language_id` = ?
             LIMIT 1',
        );
        $stmt->execute([$countryId, $languageId]);

        $row = $this->fetchAssoc($stmt);
        return $row !== null ? CountryTranslationDTO::fromRow($row) : null;
    }

    /**
     * {@inheritDoc}
     *
     * @param  array<string, int|string> $columnFilters
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
    ): array {
        /** @return CountryTranslationDTO */
        $factory = static fn (array $row): CountryTranslationDTO => CountryTranslationDTO::fromRow($row);

        return $this->listTranslationsPaginated(
            table:        'geo_country_translations',
            fkColumn:     'country_id',
            entityId:     $countryId,
            page:         $page,
            perPage:      $perPage,
            globalSearch: $globalSearch,
            filters:      $columnFilters,
            dtoFactory:   $factory,
        );
    }

    // ================================================================== //
    //  Cities — admin list
    // ================================================================== //

    /**
     * {@inheritDoc}
     *
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
            $where[]               = '(ci.`code` LIKE :global_text OR ci.`name` LIKE :global_text)';
            $params['global_text'] = '%' . $this->escapeLike(trim($globalSearch)) . '%';
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

        [$selectExtra, $join, $joinParams] = $this->buildCityTranslationJoin($languageId);

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

    // ================================================================== //
    //  Cities — public active list
    // ================================================================== //

    /** {@inheritDoc} */
    public function listActiveCitiesByCountryId(int $countryId, ?int $languageId = null): array
    {
        [$selectExtra, $join, $joinParams] = $this->buildCityTranslationJoin($languageId);

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

    /** {@inheritDoc} */
    public function listActiveCitiesByCountryCode(string $countryCode, ?int $languageId = null): array
    {
        [$selectExtra, $join, $joinParams] = $this->buildCityTranslationJoin($languageId);

        $stmt = $this->prepareOrFail("
            SELECT ci.*, {$selectExtra}
            FROM   `geo_cities` AS ci
            INNER JOIN `geo_countries` gc ON gc.`id` = ci.`country_id`
            {$join}
            WHERE  gc.`code` = ? AND ci.`is_active` = 1
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
    //  Cities — single-record lookup
    // ================================================================== //

    /** {@inheritDoc} */
    public function findCityById(int $id, ?int $languageId = null): ?CityDTO
    {
        [$selectExtra, $join, $joinParams] = $this->buildCityTranslationJoin($languageId);

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

    // ================================================================== //
    //  City translations — admin (geo tables only, no languages JOIN)
    // ================================================================== //

    /** {@inheritDoc} */
    public function findCityTranslation(int $cityId, int $languageId): ?CityTranslationDTO
    {
        $stmt = $this->prepareOrFail(
            'SELECT `id`, `city_id`, `language_id`, `name`, `created_at`, `updated_at`
             FROM `geo_city_translations`
             WHERE `city_id` = ? AND `language_id` = ?
             LIMIT 1',
        );
        $stmt->execute([$cityId, $languageId]);

        $row = $this->fetchAssoc($stmt);
        return $row !== null ? CityTranslationDTO::fromRow($row) : null;
    }

    /**
     * {@inheritDoc}
     *
     * @param  array<string, int|string> $columnFilters
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
    ): array {
        /** @return CityTranslationDTO */
        $factory = static fn (array $row): CityTranslationDTO => CityTranslationDTO::fromRow($row);

        return $this->listTranslationsPaginated(
            table:        'geo_city_translations',
            fkColumn:     'city_id',
            entityId:     $cityId,
            page:         $page,
            perPage:      $perPage,
            globalSearch: $globalSearch,
            filters:      $columnFilters,
            dtoFactory:   $factory,
        );
    }

    // ================================================================== //
    //  Aggregates
    // ================================================================== //

    /** {@inheritDoc} */
    public function maxCountryDisplayOrder(): int
    {
        return $this->scalarInt('SELECT COALESCE(MAX(`display_order`), 0) FROM `geo_countries`');
    }

    /** {@inheritDoc} */
    public function maxCityDisplayOrder(int $countryId): int
    {
        return $this->scalarInt(
            'SELECT COALESCE(MAX(`display_order`), 0) FROM `geo_cities` WHERE `country_id` = ?',
            [$countryId],
        );
    }

    // ================================================================== //
    //  Private — translation JOIN builders
    // ================================================================== //

    /**
     * @return array{0: string, 1: string, 2: list<int>}
     */
    private function buildCountryTranslationJoin(?int $languageId): array
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

    /**
     * @return array{0: string, 1: string, 2: list<int>}
     */
    private function buildCityTranslationJoin(?int $languageId): array
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

    // ================================================================== //
    //  Private — shared translation pagination (geo tables only)
    // ================================================================== //

    /**
     * Generic paginated translation listing — geo translation tables only.
     * No JOIN to the languages table. language_id is a plain INT.
     *
     * @template T
     * @param  callable(array<string,mixed>): T $dtoFactory
     * @param  array<string, int|string>        $filters
     * @return array{
     *     data:       list<T>,
     *     pagination: array{page: int, per_page: int, total: int, filtered: int}
     * }
     */
    private function listTranslationsPaginated(
        string   $table,
        string   $fkColumn,
        int      $entityId,
        int      $page,
        int      $perPage,
        ?string  $globalSearch,
        array    $filters,
        callable $dtoFactory,
    ): array {
        $page   = max(1, $page);
        $limit  = max(1, min(200, $perPage));
        $offset = ($page - 1) * $limit;

        $where  = ['`' . $fkColumn . '` = :entity_id'];
        $params = [':entity_id' => $entityId];

        if ($globalSearch !== null && trim($globalSearch) !== '') {
            $where[]               = '`name` LIKE :global_text';
            $params['global_text'] = '%' . $this->escapeLike(trim($globalSearch)) . '%';
        }
        if (isset($filters['language_id'])) {
            $where[]               = '`language_id` = :language_id';
            $params['language_id'] = (int) $filters['language_id'];
        }
        if (isset($filters['name'])) {
            $where[]              = '`name` LIKE :trans_name';
            $params['trans_name'] = '%' . $this->escapeLike((string) $filters['name']) . '%';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $stmtTotal = $this->prepareOrFail(
            "SELECT COUNT(*) FROM `{$table}` WHERE `{$fkColumn}` = :entity_id",
        );
        $stmtTotal->execute([':entity_id' => $entityId]);
        $total = (int) $stmtTotal->fetchColumn();

        $stmtFiltered = $this->prepareOrFail("SELECT COUNT(*) FROM `{$table}` {$whereSql}");
        $stmtFiltered->execute($params);
        $filtered = (int) $stmtFiltered->fetchColumn();

        $stmt = $this->prepareOrFail(
            "SELECT `id`, `{$fkColumn}`, `language_id`, `name`, `created_at`, `updated_at`
             FROM `{$table}` {$whereSql}
             ORDER BY `language_id` ASC
             LIMIT :limit OFFSET :offset",
        );
        foreach ($params as $key => $value) { $stmt->bindValue($key, $value); }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = array_map($dtoFactory, $this->fetchAllAssoc($stmt));

        return $this->paginatedResult($data, $page, $limit, $total, $filtered);
    }

    // ================================================================== //
    //  Private — utilities
    // ================================================================== //

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
}
