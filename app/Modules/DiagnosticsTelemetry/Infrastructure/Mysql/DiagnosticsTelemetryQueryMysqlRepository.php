<?php

declare(strict_types=1);

namespace Maatify\DiagnosticsTelemetry\Infrastructure\Mysql;

use Maatify\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryPolicyInterface;
use Maatify\DiagnosticsTelemetry\Contract\DiagnosticsTelemetryQueryInterface;
use Maatify\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryContextDTO;
use Maatify\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryCursorDTO;
use Maatify\DiagnosticsTelemetry\DTO\DiagnosticsTelemetryEventDTO;
use Maatify\DiagnosticsTelemetry\Exception\DiagnosticsTelemetryStorageException;
use Maatify\DiagnosticsTelemetry\Recorder\DiagnosticsTelemetryDefaultPolicy;
use DateTimeImmutable;
use DateTimeZone;
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
        $severityStr = is_string($row['severity'] ?? null) ? $row['severity'] : 'INFO';
        $severity = $this->policy->normalizeSeverity($severityStr);

        $actorTypeStr = is_string($row['actor_type'] ?? null) ? $row['actor_type'] : 'ANONYMOUS';
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

        $occurredAtStr = is_string($row['occurred_at'] ?? null) ? $row['occurred_at'] : '1970-01-01 00:00:00';
        $eventId = is_string($row['event_id'] ?? null) ? $row['event_id'] : '';
        $eventKey = is_string($row['event_key'] ?? null) ? $row['event_key'] : 'unknown';

        $context = new DiagnosticsTelemetryContextDTO(
            actorType: $actorType,
            actorId: is_int($row['actor_id'] ?? null) ? $row['actor_id'] : null,
            correlationId: is_string($row['correlation_id'] ?? null) ? $row['correlation_id'] : null,
            requestId: is_string($row['request_id'] ?? null) ? $row['request_id'] : null,
            routeName: is_string($row['route_name'] ?? null) ? $row['route_name'] : null,
            ipAddress: is_string($row['ip_address'] ?? null) ? $row['ip_address'] : null,
            userAgent: is_string($row['user_agent'] ?? null) ? $row['user_agent'] : null,
            occurredAt: new DateTimeImmutable($occurredAtStr, new DateTimeZone('UTC'))
        );

        return new DiagnosticsTelemetryEventDTO(
            eventId: $eventId,
            eventKey: $eventKey,
            severity: $severity,
            context: $context,
            durationMs: is_int($row['duration_ms'] ?? null) ? $row['duration_ms'] : null,
            metadata: $metadata
        );
    }
}