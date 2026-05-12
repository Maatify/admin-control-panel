<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Infrastructure\Repository\Translation;

use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use PDO;

final readonly class CityTranslationMatrixQueryService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @param array<string, int|string> $columnFilters
     * @return array{data: list<CityTranslationMatrixRowDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function listByCityPaginated(
        int $cityId,
        int $page,
        int $perPage,
        ?string $globalSearch,
        array $columnFilters,
    ): array {
        $this->assertCityExists($cityId);

        $page = max(1, $page);
        $limit = max(1, min(200, $perPage));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($globalSearch !== null && trim($globalSearch) !== '') {
            $globalSearch = '%' . $this->escapeLike(trim($globalSearch)) . '%';

            $where[] = '(l.name LIKE :global_text_language_name
                OR l.code LIKE :global_text_language_code
                OR t.name LIKE :global_text_name)';

            $params['global_text_language_name'] = $globalSearch;
            $params['global_text_language_code'] = $globalSearch;
            $params['global_text_name'] = $globalSearch;
        }

        if (isset($columnFilters['language_id'])) {
            $where[] = 'l.id = :language_id';
            $params['language_id'] = (int) $columnFilters['language_id'];
        }

        if (isset($columnFilters['language_code'])) {
            $where[] = 'l.code LIKE :language_code';
            $params['language_code'] = '%' . $this->escapeLike((string) $columnFilters['language_code']) . '%';
        }

        if (isset($columnFilters['language_name'])) {
            $where[] = 'l.name LIKE :language_name';
            $params['language_name'] = '%' . $this->escapeLike((string) $columnFilters['language_name']) . '%';
        }

        if (isset($columnFilters['name'])) {
            $where[] = 't.name LIKE :trans_name';
            $params['trans_name'] = '%' . $this->escapeLike((string) $columnFilters['name']) . '%';
        }

        if (isset($columnFilters['has_translation'])) {
            $value = (string) $columnFilters['has_translation'];

            if ($value === '1') {
                $where[] = 't.id IS NOT NULL';
            } elseif ($value === '0') {
                $where[] = 't.id IS NULL';
            }
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $totalStmt = $this->pdo->prepare('
            SELECT COUNT(*)
            FROM languages l
        ');
        $totalStmt->execute();
        $total = (int) $totalStmt->fetchColumn();

        $filteredStmt = $this->pdo->prepare(" 
            SELECT COUNT(*)
            FROM languages l
            LEFT JOIN geo_city_translations t
                   ON t.language_id = l.id
                  AND t.city_id = :city_id
            {$whereSql}
        ");

        foreach ($params as $key => $value) {
            $filteredStmt->bindValue(':' . $key, $value);
        }

        $filteredStmt->bindValue(':city_id', $cityId, PDO::PARAM_INT);
        $filteredStmt->execute();
        $filtered = (int) $filteredStmt->fetchColumn();

        $stmt = $this->pdo->prepare(" 
            SELECT
                t.id,
                COALESCE(t.city_id, :select_city_id) AS city_id,
                l.id AS language_id,
                l.code AS language_code,
                l.name AS language_name,
                t.name,
                t.created_at,
                t.updated_at,
                c.name AS base_city_name
            FROM languages l
            LEFT JOIN geo_city_translations t
                   ON t.language_id = l.id
                  AND t.city_id = :join_city_id
            LEFT JOIN geo_cities c
                   ON c.id = :base_city_id
            {$whereSql}
            ORDER BY l.id ASC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->bindValue(':select_city_id', $cityId, PDO::PARAM_INT);
        $stmt->bindValue(':join_city_id', $cityId, PDO::PARAM_INT);
        $stmt->bindValue(':base_city_id', $cityId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        /**
         * @var list<array{
         *   id: int|string|null,
         *   city_id: int|string,
         *   language_id: int|string,
         *   language_code: mixed,
         *   language_name: mixed,
         *   name: mixed,
         *   created_at: mixed,
         *   updated_at: mixed,
         *   base_city_name: mixed
         * }> $rows
         */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = array_map(
            fn (array $row): CityTranslationMatrixRowDTO => $this->hydrateMatrixRow($row),
            $rows
        );

        return [
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'filtered' => $filtered,
            ],
        ];
    }

    private function assertCityExists(int $cityId): void
    {
        $stmt = $this->pdo->prepare('
            SELECT 1
            FROM geo_cities
            WHERE id = :city_id
            LIMIT 1
        ');
        $stmt->bindValue(':city_id', $cityId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('City', $cityId);
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * @param array{
     *   id: int|string|null,
     *   city_id: int|string,
     *   language_id: int|string,
     *   language_code: mixed,
     *   language_name: mixed,
     *   name: mixed,
     *   created_at: mixed,
     *   updated_at: mixed,
     *   base_city_name: mixed
     * } $row
     */
    private function hydrateMatrixRow(array $row): CityTranslationMatrixRowDTO
    {
        $id = $row['id'];

        return new CityTranslationMatrixRowDTO(
            cityId: is_int($row['city_id']) ? $row['city_id'] : (int) $row['city_id'],
            languageId: is_int($row['language_id']) ? $row['language_id'] : (int) $row['language_id'],
            languageCode: is_string($row['language_code']) ? $row['language_code'] : '',
            languageName: is_string($row['language_name']) ? $row['language_name'] : '',

            id: is_int($id) || is_string($id) ? (int) $id : null,
            name: is_string($row['name']) ? $row['name'] : null,
            createdAt: is_string($row['created_at']) ? $row['created_at'] : null,
            updatedAt: is_string($row['updated_at']) ? $row['updated_at'] : null,

            baseCityName: is_string($row['base_city_name']) ? $row['base_city_name'] : '',
        );
    }
}
