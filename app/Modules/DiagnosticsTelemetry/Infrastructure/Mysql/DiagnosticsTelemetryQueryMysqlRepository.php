<?php

declare(strict_types=1);

namespace App\Modules\DiagnosticsTelemetry\Infrastructure\Mysql;

use App\Modules\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryQueryInterface;
use App\Modules\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryContextDTO;
use App\Modules\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryCursorDTO;
use App\Modules\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryEventDTO;
use App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetryActorTypeEnum;
use App\Modules\DiagnosticsTelemetry\Enum\DiagnosticsTelemetrySeverityEnum;
use App\Modules\DiagnosticsTelemetry\Exception\DiagnosticsTelemetryStorageException;
use App\Modules\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryDefaultPolicy;
use DateTimeImmutable;
use PDO;
use PDOException;
use Exception;

class DiagnosticsTelemetryQueryMysqlRepository implements DiagnosticsTelemetryQueryInterface
{
    private const TABLE_NAME = 'diagnostics_telemetry';

    public function __construct(
        private readonly PDO $pdo,
        private readonly DiagnosticsTelemetryDefaultPolicy $policy = new DiagnosticsTelemetryDefaultPolicy()
    ) {
    }

    /**
     * @return iterable<DiagnosticsTelemetryEventDTO>
     */
    public function read(?DiagnosticsTelemetryCursorDTO $cursor, int $limit = 100): iterable
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE 1=1',
            self::TABLE_NAME
        );

        $params = [];

        if ($cursor) {
            // Stable cursor paging: (occurred_at > last_time) OR (occurred_at = last_time AND id > last_id)
            $sql .= ' AND (occurred_at > :last_occurred_at OR (occurred_at = :last_occurred_at_eq AND id > :last_id))';
            $params[':last_occurred_at'] = $cursor->lastOccurredAt->format('Y-m-d H:i:s.u');
            $params[':last_occurred_at_eq'] = $cursor->lastOccurredAt->format('Y-m-d H:i:s.u');
            $params[':last_id'] = $cursor->lastId;
        }

        $sql .= ' ORDER BY occurred_at ASC, id ASC LIMIT :limit';
        // LIMIT in PDO is integer, not string, but some drivers need int.

        try {
            $stmt = $this->pdo->prepare($sql);

            // Bind limit as INT
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                yield $this->mapRowToDTO($row);
            }

        } catch (PDOException $e) {
             throw new DiagnosticsTelemetryStorageException('Failed to read telemetry logs: ' . $e->getMessage(), 0, $e);
        } catch (Exception $e) {
             throw new DiagnosticsTelemetryStorageException('Failed to map telemetry row: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return DiagnosticsTelemetryEventDTO
     * @throws Exception
     */
    private function mapRowToDTO(array $row): DiagnosticsTelemetryEventDTO
    {
        $severityEnum = DiagnosticsTelemetrySeverityEnum::tryFrom($row['severity']);
        $severity = $severityEnum ?? DiagnosticsTelemetrySeverityEnum::INFO;

        $actorType = $this->policy->normalizeActorType($row['actor_type']);

        $metadata = null;
        if (!empty($row['metadata'])) {
            $metadata = json_decode($row['metadata'], true);
        }

        $context = new DiagnosticsTelemetryContextDTO(
            actorType: $actorType,
            actorId: isset($row['actor_id']) ? (int)$row['actor_id'] : null,
            correlationId: $row['correlation_id'] ?? null,
            requestId: $row['request_id'] ?? null,
            routeName: $row['route_name'] ?? null,
            ipAddress: $row['ip_address'] ?? null,
            userAgent: $row['user_agent'] ?? null,
            occurredAt: new DateTimeImmutable($row['occurred_at'])
        );

        return new DiagnosticsTelemetryEventDTO(
            eventId: $row['event_id'],
            eventKey: $row['event_key'],
            severity: $severity,
            context: $context,
            durationMs: isset($row['duration_ms']) ? (int)$row['duration_ms'] : null,
            metadata: is_array($metadata) ? $metadata : null
        );
    }
}
