<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit;

use App\Domain\Contracts\TelemetryAuditLoggerInterface;
use App\Domain\DTO\LegacyAuditEventDTO;
use PDO;

class PdoTelemetryAuditLogger implements TelemetryAuditLoggerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(LegacyAuditEventDTO $event): void
    {
        // Retargeted to telemetry_traces
        // Fail-open: swallow exceptions (implied by "best-effort")
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO telemetry_traces (
                    event_key,
                    severity,
                    actor_admin_id,
                    ip_address,
                    user_agent,
                    route_name,
                    request_id,
                    metadata,
                    created_at
                ) VALUES (
                    :event_key,
                    :severity,
                    :actor_admin_id,
                    :ip_address,
                    :user_agent,
                    :route_name,
                    :request_id,
                    :metadata,
                    :created_at
                )'
            );

            $metadata = [
                'target_type' => $event->targetType,
                'target_id' => $event->targetId,
                'changes' => $event->changes,
                // ip/ua now promoted to columns, but keeping in metadata
                // might be redundant, removing them to save space if desired.
                // Keeping strict minimal scope: just target_type/id/changes
            ];

            $metadataJson = json_encode($metadata, JSON_THROW_ON_ERROR);

            $stmt->execute([
                ':event_key' => $event->action,
                ':severity' => $event->severity,
                ':actor_admin_id' => $event->actorAdminId,
                ':ip_address' => $event->ipAddress,
                ':user_agent' => $event->userAgent,
                ':route_name' => $event->routeName,
                ':request_id' => $event->requestId,
                ':metadata' => $metadataJson,
                ':created_at' => $event->occurredAt->format('Y-m-d H:i:s.u'),
            ]);
        } catch (\Throwable $e) {
            // Best-effort: ignore failures
        }
    }
}
