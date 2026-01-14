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
        // Retargeted to telemetry_traces (Phase 2)
        // No longer touches audit_logs
        $stmt = $this->pdo->prepare(
            'INSERT INTO telemetry_traces (event_key, actor_admin_id, metadata, created_at)
             VALUES (:event_key, :actor_admin_id, :metadata, :created_at)'
        );

        $metadata = [
            'target_type' => $event->targetType,
            'target_id' => $event->targetId,
            'changes' => $event->changes,
            'ip_address' => $event->ipAddress,
            'user_agent' => $event->userAgent,
        ];

        $metadataJson = json_encode($metadata, JSON_THROW_ON_ERROR);

        $stmt->execute([
            ':event_key' => $event->action,
            ':actor_admin_id' => $event->actorAdminId,
            ':metadata' => $metadataJson,
            ':created_at' => $event->occurredAt->format('Y-m-d H:i:s.u'),
        ]);
    }
}
