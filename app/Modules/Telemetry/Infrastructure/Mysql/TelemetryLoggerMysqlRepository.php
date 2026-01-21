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
use App\Modules\Telemetry\Infrastructure\Contracts\TelemetryStorageInterface;
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
final readonly class TelemetryLoggerMysqlRepository  implements TelemetryLoggerInterface, TelemetryStorageInterface
{
    public function __construct(
        private PDO $pdo
    )
    {
    }

    /**
     * Module contract used by Domain recorder.
     *
     * @throws TelemetryStorageException
     */
    public function log(TelemetryEventDTO $dto): void
    {
        $this->store($dto);
    }

    /**
     * Low-level storage contract.
     *
     * @throws TelemetryStorageException
     */
    public function store(TelemetryEventDTO $event): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO telemetry_traces (
                    actor_type,
                    actor_id,
                    event_key,
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
                    :event_key,
                    :severity,
                    :request_id,
                    :route_name,
                    :ip_address,
                    :user_agent,
                    :metadata,
                    :occurred_at
                )'
            );

            $metadataJson = $event->metadata === [] ? null : json_encode($event->metadata, JSON_THROW_ON_ERROR);

            $stmt->execute([
                ':actor_type'  => $event->actorType,
                ':actor_id'    => $event->actorId,
                ':event_key'   => $event->eventType->value,
                ':severity'    => $event->severity->value,
                ':request_id'  => $event->requestId,
                ':route_name'  => $event->routeName,
                ':ip_address'  => $event->ipAddress,
                ':user_agent'  => $event->userAgent,
                ':metadata'    => $metadataJson,
                ':occurred_at' => $event->occurredAt->format('Y-m-d H:i:s.u'),
            ]);
        } catch (Throwable $e) {
            throw new TelemetryStorageException('Telemetry storage failed (mysql insert).', 0, $e);
        }
    }
}
