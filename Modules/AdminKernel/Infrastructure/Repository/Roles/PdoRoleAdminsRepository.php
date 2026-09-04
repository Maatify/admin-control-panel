<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\Roles;

use Maatify\AdminKernel\Domain\Contracts\Roles\RoleAdminsRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\DTO\Roles\RoleAdminListItemDTO;
use Maatify\AdminKernel\Domain\DTO\Roles\RoleAdminsQueryResponseDTO;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use PDO;
use RuntimeException;

readonly class PdoRoleAdminsRepository implements RoleAdminsRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    )
    {
    }

    public function assign(int $roleId, int $adminId): void
    {
        // Ensure role exists
        $stmt = $this->pdo->prepare('SELECT 1 FROM roles WHERE id = :id');
        $stmt->execute(['id' => $roleId]);
        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('role', $roleId);
        }

        // Ensure admin exists
        $stmt = $this->pdo->prepare('SELECT 1 FROM admins WHERE id = :id');
        $stmt->execute(['id' => $adminId]);
        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('admin', $adminId);
        }

        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO admin_roles (admin_id, role_id)
             VALUES (:admin_id, :role_id)'
        );

        if ($stmt->execute([
                'admin_id' => $adminId,
                'role_id'  => $roleId,
            ]) === false) {
            throw new RuntimeException('Failed to assign admin to role.');
        }
    }

    public function unassign(int $roleId, int $adminId): void
    {
        // Ensure role exists
        $stmt = $this->pdo->prepare('SELECT 1 FROM roles WHERE id = :id');
        $stmt->execute(['id' => $roleId]);
        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('role', $roleId);
        }

        $stmt = $this->pdo->prepare(
            'DELETE FROM admin_roles
             WHERE admin_id = :admin_id
               AND role_id  = :role_id'
        );

        if ($stmt->execute([
                'admin_id' => $adminId,
                'role_id'  => $roleId,
            ]) === false) {
            throw new RuntimeException('Failed to unassign admin from role.');
        }
    }

    public function queryAdminsForRole(
        int $roleId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): RoleAdminsQueryResponseDTO {

        // Ensure role exists
        $stmt = $this->pdo->prepare('SELECT 1 FROM roles WHERE id = :id');
        $stmt->execute(['id' => $roleId]);
        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('role', $roleId);
        }

        $where  = [];
        $params = ['role_id' => $roleId];

        // ─────────────────────────────
        // Global search (display_name OR status)
        // ─────────────────────────────
        if ($filters->globalSearch !== null && trim($filters->globalSearch) !== '') {
            $g = trim($filters->globalSearch);

            $where[] = '(a.display_name LIKE :g OR a.status = :g_status)';
            $params['g'] = '%' . $g . '%';
            $params['g_status'] = strtoupper($g);
        }

        // ─────────────────────────────
        // Column filters
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {

            if ($alias === 'id') {
                $where[] = 'a.id = :admin_id';
                $params['admin_id'] = (int) $value;
            }

            if ($alias === 'status') {
                $where[] = 'a.status = :status';
                $params['status'] = (string) $value;
            }

            if ($alias === 'assigned') {
                if ((int) $value === 1) {
                    $where[] = 'ar.admin_id IS NOT NULL';
                } else {
                    $where[] = 'ar.admin_id IS NULL';
                }
            }
        }

        // ─────────────────────────────
        // Date filter
        // ─────────────────────────────
        if ($filters->dateFrom !== null) {
            $where[] = 'a.created_at >= :date_from';
            $params['date_from'] = $filters->dateFrom->format('Y-m-d 00:00:00');
        }

        if ($filters->dateTo !== null) {
            $where[] = 'a.created_at <= :date_to';
            $params['date_to'] = $filters->dateTo->format('Y-m-d 23:59:59');
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total
        // ─────────────────────────────
        $stmtTotal = $this->pdo->query('SELECT COUNT(*) FROM admins');
        if ($stmtTotal === false) {
            throw new RuntimeException('Failed to count admins');
        }
        $total = (int) $stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM admins a
             LEFT JOIN admin_roles ar
                ON ar.admin_id = a.id
               AND ar.role_id = :role_id
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
                a.id,
                a.display_name,
                a.status,
                a.created_at,
                CASE WHEN ar.admin_id IS NULL THEN 0 ELSE 1 END AS assigned
            FROM admins a
            LEFT JOIN admin_roles ar
                ON ar.admin_id = a.id
               AND ar.role_id = :role_id
            {$whereSql}
            ORDER BY a.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $items[] = new RoleAdminListItemDTO(
                id: (int) $row['id'],
                display_name: $row['display_name'],
                status: $row['status'],
                assigned: (bool) $row['assigned']
            );
        }

        return new RoleAdminsQueryResponseDTO(
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
