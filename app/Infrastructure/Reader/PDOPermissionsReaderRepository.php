<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-25 20:05
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Infrastructure\Reader;

use App\Domain\Contracts\PermissionsReaderRepositoryInterface;
use App\Domain\DTO\Common\PaginationDTO;
use App\Domain\DTO\Permission\PermissionListItemDTO;
use App\Domain\DTO\Permission\PermissionsQueryResponseDTO;
use App\Domain\List\ListQueryDTO;
use App\Infrastructure\Query\ResolvedListFilters;
use PDO;

class PDOPermissionsReaderRepository implements PermissionsReaderRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function queryPermissions(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): PermissionsQueryResponseDTO {

        $where  = [];
        $params = [];

        // ─────────────────────────────
        // Global search (name only)
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);

            if ($g !== '') {
                $where[] = 'p.name LIKE :global_name';
                $params['global_name'] = '%' . $g . '%';
            }
        }

        // ─────────────────────────────
        // Column filters
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {

            if ($alias === 'id') {
                $where[] = 'p.id = :id';
                $params['id'] = (int) $value;
            }

            if ($alias === 'name') {
                $where[] = 'p.name LIKE :name';
                $params['name'] = '%' . trim((string) $value) . '%';
            }

            // group is derived from name (substring before first dot)
            if ($alias === 'group') {
                $where[] = 'SUBSTRING_INDEX(p.name, ".", 1) = :group_name';
                $params['group_name'] = (string) $value;
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total (no filters)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM permissions');

        if ($stmtTotal === false) {
            throw new \RuntimeException('Failed to execute permissions total count query');
        }

        $total = (int) $stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM permissions p
             {$whereSql}"
        );
        $stmtFiltered->execute($params);
        $filtered = (int) $stmtFiltered->fetchColumn();

        // ─────────────────────────────
        // Data
        // ─────────────────────────────
        $limit  = $query->perPage;
        $offset = ($query->page - 1) * $limit;

        $sql = "
            SELECT
                p.id,
                p.name,
                p.display_name,
                p.description
            FROM permissions p
            {$whereSql}
            ORDER BY
                SUBSTRING_INDEX(p.name, '.', 1) ASC,
                p.name ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];

        foreach ($rows ?: [] as $row) {
            $name  = (string) $row['name'];
            $group = explode('.', $name, 2)[0];

            $items[] = new PermissionListItemDTO(
                id: (int) $row['id'],
                name: $name,
                group: $group,
                display_name: $row['display_name'] !== null ? (string) $row['display_name'] : null,
                description: $row['description'] !== null ? (string) $row['description'] : null,
            );
        }

        return new PermissionsQueryResponseDTO(
            data: $items,
            pagination: new PaginationDTO(
                page: $query->page,
                perPage: $query->perPage,
                total: $total,
                filtered: $filtered
            )
        );
    }
}
