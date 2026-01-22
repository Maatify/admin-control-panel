<?php

declare(strict_types=1);

namespace App\Modules\DiagnosticsTelemetry\Infrastructure\Mysql;

use App\Modules\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryPolicyInterface;
use App\Modules\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryQueryInterface;
use App\Modules\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryContextDTO;
use App\Modules\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryCursorDTO;
use App\Modules\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryEventDTO;
use App\Modules\DiagnosticsTelemetry\Exception\DiagnosticsTelemetryStorageException;
use App\Modules\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryDefaultPolicy;
use DateTimeImmutable;
use PDO;
use PDOException;
use Exception;
use JsonException;

class DiagnosticsTelemetryQueryMysqlRepository implements DiagnosticsTelemetryQueryInterface
{
    private const TABLE_NAME = 'diagnostics_telemetry';

    private readonly DiagnosticsTelemetryPolicyInterface $policy;

    public function __construct(
        private readonly PDO $pdo,
        ?DiagnosticsTelemetryPolicyInterface $policy = null
    ) {
        $this->policy = $policy ?? new DiagnosticsTelemetryDefaultPolicy();
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
            $sql .= ' AND (occurred_at > :last_occurred_at OR (occurred_at = :last_occurred_at_eq AND id > :last_id))';
            $params[':last_occurred_at'] = $cursor->lastOccurredAt->format('Y-m-d H:i:s.u');
            $params[':last_occurred_at_eq'] = $cursor->lastOccurredAt->format('Y-m-d H:i:s.u');
            $params[':last_id'] = $cursor->lastId;
        }

        $sql .= ' ORDER BY occurred_at ASC, id ASC LIMIT :limit';

        try {
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                /** @var array<string, mixed> $row */
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
        $severityStr = isset($row['severity']) ? (string)$row['severity'] : 'INFO';
        $severity = $this->policy->normalizeSeverity($severityStr);

        $actorTypeStr = isset($row['actor_type']) ? (string)$row['actor_type'] : 'ANONYMOUS';
        $actorType = $this->policy->normalizeActorType($actorTypeStr);

        $metadata = null;
        if (isset($row['metadata']) && is_string($row['metadata']) && $row['metadata'] !== '') {
            try {
                $decoded = json_decode($row['metadata'], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $metadata = $decoded;
                }
            } catch (JsonException) {
                // Metadata corruption in DB; treat as null or partial?
                // Best effort: null.
                $metadata = null;
            }
        }

        $occurredAtStr = isset($row['occurred_at']) ? (string)$row['occurred_at'] : '1970-01-01 00:00:00';
        $eventId = isset($row['event_id']) ? (string)$row['event_id'] : '';
        $eventKey = isset($row['event_key']) ? (string)$row['event_key'] : 'unknown';

        $context = new DiagnosticsTelemetryContextDTO(
            actorType: $actorType,
            actorId: isset($row['actor_id']) ? (int)$row['actor_id'] : null,
            correlationId: isset($row['correlation_id']) ? (string)$row['correlation_id'] : null,
            requestId: isset($row['request_id']) ? (string)$row['request_id'] : null,
            routeName: isset($row['route_name']) ? (string)$row['route_name'] : null,
            ipAddress: isset($row['ip_address']) ? (string)$row['ip_address'] : null,
            userAgent: isset($row['user_agent']) ? (string)$row['user_agent'] : null,
            occurredAt: new DateTimeImmutable($occurredAtStr)
        );

        return new DiagnosticsTelemetryEventDTO(
            eventId: $eventId,
            eventKey: $eventKey,
            severity: $severity,
            context: $context,
            durationMs: isset($row['duration_ms']) ? (int)$row['duration_ms'] : null,
            metadata: $metadata
        );
    }
}
