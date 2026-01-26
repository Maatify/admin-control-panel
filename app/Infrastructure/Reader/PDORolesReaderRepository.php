<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-26 20:27
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Infrastructure\Reader;

use App\Domain\Contracts\RolesReaderRepositoryInterface;
use App\Domain\DTO\Common\PaginationDTO;
use App\Domain\DTO\Roles\RolesListItemDTO;
use App\Domain\DTO\Roles\RolesQueryResponseDTO;
use App\Domain\List\ListQueryDTO;
use App\Infrastructure\Query\ResolvedListFilters;
use PDO;

readonly class PDORolesReaderRepository implements RolesReaderRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
    ) {
    }

    public function queryRoles(ListQueryDTO $query, ResolvedListFilters $filters): RolesQueryResponseDTO
    {
        $where  = [];
        $params = [];

        // ─────────────────────────────
        // Global search (name only)
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);

            if ($g !== '') {
                $where[] = 'r.name LIKE :global_name';
                $params['global_name'] = '%' . $g . '%';
            }
        }

        // ─────────────────────────────
        // Column filters
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {

            if ($alias === 'id') {
                $where[] = 'r.id = :id';
                $params['id'] = (int) $value;
            }

            if ($alias === 'name') {
                $where[] = 'r.name LIKE :name';
                $params['name'] = '%' . trim((string) $value) . '%';
            }

            // group is derived from name (substring before first dot)
            if ($alias === 'group') {
                $where[] = 'SUBSTRING_INDEX(r.name, ".", 1) = :group_name';
                $params['group_name'] = (string) $value;
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total (no filters)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM roles');

        if ($stmtTotal === false) {
            throw new \RuntimeException('Failed to execute roles total count query');
        }

        $total = (int) $stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM roles r
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
                r.id,
                r.name,
                r.display_name,
                r.description
            FROM roles r
            {$whereSql}
            ORDER BY
                SUBSTRING_INDEX(r.name, '.', 1) ASC,
                r.name ASC
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

            $items[] = new RolesListItemDTO(
                id: (int) $row['id'],
                name: $name,
                group: $group,
                display_name: $row['display_name'] !== null ? (string) $row['display_name'] : null,
                description: $row['description'] !== null ? (string) $row['description'] : null,
            );
        }

        return new RolesQueryResponseDTO(
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