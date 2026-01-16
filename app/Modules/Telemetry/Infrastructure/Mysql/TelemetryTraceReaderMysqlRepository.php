<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-16 11:34
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Telemetry\Infrastructure\Mysql;

use App\Modules\Telemetry\Contracts\TelemetryTraceReaderInterface;
use App\Modules\Telemetry\DTO\TelemetryTraceReadDTO;
use App\Modules\Telemetry\DTO\TelemetryTraceReadPageDTO;
use App\Modules\Telemetry\DTO\TelemetryTraceReadQueryDTO;
use PDO;

final class TelemetryTraceReaderMysqlRepository implements TelemetryTraceReaderInterface
{
    private const TABLE = 'telemetry_traces';

    public function __construct(
        private PDO $pdo
    )
    {
    }

    public function paginate(TelemetryTraceReadQueryDTO $query): TelemetryTraceReadPageDTO
    {
        $page = max(1, $query->page);
        $perPage = max(1, min(100, $query->perPage));
        $offset = ($page - 1) * $perPage;

        $total = $this->count($query);

        [$whereSql, $params] = $this->buildWhere($query);

        $sql =
            'SELECT id, event_key, severity, route_name, request_id, actor_admin_id, ip_address, user_agent, metadata, occurred_at ' .
            'FROM `' . self::TABLE . '` ' .
            ($whereSql !== '' ? 'WHERE ' . $whereSql . ' ' : '') .
            'ORDER BY occurred_at DESC, id DESC ' .
            'LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ? : [];

        $data = [];
        foreach ($rows as $row) {
            $data[] = $this->mapRow($row);
        }

        return new TelemetryTraceReadPageDTO(
            data   : $data,
            page   : $page,
            perPage: $perPage,
            total  : $total
        );
    }

    public function count(TelemetryTraceReadQueryDTO $query): int
    {
        [$whereSql, $params] = $this->buildWhere($query);

        $sql =
            'SELECT COUNT(*) AS cnt FROM `' . self::TABLE . '` ' .
            ($whereSql !== '' ? 'WHERE ' . $whereSql : '');

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        $stmt->execute();

        $cnt = $stmt->fetchColumn();

        return is_numeric($cnt) ? (int)$cnt : 0;
    }

    /**
     * @return array{0:string,1:array<string, mixed>}
     */
    private function buildWhere(TelemetryTraceReadQueryDTO $q): array
    {
        $clauses = [];
        $params = [];

        // Exact filters (NO metadata filtering)
        if (is_string($q->eventKey) && $q->eventKey !== '') {
            $clauses[] = 'event_key = :event_key';
            $params[':event_key'] = $q->eventKey;
        }

        if (is_string($q->severity) && $q->severity !== '') {
            $clauses[] = 'severity = :severity';
            $params[':severity'] = $q->severity;
        }

        if (is_string($q->routeName) && $q->routeName !== '') {
            $clauses[] = 'route_name = :route_name';
            $params[':route_name'] = $q->routeName;
        }

        if (is_string($q->requestId) && $q->requestId !== '') {
            $clauses[] = 'request_id = :request_id';
            $params[':request_id'] = $q->requestId;
        }

        if (is_int($q->actorAdminId)) {
            $clauses[] = 'actor_admin_id = :actor_admin_id';
            $params[':actor_admin_id'] = $q->actorAdminId;
        }

        // Range filters
        if ($q->occurredFrom instanceof \DateTimeImmutable) {
            $clauses[] = 'occurred_at >= :occurred_from';
            $params[':occurred_from'] = $q->occurredFrom->format('Y-m-d H:i:s.u');
        }

        if ($q->occurredTo instanceof \DateTimeImmutable) {
            $clauses[] = 'occurred_at <= :occurred_to';
            $params[':occurred_to'] = $q->occurredTo->format('Y-m-d H:i:s.u');
        }

        return [implode(' AND ', $clauses), $params];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @throws \App\Modules\Telemetry\Exceptions\TelemetryTraceRowMappingException
     */
    private function mapRow(array $row): TelemetryTraceReadDTO
    {
        if (!isset($row['id']) || !is_numeric($row['id'])) {
            throw new \App\Modules\Telemetry\Exceptions\TelemetryTraceRowMappingException('Invalid telemetry_traces.id');
        }

        if (!isset($row['event_key']) || !is_string($row['event_key']) || $row['event_key'] === '') {
            throw new \App\Modules\Telemetry\Exceptions\TelemetryTraceRowMappingException('Invalid telemetry_traces.event_key');
        }

        if (!isset($row['severity']) || !is_string($row['severity']) || $row['severity'] === '') {
            throw new \App\Modules\Telemetry\Exceptions\TelemetryTraceRowMappingException('Invalid telemetry_traces.severity');
        }

        if (!isset($row['occurred_at']) || !is_string($row['occurred_at']) || $row['occurred_at'] === '') {
            throw new \App\Modules\Telemetry\Exceptions\TelemetryTraceRowMappingException('Invalid telemetry_traces.occurred_at');
        }

        $metadata = null;

        if (isset($row['metadata']) && is_string($row['metadata']) && $row['metadata'] !== '') {
            $decoded = json_decode($row['metadata'], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        try {
            $occurredAt = new \DateTimeImmutable($row['occurred_at']);
        } catch (\Throwable $e) {
            throw new \App\Modules\Telemetry\Exceptions\TelemetryTraceRowMappingException(
                'Invalid telemetry_traces.occurred_at',
                $e
            );
        }

        $routeName = (isset($row['route_name']) && is_string($row['route_name'])) ? $row['route_name'] : null;
        $requestId = (isset($row['request_id']) && is_string($row['request_id'])) ? $row['request_id'] : null;

        $actorAdminId = null;
        if (isset($row['actor_admin_id']) && is_numeric($row['actor_admin_id'])) {
            $actorAdminId = (int) $row['actor_admin_id'];
        }

        $ipAddress = (isset($row['ip_address']) && is_string($row['ip_address'])) ? $row['ip_address'] : null;
        $userAgent = (isset($row['user_agent']) && is_string($row['user_agent'])) ? $row['user_agent'] : null;

        return new TelemetryTraceReadDTO(
            id: (int) $row['id'],
            eventKey: $row['event_key'],
            severity: $row['severity'],
            routeName: $routeName,
            requestId: $requestId,
            actorAdminId: $actorAdminId,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            metadata: $metadata,
            occurredAt: $occurredAt
        );
    }

}
