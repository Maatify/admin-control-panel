<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\Permissions;

use Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionRolesQueryRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\DTO\Permissions\PermissionRoleListItemDTO;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;
use PDO;
use RuntimeException;

final readonly class PdoPermissionRolesQueryRepository implements PermissionRolesQueryRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function queryRolesForPermission(
        int $permissionId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): array {

        // ─────────────────────────────
        // Ensure permission exists
        // ─────────────────────────────
        $stmt = $this->pdo->prepare('SELECT 1 FROM permissions WHERE id = :id');
        $stmt->execute(['id' => $permissionId]);

        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('permission', $permissionId);
        }

        $where  = [];
        $params = ['permission_id' => $permissionId];

        // ─────────────────────────────
        // Global search
        // ─────────────────────────────
        if ($filters->globalSearch !== null && trim($filters->globalSearch) !== '') {
            $where[] = '(
                r.name LIKE :g
                OR r.display_name LIKE :g
                OR r.description LIKE :g
                OR SUBSTRING_INDEX(r.name, ".", 1) LIKE :g
            )';
            $params['g'] = '%' . trim($filters->globalSearch) . '%';
        }

        // ─────────────────────────────
        // Column filters
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {
            if ($alias === 'id') {
                $where[] = 'r.id = :rid';
                $params['rid'] = (int) $value;
            }

            if ($alias === 'name') {
                $where[] = 'r.name LIKE :name';
                $params['name'] = '%' . (string) $value . '%';
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

        $whereSql = $where ? 'AND ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total = all roles linked to permission (ignores filters by design)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->prepare(
            '
            SELECT COUNT(*)
            FROM role_permissions
            WHERE permission_id = :permission_id
            '
        );

        if ($stmtTotal === false || $stmtTotal->execute(['permission_id' => $permissionId]) === false) {
            throw new RuntimeException('Failed to count permission roles');
        }

        $total = (int) $stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "
            SELECT COUNT(*)
            FROM role_permissions rp
            INNER JOIN roles r ON r.id = rp.role_id
            WHERE rp.permission_id = :permission_id
            {$whereSql}
            "
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
                r.is_active
            FROM role_permissions rp
            INNER JOIN roles r ON r.id = rp.role_id
            WHERE rp.permission_id = :permission_id
            {$whereSql}
            ORDER BY r.name
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        if ($stmt->execute() === false) {
            throw new RuntimeException('Failed to query permission roles');
        }

        $items = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $name  = (string) $row['name'];
            $group = explode('.', $name, 2)[0];
            $items[] = new PermissionRoleListItemDTO(
                role_id: (int) $row['id'],
                role_name: (string) $row['name'],
                group: $group,
                display_name: $row['display_name'],
                is_active: (bool) $row['is_active'],
            );
        }

        return [
            'data' => $items,
            'pagination' => new PaginationDTO(
                page: $query->page,
                perPage: $query->perPage,
                total: $total,
                filtered: $filtered
            )
        ];
    }
}
