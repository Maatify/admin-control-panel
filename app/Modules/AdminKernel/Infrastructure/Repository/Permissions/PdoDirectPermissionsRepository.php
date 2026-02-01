<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\Permissions;

use Maatify\AdminKernel\Domain\Contracts\Permissions\DirectPermissionsRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\DTO\Permissions\DirectPermissionListItemDTO;
use Maatify\AdminKernel\Domain\DTO\Permissions\DirectPermissionsQueryResponseDTO;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;
use PDO;
use RuntimeException;

final readonly class PdoDirectPermissionsRepository implements DirectPermissionsRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function queryDirectPermissionsForAdmin(
        int $adminId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): DirectPermissionsQueryResponseDTO {

        // ─────────────────────────────
        // Ensure admin exists
        // ─────────────────────────────
        $stmt = $this->pdo->prepare('SELECT 1 FROM admins WHERE id = :id');
        $stmt->execute(['id' => $adminId]);

        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('admin', $adminId);
        }

        $where  = ['adp.admin_id = :admin_id'];
        $params = ['admin_id' => $adminId];

        // ─────────────────────────────
        // Global search (permission name only)
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $g = trim($filters->globalSearch);
            if ($g !== '') {
                $where[] = 'p.name LIKE :g';
                $params['g'] = '%' . $g . '%';
            }
        }

        // ─────────────────────────────
        // Column filters
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {

            if ($alias === 'id') {
                $where[] = 'p.id = :pid';
                $params['pid'] = (int) $value;
            }

            if ($alias === 'name') {
                $where[] = 'p.name LIKE :name';
                $params['name'] = '%' . (string) $value . '%';
            }

            if ($alias === 'group') {
                $where[] = 'SUBSTRING_INDEX(p.name, ".", 1) = :group_name';
                $params['group_name'] = (string) $value;
            }

            if ($alias === 'is_allowed') {
                $where[] = 'adp.is_allowed = :is_allowed';
                $params['is_allowed'] = (int) $value;
            }
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // ─────────────────────────────
        // Total (no filters except admin)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->prepare(
            '
            SELECT COUNT(*)
            FROM admin_direct_permissions
            WHERE admin_id = :admin_id
            '
        );

        if ($stmtTotal->execute(['admin_id' => $adminId]) === false) {
            throw new RuntimeException('Failed to count direct permissions');
        }

        $total = (int) $stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "
            SELECT COUNT(*)
            FROM admin_direct_permissions adp
            INNER JOIN permissions p ON p.id = adp.permission_id
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
                p.id,
                p.name,
                p.display_name,
                p.description,
                adp.is_allowed,
                adp.expires_at,
                adp.granted_at
            FROM admin_direct_permissions adp
            INNER JOIN permissions p ON p.id = adp.permission_id
            {$whereSql}
            ORDER BY
                SUBSTRING_INDEX(p.name, '.', 1),
                p.name
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        if ($stmt->execute() === false) {
            throw new RuntimeException('Failed to query direct permissions');
        }

        $items = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {

            $name  = (string) $row['name'];
            $group = explode('.', $name, 2)[0];

            $items[] = new DirectPermissionListItemDTO(
                id: (int) $row['id'],
                name: $name,
                group: $group,
                display_name: $row['display_name'] !== null ? (string) $row['display_name'] : null,
                description: $row['description'] !== null ? (string) $row['description'] : null,
                is_allowed: (bool) $row['is_allowed'],
                expires_at: $row['expires_at'],
                granted_at: (string) $row['granted_at']
            );
        }

        return new DirectPermissionsQueryResponseDTO(
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
