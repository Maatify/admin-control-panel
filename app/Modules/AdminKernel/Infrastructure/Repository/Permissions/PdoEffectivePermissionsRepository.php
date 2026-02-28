<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\Permissions;

use Maatify\AdminKernel\Domain\Contracts\Permissions\EffectivePermissionsRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\DTO\Permissions\EffectivePermissionListItemDTO;
use Maatify\AdminKernel\Domain\DTO\Permissions\EffectivePermissionsQueryResponseDTO;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\List\Filters\ResolvedListFilters;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use PDO;
use RuntimeException;

final readonly class PdoEffectivePermissionsRepository implements EffectivePermissionsRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function queryEffectivePermissionsForAdmin(
        int $adminId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): EffectivePermissionsQueryResponseDTO {

        // ─────────────────────────────
        // Ensure admin exists
        // ─────────────────────────────
        $stmt = $this->pdo->prepare('SELECT 1 FROM admins WHERE id = :id');
        $stmt->execute(['id' => $adminId]);

        if ($stmt->fetchColumn() === false) {
            throw new EntityNotFoundException('admin', $adminId);
        }

        $where  = [];
        $params = ['admin_id' => $adminId];

        // ─────────────────────────────
        // Global search
        // ─────────────────────────────
        if ($filters->globalSearch !== null && trim($filters->globalSearch) !== '') {
            $where[] = '(
                p.name LIKE :g
                OR p.display_name LIKE :g
                OR p.description LIKE :g
                OR SUBSTRING_INDEX(p.name, ".", 1) LIKE :g
            )';
            $params['g'] = '%' . trim($filters->globalSearch) . '%';
        }

        // ─────────────────────────────
        // Column filters
        // ─────────────────────────────
        foreach ($filters->columnFilters as $alias => $value) {
            if ($alias === 'name') {
                $where[] = 'p.name LIKE :name';
                $params['name'] = '%' . (string)$value . '%';
            }

            if ($alias === 'group') {
                $where[] = 'SUBSTRING_INDEX(p.name, ".", 1) = :group_name';
                $params['group_name'] = (string)$value;
            }
        }

        $whereSql = $where ? ' AND ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total (effective only)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->prepare(
            "
            SELECT COUNT(*)
            FROM permissions p
            WHERE
                EXISTS (
                    SELECT 1
                    FROM admin_direct_permissions adp
                    WHERE adp.permission_id = p.id
                      AND adp.admin_id = :admin_id
                      AND (adp.expires_at IS NULL OR adp.expires_at > NOW())
                )
                OR EXISTS (
                    SELECT 1
                    FROM role_permissions rp
                    INNER JOIN admin_roles ar
                        ON ar.role_id = rp.role_id
                    WHERE rp.permission_id = p.id
                      AND ar.admin_id = :admin_id
                )
            "
        );

        if ($stmtTotal->execute(['admin_id' => $adminId]) === false) {
            throw new RuntimeException('Failed to count effective permissions');
        }

        $total = (int)$stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "
            SELECT COUNT(*)
            FROM permissions p
            WHERE
                (
                    EXISTS (
                        SELECT 1
                        FROM admin_direct_permissions adp
                        WHERE adp.permission_id = p.id
                          AND adp.admin_id = :admin_id
                          AND (adp.expires_at IS NULL OR adp.expires_at > NOW())
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM role_permissions rp
                        INNER JOIN admin_roles ar
                            ON ar.role_id = rp.role_id
                        WHERE rp.permission_id = p.id
                          AND ar.admin_id = :admin_id
                    )
                )
                {$whereSql}
            "
        );

        $stmtFiltered->execute($params);
        $filtered = (int)$stmtFiltered->fetchColumn();

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

                adp.is_allowed   AS direct_allowed,
                adp.expires_at   AS direct_expires,

                r.name           AS role_name

            FROM permissions p

            LEFT JOIN admin_direct_permissions adp
                ON adp.permission_id = p.id
               AND adp.admin_id = :admin_id
               AND (adp.expires_at IS NULL OR adp.expires_at > NOW())

            LEFT JOIN role_permissions rp
                ON rp.permission_id = p.id

            LEFT JOIN admin_roles ar
                ON ar.role_id = rp.role_id
               AND ar.admin_id = :admin_id

            LEFT JOIN roles r
                ON r.id = ar.role_id

            WHERE
                (
                    adp.id IS NOT NULL
                    OR ar.admin_id IS NOT NULL
                )
                {$whereSql}

            GROUP BY p.id
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
            throw new RuntimeException('Failed to query effective permissions');
        }

        /**
         * @var array<int, array{
         *   id:int,
         *   name:string,
         *   display_name:string|null,
         *   description:string|null,
         *   direct_allowed:int|null,
         *   direct_expires:string|null,
         *   role_name:string|null
         * }> $rows
         */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];

        foreach ($rows as $row) {
            $name  = $row['name'];
            $group = explode('.', $name, 2)[0];

            if ($row['direct_allowed'] !== null) {
                $source    = $row['direct_allowed'] === 1 ? 'direct_allow' : 'direct_deny';
                $isAllowed = $row['direct_allowed'] === 1;
            } else {
                $source    = 'role';
                $isAllowed = true;
            }

            $items[] = new EffectivePermissionListItemDTO(
                id: $row['id'],
                name: $name,
                group: $group,
                display_name: $row['display_name'],
                description: $row['description'],
                source: $source,
                role_name: $row['role_name'],
                is_allowed: $isAllowed,
                expires_at: $row['direct_expires']
            );
        }

        return new EffectivePermissionsQueryResponseDTO(
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
