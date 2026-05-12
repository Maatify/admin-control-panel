<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Infrastructure\Repository\Translation;

use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use PDO;

final readonly class CountryTranslationMatrixQueryService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @param array<string, int|string> $columnFilters
     * @return array{data: list<CountryTranslationMatrixRowDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function listByCountryPaginated(
        int $countryId,
        int $page,
        int $perPage,
        ?string $globalSearch,
        array $columnFilters,
    ): array {
        $this->assertCountryExists($countryId);

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
            LEFT JOIN geo_country_translations t
                   ON t.language_id = l.id
                  AND t.country_id = :country_id
            {$whereSql}
        ");

        foreach ($params as $key => $value) {
            $filteredStmt->bindValue(':' . $key, $value);
        }

        $filteredStmt->bindValue(':country_id', $countryId, PDO::PARAM_INT);
        $filteredStmt->execute();
        $filtered = (int) $filteredStmt->fetchColumn();

        $stmt = $this->pdo->prepare(" 
            SELECT
                t.id,
                COALESCE(t.country_id, :select_country_id) AS country_id,
                l.id AS language_id,
                l.code AS language_code,
                l.name AS language_name,
                t.name,
                t.created_at,
                t.updated_at,
                c.name AS base_country_name
            FROM languages l
            LEFT JOIN geo_country_translations t
                   ON t.language_id = l.id
                  AND t.country_id = :join_country_id
            LEFT JOIN geo_countries c
                   ON c.id = :base_country_id
            {$whereSql}
            ORDER BY l.id ASC
            LIMIT :limit OFFSET :offset
        ");

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->bindValue(':select_country_id', $countryId, PDO::PARAM_INT);
        $stmt->bindValue(':join_country_id', $countryId, PDO::PARAM_INT);
        $stmt->bindValue(':base_country_id', $countryId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        /**
         * @var list<array{
         *   id: int|string|null,
         *   country_id: int|string,
         *   language_id: int|string,
         *   language_code: mixed,
         *   language_name: mixed,
         *   name: mixed,
         *   created_at: mixed,
         *   updated_at: mixed,
         *   base_country_name: mixed
         * }> $rows
         */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = array_map(
            fn (array $row): CountryTranslationMatrixRowDTO => $this->hydrateMatrixRow($row),
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

    private function assertCountryExists(int $countryId): void
    {
        $stmt = $this->pdo->prepare('
            SELECT 1
            FROM geo_countries
            WHERE id = :country_id
            LIMIT 1
        ');
        $stmt->bindValue(':country_id', $countryId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('Country', $countryId);
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * @param array{
     *   id: int|string|null,
     *   country_id: int|string,
     *   language_id: int|string,
     *   language_code: mixed,
     *   language_name: mixed,
     *   name: mixed,
     *   created_at: mixed,
     *   updated_at: mixed,
     *   base_country_name: mixed
     * } $row
     */
    private function hydrateMatrixRow(array $row): CountryTranslationMatrixRowDTO
    {
        $id = $row['id'];

        return new CountryTranslationMatrixRowDTO(
            countryId: is_int($row['country_id']) ? $row['country_id'] : (int) $row['country_id'],
            languageId: is_int($row['language_id']) ? $row['language_id'] : (int) $row['language_id'],
            languageCode: is_string($row['language_code']) ? $row['language_code'] : '',
            languageName: is_string($row['language_name']) ? $row['language_name'] : '',

            id: is_int($id) || is_string($id) ? (int) $id : null,
            name: is_string($row['name']) ? $row['name'] : null,
            createdAt: is_string($row['created_at']) ? $row['created_at'] : null,
            updatedAt: is_string($row['updated_at']) ? $row['updated_at'] : null,

            baseCountryName: is_string($row['base_country_name']) ? $row['base_country_name'] : '',
        );
    }
}
