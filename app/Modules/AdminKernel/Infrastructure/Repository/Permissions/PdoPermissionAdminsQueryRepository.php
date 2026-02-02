<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\Permissions;

use Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionAdminsQueryRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\DTO\Permissions\PermissionAdminOverrideListItemDTO;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;
use PDO;
use RuntimeException;

final readonly class PdoPermissionAdminsQueryRepository implements PermissionAdminsQueryRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function queryAdminsForPermission(
        int $permissionId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): array {

        // ─────────────────────────────
        // Ensure permission exists
        // ─────────────────────────────
        $stmtPermission = $this->pdo->prepare(
            'SELECT 1 FROM permissions WHERE id = :id'
        );
        $stmtPermission->execute(['id' => $permissionId]);

        if ($stmtPermission->fetchColumn() === false) {
            throw new EntityNotFoundException('permission', $permissionId);
        }

        $where  = ['adp.permission_id = :pid'];
        $params = ['pid' => $permissionId];

        // ─────────────────────────────
        // Global search
        // ─────────────────────────────
        if ($filters->globalSearch !== null && trim($filters->globalSearch) !== '') {
            $where[] = 'a.display_name LIKE :g';
            $params['g'] = '%' . trim($filters->globalSearch) . '%';
        }

        // ─────────────────────────────
        // Column filters
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {
            if ($alias === 'admin_id') {
                $where[] = 'adp.admin_id = :admin_id';
                $params['admin_id'] = (int) $value;
            }

            if ($alias === 'is_allowed') {
                $where[] = 'adp.is_allowed = :is_allowed';
                $params['is_allowed'] = (int) $value;
            }
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // ─────────────────────────────
        // Total (same scope as data, no filters)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->prepare(
            "
            SELECT COUNT(*)
            FROM admin_direct_permissions adp
            INNER JOIN admins a ON a.id = adp.admin_id
            WHERE adp.permission_id = :pid
            "
        );

        if ($stmtTotal->execute(['pid' => $permissionId]) === false) {
            throw new RuntimeException('Failed to count total admins');
        }

        $total = (int) $stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "
            SELECT COUNT(*)
            FROM admin_direct_permissions adp
            INNER JOIN admins a ON a.id = adp.admin_id
            {$whereSql}
            "
        );

        if ($stmtFiltered->execute($params) === false) {
            throw new RuntimeException('Failed to count filtered admins');
        }

        $filtered = (int) $stmtFiltered->fetchColumn();

        // ─────────────────────────────
        // Data
        // ─────────────────────────────
        $limit  = $query->perPage;
        $offset = ($query->page - 1) * $limit;

        $stmt = $this->pdo->prepare(
            "
            SELECT
                adp.admin_id,
                a.display_name AS admin_display_name,
                adp.is_allowed,
                adp.expires_at,
                adp.granted_at
            FROM admin_direct_permissions adp
            INNER JOIN admins a ON a.id = adp.admin_id
            {$whereSql}
            ORDER BY adp.granted_at DESC
            LIMIT :limit OFFSET :offset
            "
        );

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        if ($stmt->execute() === false) {
            throw new RuntimeException('Failed to query admins for permission');
        }

        /** @var array<int,array{
         *  admin_id:int,
         *  admin_display_name:string,
         *  is_allowed:int,
         *  expires_at:string|null,
         *  granted_at:string
         * }> $rows
         */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];

        foreach ($rows as $row) {
            $items[] = new PermissionAdminOverrideListItemDTO(
                admin_id: (int) $row['admin_id'],
                admin_display_name: (string) $row['admin_display_name'],
                is_allowed: (bool) $row['is_allowed'],
                expires_at: $row['expires_at'],
                granted_at: (string) $row['granted_at'],
            );
        }

        return [
            'data' => $items,
            'pagination' => new PaginationDTO(
                page: $query->page,
                perPage: $query->perPage,
                total: $total,
                filtered: $filtered
            ),
        ];
    }
}
