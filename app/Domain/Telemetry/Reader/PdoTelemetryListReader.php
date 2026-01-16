<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-16 13:48
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Telemetry\Reader;

use App\Domain\DTO\Common\PaginationDTO;
use App\Domain\List\ListQueryDTO;
use App\Domain\Telemetry\Contracts\TelemetryListReaderInterface;
use App\Domain\Telemetry\DTO\TelemetryListItemDTO;
use App\Domain\Telemetry\DTO\TelemetryListResponseDTO;
use App\Infrastructure\Query\ResolvedListFilters;
use PDO;
use RuntimeException;

final readonly class PdoTelemetryListReader implements TelemetryListReaderInterface
{
    public function __construct(
        private PDO $pdo
    )
    {
    }

    public function getTelemetry(
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): TelemetryListResponseDTO
    {
        $where = [];
        $params = [];

        // ───────── Global Search (OR) ─────────
        if ($filters->globalSearch !== null) {
            $where[] = '(' . implode(' OR ', [
                    'event_key LIKE :global',
                    'route_name LIKE :global',
                    'request_id LIKE :global',
                ]) . ')';

            $params['global'] = '%' . $filters->globalSearch . '%';
        }

        // ───────── Column filters ─────────
        foreach ($filters->columnFilters as $column => $value) {
            if ($column === 'event_key') {
                $where[] = 'event_key = :event_key';
                $params['event_key'] = $value;
            }

            if ($column === 'route_name') {
                $where[] = 'route_name = :route_name';
                $params['route_name'] = $value;
            }

            if ($column === 'request_id') {
                $where[] = 'request_id = :request_id';
                $params['request_id'] = $value;
            }

            if ($column === 'actor_type') {
                $where[] = 'actor_type = :actor_type';
                $params['actor_type'] = $value;
            }

            if ($column === 'actor_id') {
                $where[] = 'actor_id = :actor_id';
                $params['actor_id'] = (int)$value;
            }

            if ($column === 'ip_address') {
                $where[] = 'ip_address = :ip_address';
                $params['ip_address'] = $value;
            }
        }

        // ───────── Date range ─────────
        if ($filters->dateFrom !== null) {
            $where[] = 'occurred_at >= :date_from';
            $params['date_from'] = $filters->dateFrom->format('Y-m-d H:i:s.u');
        }

        if ($filters->dateTo !== null) {
            $where[] = 'occurred_at <= :date_to';
            $params['date_to'] = $filters->dateTo->format('Y-m-d H:i:s.u');
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // ───────── Total ─────────
        $totalStmt = $this->pdo->query('SELECT COUNT(*) FROM telemetry_traces');
        if ($totalStmt === false) {
            throw new RuntimeException('Failed total count');
        }
        $total = (int)$totalStmt->fetchColumn();

        // ───────── Filtered ─────────
        $stmtFiltered = $this->pdo->prepare(
            "SELECT COUNT(*) FROM telemetry_traces {$whereSql}"
        );
        $stmtFiltered->execute($params);
        $filtered = (int)$stmtFiltered->fetchColumn();

        // ───────── Data ─────────
        $limit = $query->perPage;
        $offset = ($query->page - 1) * $limit;

        $sql = "
            SELECT
                id,
                event_key,
                severity,
                actor_type,
                actor_id,
                route_name,
                request_id,
                ip_address,
                occurred_at
            FROM telemetry_traces
            {$whereSql}
            ORDER BY occurred_at DESC
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
            $items[] = new TelemetryListItemDTO(
                id         : (int)$row['id'],
                event_key  : (string)$row['event_key'],
                severity   : (string)$row['severity'],
                actor_type : (string)$row['actor_type'],
                actor_id   : $row['actor_id'] !== null ? (int)$row['actor_id'] : null,
                route_name : $row['route_name'] !== null ? (string)$row['route_name'] : null,
                request_id : $row['request_id'] !== null ? (string)$row['request_id'] : null,
                ip_address : $row['ip_address'] !== null ? (string)$row['ip_address'] : null,
                occurred_at: (string)$row['occurred_at']
            );
        }

        return new TelemetryListResponseDTO(
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
