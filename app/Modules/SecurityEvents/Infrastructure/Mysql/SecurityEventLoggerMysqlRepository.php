<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 09:37
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\SecurityEvents\Infrastructure\Mysql;

use App\Modules\SecurityEvents\Contracts\SecurityEventLoggerInterface;
use App\Modules\SecurityEvents\Exceptions\SecurityEventStorageException;
use App\Modules\SecurityEvents\Infrastructure\Contracts\SecurityEventStorageInterface;
use App\Modules\SecurityEvents\DTO\SecurityEventDTO;
use DateTimeImmutable;
use PDO;
use Throwable;

/**
 * MySQL-based repository for persisting security events.
 *
 * Best-effort logger:
 * - MUST NOT throw
 * - MUST NOT affect authentication / authorization flow
 */
final readonly class SecurityEventLoggerMysqlRepository implements
    SecurityEventLoggerInterface,
    SecurityEventStorageInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function log(SecurityEventDTO $event): void
    {
        $this->store($event);
    }

    /**
     * {@inheritdoc}
     */
    public function store(SecurityEventDTO $event): void
    {
        try {
            $stmt = $this->pdo->prepare(
                <<<SQL
                INSERT INTO security_events (
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
                )
                SQL
            );

            $stmt->execute([
                'actor_type' => $event->actorType,
                'actor_id'   => $event->actorId,

                'event_type' => $event->eventType->value,
                'severity'   => $event->severity->value,

                'request_id' => $event->requestId,
                'route_name' => $event->routeName,

                'ip_address' => $event->ipAddress,
                'user_agent' => $event->userAgent,

                // metadata is NOT NULL in schema → always encode array
                'metadata'   => json_encode(
                    $event->metadata ?? [],
                    JSON_THROW_ON_ERROR
                ),

                'occurred_at' => ($event->occurredAt ?? new DateTimeImmutable())
                    ->format('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            throw new SecurityEventStorageException(
                'Failed to store security event',
                $e
            );
        }
    }
}
