<?php

declare(strict_types=1);

namespace App\Modules\TelemetryLogging\Infrastructure;

use App\Modules\TelemetryLogging\Contract\TelemetryWriterInterface;
use App\Modules\TelemetryLogging\DTO\TelemetryEventDTO;
use App\Modules\TelemetryLogging\Exception\TelemetryStorageException;
use PDO;
use PDOException;
use JsonException;

class TelemetryMySqlWriter implements TelemetryWriterInterface
{
    private const TABLE_NAME = 'diagnostics_telemetry';

    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function write(TelemetryEventDTO $dto): void
    {
        $sql = sprintf(
            'INSERT INTO %s (
                event_id,
                event_key,
                severity,
                actor_type,
                actor_id,
                correlation_id,
                request_id,
                route_name,
                ip_address,
                user_agent,
                duration_ms,
                metadata,
                occurred_at
            ) VALUES (
                :event_id,
                :event_key,
                :severity,
                :actor_type,
                :actor_id,
                :correlation_id,
                :request_id,
                :route_name,
                :ip_address,
                :user_agent,
                :duration_ms,
                :metadata,
                :occurred_at
            )',
            self::TABLE_NAME
        );

        try {
            $metadataJson = $dto->metadata ? json_encode($dto->metadata, JSON_THROW_ON_ERROR) : null;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':event_id' => $dto->eventId,
                ':event_key' => $dto->eventKey,
                ':severity' => $dto->severity->value,
                ':actor_type' => $dto->context->actorType,
                ':actor_id' => $dto->context->actorId,
                ':correlation_id' => $dto->context->correlationId,
                ':request_id' => $dto->context->requestId,
                ':route_name' => $dto->context->routeName,
                ':ip_address' => $dto->context->ipAddress,
                ':user_agent' => $dto->context->userAgent,
                ':duration_ms' => $dto->durationMs,
                ':metadata' => $metadataJson,
                ':occurred_at' => $dto->context->occurredAt->format('Y-m-d H:i:s.u'),
            ]);
        } catch (PDOException $e) {
            throw new TelemetryStorageException('Failed to write telemetry log: ' . $e->getMessage(), 0, $e);
        } catch (JsonException $e) {
             throw new TelemetryStorageException('Failed to encode metadata: ' . $e->getMessage(), 0, $e);
        }
    }
}
