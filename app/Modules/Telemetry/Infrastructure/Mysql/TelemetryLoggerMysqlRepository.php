<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 13:09
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Telemetry\Infrastructure\Mysql;

use App\Modules\Telemetry\Contracts\TelemetryLoggerInterface;
use App\Modules\Telemetry\DTO\TelemetryEventDTO;
use App\Modules\Telemetry\Exceptions\TelemetryStorageException;
use PDO;
use Throwable;

/**
 * MySQL Telemetry storage adapter (write-side only).
 *
 * RULES:
 * - INSERT only
 * - No query/read responsibilities
 * - No swallowing (throw TelemetryStorageException)
 */
final readonly class TelemetryLoggerMysqlRepository implements TelemetryLoggerInterface
{
    public function __construct(
        private PDO $pdo
    )
    {
    }

    public function insert(TelemetryEventDTO $dto): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO telemetry_traces (
                    actor_type,
                    actor_id,
                    event_type,
                    severity,
                    request_id,
                    route_name,
                    ip_address,
                    user_agent,
                    metadata,
                    occurred_at
                ) VALUES (
                    :actor_type,
                    :actor_id,
                    :event_type,
                    :severity,
                    :request_id,
                    :route_name,
                    :ip_address,
                    :user_agent,
                    :metadata,
                    :occurred_at
                )'
            );

            $metadataJson = $dto->metadata === [] ? null : json_encode($dto->metadata, JSON_THROW_ON_ERROR);

            $stmt->execute([
                ':actor_type'  => $dto->actorType,
                ':actor_id'    => $dto->actorId,
                ':event_type'  => $dto->eventType->value,
                ':severity'    => $dto->severity->value,
                ':request_id'  => $dto->requestId,
                ':route_name'  => $dto->routeName,
                ':ip_address'  => $dto->ipAddress,
                ':user_agent'  => $dto->userAgent,
                ':metadata'    => $metadataJson,
                ':occurred_at' => $dto->occurredAt->format('Y-m-d H:i:s.u'),
            ]);
        } catch (Throwable $e) {
            throw new TelemetryStorageException('Telemetry storage failed (mysql insert).', 0, $e);
        }
    }
}
