<?php

declare(strict_types=1);

namespace Maatify\ExchangeRates\Admin\Provider\Infrastructure\Repository;

use Maatify\ExchangeRates\Admin\Provider\Contract\ProviderQueryRepositoryInterface;
use Maatify\ExchangeRates\Admin\Provider\DTO\ProviderDTO;
use Maatify\ExchangeRates\Admin\Provider\DTO\ProviderListItemDTO;
use PDO;

final class PdoProviderQueryRepository implements ProviderQueryRepositoryInterface
{
    public function __construct(private readonly PDO $pdo) {}

    // =========================================================
    //  Single-row lookups
    // =========================================================

    /**
     * Find a provider by id. Includes soft-deleted rows.
     * Callers must check $dto->deletedAt to determine deletion status.
     */
    public function findById(int $id): ?ProviderDTO
    {
        $stmt = $this->pdo->prepare(
            'SELECT `id`, `name`, `code`, `description`, `is_active`, `display_order`,
                    `created_at`, `updated_at`, `deleted_at`
               FROM `maa_er_providers`
              WHERE `id` = :id
              LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrateDetail($row);
    }

    /**
     * Find a provider by code. Includes inactive providers, excludes soft-deleted rows.
     */
    public function findByCode(string $code): ?ProviderDTO
    {
        $stmt = $this->pdo->prepare(
            'SELECT `id`, `name`, `code`, `description`, `is_active`, `display_order`,
                    `created_at`, `updated_at`, `deleted_at`
               FROM `maa_er_providers`
              WHERE `code` = :code
                AND `deleted_at` IS NULL
              LIMIT 1'
        );
        $stmt->execute(['code' => strtoupper(trim($code))]);

        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrateDetail($row);
    }

    // =========================================================
    //  Paginated list
    // =========================================================

    /**
     * @param  array<string, string|int>  $columnFilters
     * @return array{data: list<ProviderListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function list(
        int     $page,
        int     $perPage,
        ?string $globalSearch,
        array   $columnFilters,
    ): array {
        // 1. Total — unfiltered
        $stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM `maa_er_providers`');
        if ($stmtTotal === false) {
            throw new \RuntimeException('Failed to count maa_er_providers');
        }
        $total = (int) $stmtTotal->fetchColumn();

        // 2. Build WHERE
        $where  = [];
        $params = [];

        if ($globalSearch !== null && trim($globalSearch) !== '') {
            // Each column gets its own named placeholder — PDO does not allow
            // reuse of the same placeholder within a single statement.
            $where[]              = '(`p`.`name` LIKE :global_name OR `p`.`code` LIKE :global_code)';
            $searchVal            = '%' . trim($globalSearch) . '%';
            $params['global_name'] = $searchVal;
            $params['global_code'] = $searchVal;
        }

        if (isset($columnFilters['is_active'])) {
            $where[]             = '`p`.`is_active` = :is_active';
            $params['is_active'] = (int) $columnFilters['is_active'];
        }

        if (isset($columnFilters['deleted'])) {
            $where[] = (int) $columnFilters['deleted'] === 1
                ? '`p`.`deleted_at` IS NOT NULL'
                : '`p`.`deleted_at` IS NULL';
        } else {
            $where[] = '`p`.`deleted_at` IS NULL';
        }

        // $where always contains at least the deleted_at condition — never empty.
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // 3. Filtered count
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(`p`.`id`) FROM `maa_er_providers` `p` {$whereSql}"
        );
        $stmtFiltered->execute($params);
        $filtered = (int) $stmtFiltered->fetchColumn();

        // 4. Data
        $offset = ($page - 1) * $perPage;
        $stmt   = $this->pdo->prepare(
            "SELECT `p`.`id`, `p`.`name`, `p`.`code`, `p`.`is_active`,
                    `p`.`display_order`, `p`.`created_at`
               FROM `maa_er_providers` `p`
               {$whereSql}
              ORDER BY `p`.`display_order` ASC, `p`.`id` ASC
              LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $item = $this->hydrateListItem($row);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        return [
            'data'       => $items,
            'pagination' => [
                'page'     => $page,
                'per_page' => $perPage,
                'total'    => $total,
                'filtered' => $filtered,
            ],
        ];
    }

    // =========================================================
    //  Hydration
    // =========================================================

    /** @param array<string, mixed> $row */
    private function hydrateDetail(array $row): ProviderDTO
    {
        $id           = $row['id']            ?? null;
        $isActive     = $row['is_active']     ?? null;
        $displayOrder = $row['display_order'] ?? null;
        $updatedAt    = $row['updated_at']    ?? null;
        $deletedAt    = $row['deleted_at']    ?? null;

        return new ProviderDTO(
            id:           (is_int($id)       || is_string($id))       ? (int) $id           : 0,
            name:         is_string($row['name'] ?? null)              ? (string) $row['name']        : '',
            code:         is_string($row['code'] ?? null)              ? (string) $row['code']        : '',
            description:  is_string($row['description'] ?? null)       ? (string) $row['description'] : null,
            isActive:     (is_int($isActive) || is_string($isActive))  && (int) $isActive === 1,
            displayOrder: (is_int($displayOrder) || is_string($displayOrder)) ? (int) $displayOrder : 0,
            createdAt:    is_string($row['created_at'] ?? null)        ? (string) $row['created_at']  : '',
            updatedAt:    is_string($updatedAt) ? $updatedAt : null,
            deletedAt:    is_string($deletedAt) ? $deletedAt : null,
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrateListItem(array $row): ?ProviderListItemDTO
    {
        $id           = $row['id']            ?? null;
        $isActive     = $row['is_active']     ?? null;
        $displayOrder = $row['display_order'] ?? null;

        if (! (is_int($id) || is_string($id))) {
            return null;
        }

        return new ProviderListItemDTO(
            id:           (int) $id,
            name:         is_string($row['name'] ?? null) ? (string) $row['name'] : '',
            code:         is_string($row['code'] ?? null) ? (string) $row['code'] : '',
            isActive:     (is_int($isActive)     || is_string($isActive))     && (int) $isActive     === 1,
            displayOrder: (is_int($displayOrder) || is_string($displayOrder)) ? (int) $displayOrder  : 0,
            createdAt:    is_string($row['created_at'] ?? null) ? (string) $row['created_at'] : '',
        );
    }
}
