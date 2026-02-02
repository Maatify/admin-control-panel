<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository\Permissions;

use Maatify\AdminKernel\Domain\Contracts\Permissions\DirectPermissionsAssignableRepositoryInterface;
use Maatify\AdminKernel\Domain\DTO\Common\PaginationDTO;
use Maatify\AdminKernel\Domain\DTO\Permissions\DirectPermissionsAssignableListItemDTO;
use Maatify\AdminKernel\Domain\DTO\Permissions\DirectPermissionsAssignableQueryResponseDTO;
use Maatify\AdminKernel\Domain\Exception\EntityNotFoundException;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;
use PDO;
use RuntimeException;

final readonly class PdoDirectPermissionsAssignableRepository implements DirectPermissionsAssignableRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function queryAssignablePermissionsForAdmin(
        int $adminId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): DirectPermissionsAssignableQueryResponseDTO {

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
        // Global search (optional)
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
        // Column filters (optional)
        // ─────────────────────────────
        foreach ($filters->columnFilters ?? [] as $alias => $value) {

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

            if ($alias === 'assigned') {
                $where[] = ((int) $value === 1)
                    ? 'adp.id IS NOT NULL'
                    : 'adp.id IS NULL';
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total (ALL permissions)
        // ─────────────────────────────
        $stmtTotal = $this->pdo->prepare('SELECT COUNT(*) FROM permissions');

        if ($stmtTotal === false || $stmtTotal->execute() === false) {
            throw new RuntimeException('Failed to count assignable permissions');
        }

        $total = (int) $stmtTotal->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "
            SELECT COUNT(*)
            FROM permissions p
            LEFT JOIN admin_direct_permissions adp
                ON adp.permission_id = p.id
               AND adp.admin_id = :admin_id
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
                adp.expires_at
            FROM permissions p
            LEFT JOIN admin_direct_permissions adp
                ON adp.permission_id = p.id
               AND adp.admin_id = :admin_id
            {$whereSql}
            ORDER BY SUBSTRING_INDEX(p.name,'.', 1), p.name
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        if ($stmt->execute() === false) {
            throw new RuntimeException('Failed to query assignable permissions');
        }

        /** @var array<int,array{
         *  id:int,
         *  name:string,
         *  display_name:string|null,
         *  description:string|null,
         *  is_allowed:int|null,
         *  expires_at:string|null
         * }> $rows
         */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];

        foreach ($rows as $row) {
            $group = explode('.', $row['name'], 2)[0];

            $items[] = new DirectPermissionsAssignableListItemDTO(
                id: $row['id'],
                name: $row['name'],
                group: $group,
                display_name: $row['display_name'],
                description: $row['description'],
                assigned: $row['is_allowed'] !== null,
                is_allowed: $row['is_allowed'] !== null ? $row['is_allowed'] === 1 : null,
                expires_at: $row['expires_at']
            );
        }

        return new DirectPermissionsAssignableQueryResponseDTO(
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
