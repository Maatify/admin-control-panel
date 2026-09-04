<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\Roles;

use Maatify\AdminKernel\Domain\Contracts\Roles\RolePermissionsRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\DTO\Roles\RolePermissionListItemDTO;
use Maatify\AdminKernel\Domain\DTO\Roles\RolePermissionsQueryResponseDTO;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use PDO;
use RuntimeException;

readonly class PdoRolePermissionsRepository implements RolePermissionsRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    )
    {
    }

    public function assign(int $roleId, int $permissionId): void
    {
        // Ensure role exists
        $stmt = $this->pdo->prepare('SELECT 1 FROM roles WHERE id = :id');
        $stmt->execute(['id' => $roleId]);

        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('role', $roleId);
        }

        // Ensure permission exists
        $stmt = $this->pdo->prepare('SELECT 1 FROM permissions WHERE id = :id');
        $stmt->execute(['id' => $permissionId]);

        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('permission', $permissionId);
        }

        // Idempotent insert
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO role_permissions (role_id, permission_id)
             VALUES (:role_id, :permission_id)'
        );

        if ($stmt->execute([
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
            ]) === false) {
            throw new RuntimeException('Failed to assign permission to role.');
        }
    }

    public function unassign(int $roleId, int $permissionId): void
    {
        // Ensure role exists
        $stmt = $this->pdo->prepare('SELECT 1 FROM roles WHERE id = :id');
        $stmt->execute(['id' => $roleId]);

        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('role', $roleId);
        }

        $stmt = $this->pdo->prepare(
            'DELETE FROM role_permissions
             WHERE role_id = :role_id
               AND permission_id = :permission_id'
        );

        if ($stmt->execute([
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
            ]) === false) {
            throw new RuntimeException('Failed to unassign permission from role.');
        }
        // Idempotent by nature (0 rows affected is OK)
    }

    public function queryForRole(
        int $roleId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): RolePermissionsQueryResponseDTO
    {
        // Ensure role exists
        $stmt = $this->pdo->prepare('SELECT 1 FROM roles WHERE id = :id');
        $stmt->execute(['id' => $roleId]);

        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('role', $roleId);
        }

        $where  = [];
        $params = ['role_id' => $roleId];

        // Global search
        if ($filters->globalSearch !== null && trim($filters->globalSearch) !== '') {
            $where[] = 'p.name LIKE :global_name';
            $params['global_name'] = '%' . trim($filters->globalSearch) . '%';
        }

        // Column filters
        foreach ($filters->columnFilters as $alias => $value) {
            if ($alias === 'id') {
                $where[] = 'p.id = :pid';
                $params['pid'] = (int)$value;
            }

            if ($alias === 'name') {
                $where[] = 'p.name LIKE :name';
                $params['name'] = '%' . trim((string)$value) . '%';
            }

            if ($alias === 'group') {
                $where[] = 'SUBSTRING_INDEX(p.name, ".", 1) = :group';
                $params['group'] = (string)$value;
            }

            if ($alias === 'assigned') {
                if ((int)$value === 1) {
                    $where[] = 'rp.permission_id IS NOT NULL';
                } else {
                    $where[] = 'rp.permission_id IS NULL';
                }
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total
        $stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM permissions');

        if ($stmtTotal === false) {
            throw new RuntimeException('Failed to execute permissions total count query');
        }

        $total = (int)$stmtTotal->fetchColumn();

        // Filtered
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(*)
         FROM permissions p
         LEFT JOIN role_permissions rp
            ON rp.permission_id = p.id
           AND rp.role_id = :role_id
         {$whereSql}"
        );
        $stmtFiltered->execute($params);
        $filtered = (int)$stmtFiltered->fetchColumn();

        // Data
        $limit  = $query->perPage;
        $offset = ($query->page - 1) * $limit;

        $sql = "
        SELECT
            p.id,
            p.name,
            p.display_name,
            p.description,
            CASE WHEN rp.permission_id IS NULL THEN 0 ELSE 1 END AS assigned
        FROM permissions p
        LEFT JOIN role_permissions rp
            ON rp.permission_id = p.id
           AND rp.role_id = :role_id
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
            $items[] = new RolePermissionListItemDTO(
                id: (int)$row['id'],
                name: (string)$row['name'],
                group: $group,
                display_name: $row['display_name'] !== null ? (string)$row['display_name'] : null,
                description: $row['description'] !== null ? (string)$row['description'] : null,
                assigned: (bool)$row['assigned'],
            );
        }

        return new RolePermissionsQueryResponseDTO(
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
