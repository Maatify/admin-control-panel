<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\Roles;

use Maatify\AdminKernel\Domain\Contracts\Roles\AdminRolesRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\DTO\Roles\AdminRoleListItemDTO;
use Maatify\AdminKernel\Domain\DTO\Roles\AdminRolesQueryResponseDTO;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use PDO;
use RuntimeException;

final readonly class PdoAdminRolesRepository implements AdminRolesRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function queryRolesForAdmin(
        int $adminId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): AdminRolesQueryResponseDTO {

        // ─────────────────────────────
        // Ensure admin exists
        // ─────────────────────────────
        $stmt = $this->pdo->prepare('SELECT 1 FROM admins WHERE id = :id');
        $stmt->execute(['id' => $adminId]);
        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('admin', $adminId);
        }

        $where  = ['ar.admin_id = :admin_id'];
        $params = ['admin_id' => $adminId];

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

            if ($alias === 'group') {
                $where[] = 'SUBSTRING_INDEX(r.name, ".", 1) = :group_name';
                $params['group_name'] = (string) $value;
            }

            if ($alias === 'is_active') {
                $where[] = 'r.is_active = :is_active';
                $params['is_active'] = (int) $value;
            }
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // ─────────────────────────────
        // Total (assigned only, no filters)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM admin_roles
             WHERE admin_id = :admin_id'
        );

        if ($stmtTotal->execute(['admin_id' => $adminId]) === false) {
            throw new RuntimeException('Failed to execute admin roles total count query');
        }

        $total = (int) $stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM admin_roles ar
             INNER JOIN roles r ON r.id = ar.role_id
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
                r.description,
                r.is_active
            FROM admin_roles ar
            INNER JOIN roles r ON r.id = ar.role_id
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

        if ($stmt->execute() === false) {
            throw new RuntimeException('Failed to query admin roles.');
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];

        foreach ($rows ?: [] as $row) {
            $name  = (string) $row['name'];
            $group = explode('.', $name, 2)[0];

            $items[] = new AdminRoleListItemDTO(
                id: (int) $row['id'],
                name: $name,
                group: $group,
                display_name: $row['display_name'] !== null ? (string) $row['display_name'] : null,
                description: $row['description'] !== null ? (string) $row['description'] : null,
                is_active: (bool) $row['is_active']
            );
        }

        return new AdminRolesQueryResponseDTO(
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
