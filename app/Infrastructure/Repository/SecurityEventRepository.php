<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\SecurityEventDTO;
use PDO;

class SecurityEventRepository implements SecurityEventLoggerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(SecurityEventDTO $event): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO security_events (
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

            $metadata = $event->context;
            $routeName = null;
            if (isset($metadata['route_name']) && is_string($metadata['route_name'])) {
                $routeName = $metadata['route_name'];
                unset($metadata['route_name']);
            }
            $metadataJson = json_encode($metadata, JSON_THROW_ON_ERROR);

            $stmt->execute([
                ':actor_type'  => $event->adminId === null ? 'system' : 'admin',
                ':actor_id'    => $event->adminId,
                ':event_type'  => $event->eventName,
                ':severity'    => $event->severity,
                ':request_id'  => $event->requestId,
                ':route_name'  => $routeName,
                ':ip_address'  => $event->ipAddress,
                ':user_agent'  => $event->userAgent,
                ':metadata'    => $metadataJson,
                ':occurred_at' => $event->occurredAt->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // swallow — security events are best-effort and must not break flow
        }
    }
}
