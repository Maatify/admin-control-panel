<?php

declare(strict_types=1);

namespace Maatify\Geo\Infrastructure\Repository;

use Maatify\Geo\Command\DeleteCityTranslationCommand;
use Maatify\Geo\Command\UpsertCityTranslationCommand;
use Maatify\Geo\Contract\CityTranslationRepositoryInterface;
use Maatify\Geo\DTO\CityTranslationDTO;
use Maatify\Geo\Exception\GeoInvalidArgumentException;
use Maatify\Geo\Exception\GeoPersistenceException;
use PDO;
use PDOStatement;

/**
 * PDO implementation for all city translation read and write operations.
 *
 * NEVER references the `languages` table.
 */
final class PdoCityTranslationRepository implements CityTranslationRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    // ================================================================== //
    //  Queries
    // ================================================================== //

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
        /** @var callable(array<string,mixed>): CityTranslationDTO $factory */
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
    //  Commands
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
                ':city_id'     => $command->cityId,
                ':language_id' => $command->languageId,
                ':insert_name' => $command->translatedName,
                ':update_name' => $command->translatedName,
            ]);
        } catch (\PDOException $e) {
            if ($this->isForeignKeyViolation($e)) {
                throw GeoInvalidArgumentException::invalidLanguageId($command->languageId);
            }
            throw GeoPersistenceException::fromPdoException($e);
        }

        $dto = $this->findCityTranslation($command->cityId, $command->languageId);
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
    //  Private — shared translation pagination
    // ================================================================== //

    /**
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

    private function isForeignKeyViolation(\PDOException $e): bool
    {
        return $e->getCode() === '23000' && str_contains($e->getMessage(), '1452');
    }
}

