<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 22:28
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Infrastructure\Reader\ActivityLog;

use App\Domain\DTO\ActivityLog\ActivityLogListItemDTO;
use App\Domain\DTO\ActivityLog\ActivityLogListResponseDTO;
use App\Domain\DTO\Common\PaginationDTO;
use App\Domain\List\ListQueryDTO;
use App\Domain\ActivityLog\Reader\ActivityLogListReaderInterface;
use App\Infrastructure\Query\ResolvedListFilters;
use PDO;
use RuntimeException;

final readonly class PdoActivityLogListReader implements ActivityLogListReaderInterface
{
    public function __construct(
        private PDO $pdo
    )
    {
    }

    public function getActivityLogs(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): ActivityLogListResponseDTO
    {
        $where = [];
        $params = [];

        // ─────────────────────────────
        // Global search (action OR request_id)
        // ─────────────────────────────
        if ($filters->globalSearch !== null) {
            $where[] = '(al.action LIKE :global OR al.request_id LIKE :global OR ip_address LIKE :global)';
            $params['global'] = '%' . $filters->globalSearch . '%';
        }

        // ─────────────────────────────
        // Column filters
        // ─────────────────────────────
        foreach ($filters->columnFilters as $column => $value) {
            if ($column === 'actor_type') {
                $where[] = 'al.actor_type = :actor_type';
                $params['actor_type'] = (string)$value;
            }

            if ($column === 'actor_id') {
                $where[] = 'al.actor_id = :actor_id';
                $params['actor_id'] = (int)$value;
            }

            if ($column === 'action') {
                $where[] = 'al.action LIKE :action';
                $params['action'] = '%' . $value . '%';
            }

            if ($column === 'entity_type') {
                $where[] = 'al.entity_type = :entity_type';
                $params['entity_type'] = (string)$value;
            }

            if ($column === 'entity_id') {
                $where[] = 'al.entity_id = :entity_id';
                $params['entity_id'] = (int)$value;
            }

            if ($column === 'request_id') {
                $where[] = 'al.request_id LIKE :request_id';
                $params['request_id'] = '%' . $value . '%';
            }

            if ($column === 'ip_address') {
                $where[] = 'al.ip_address LIKE :ip_address';
                $params['ip_address'] = '%' . $value . '%';
            }
        }

        // ─────────────────────────────
        // Date range (occurred_at)
        // ─────────────────────────────
        if ($filters->dateFrom !== null) {
            $where[] = 'al.occurred_at >= :date_from';
            $params['date_from'] = $filters->dateFrom->format('Y-m-d 00:00:00');
        }

        if ($filters->dateTo !== null) {
            $where[] = 'al.occurred_at <= :date_to';
            $params['date_to'] = $filters->dateTo->format('Y-m-d 23:59:59');
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ─────────────────────────────
        // Total (no filters)
        // ─────────────────────────────
        $totalStmt = $this->pdo->query('SELECT COUNT(*) FROM operational_activity');

        if ($totalStmt === false) {
            throw new RuntimeException('Failed to execute total count query');
        }

        $total = (int)$totalStmt->fetchColumn();

        // ─────────────────────────────
        // Filtered
        // ─────────────────────────────
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(*) FROM operational_activity al {$whereSql}"
        );
        $stmtFiltered->execute($params);
        $filtered = (int)$stmtFiltered->fetchColumn();

        // ─────────────────────────────
        // Data
        // ─────────────────────────────
        $limit = $query->perPage;
        $offset = ($query->page - 1) * $limit;

        $sql = "
            SELECT
                al.id,
                al.action,
                al.actor_type,
                al.actor_id,
                al.entity_type,
                al.entity_id,
                al.metadata,
                al.ip_address,
                al.user_agent,
                al.request_id,
                al.occurred_at
            FROM operational_activity al
            {$whereSql}
            ORDER BY al.occurred_at DESC
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

        foreach ($rows ? : [] as $row) {
            $metadata = null;

            if ($row['metadata'] !== null) {
                $decoded = json_decode((string) $row['metadata'], true);

                if (is_array($decoded)) {
                    /** @var array<string, mixed> $decoded */
                    $metadata = $decoded;
                }
            }

            $items[] = new ActivityLogListItemDTO(
                id          : (int) $row['id'],
                action      : (string) $row['action'],

                actor_type  : (string) $row['actor_type'],
                actor_id    : $row['actor_id'] !== null ? (int) $row['actor_id'] : null,

                entity_type : $row['entity_type'] !== null ? (string) $row['entity_type'] : null,
                entity_id   : $row['entity_id'] !== null ? (int) $row['entity_id'] : null,

                metadata    : $metadata,

                ip_address  : $row['ip_address'] !== null ? (string) $row['ip_address'] : null,
                user_agent  : $row['user_agent'] !== null ? (string) $row['user_agent'] : null,

                request_id  : $row['request_id'] !== null ? (string) $row['request_id'] : null,

                occurred_at : (string) $row['occurred_at']
            );
        }

        return new ActivityLogListResponseDTO(
            data      : $items,
            pagination: new PaginationDTO(
                page    : $query->page,
                perPage : $query->perPage,
                total   : $total,
                filtered: $filtered
            )
        );
    }
}
