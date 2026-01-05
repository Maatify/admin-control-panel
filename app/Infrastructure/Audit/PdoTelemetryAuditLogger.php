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
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (actor_admin_id, target_type, target_id, action, changes, ip_address, user_agent, occurred_at)
             VALUES (:actor_admin_id, :target_type, :target_id, :action, :changes, :ip_address, :user_agent, :occurred_at)'
        );

        $changesJson = json_encode($event->changes);
        assert($changesJson !== false);

        $stmt->execute([
            ':actor_admin_id' => $event->actorAdminId,
            ':target_type' => $event->targetType,
            ':target_id' => $event->targetId,
            ':action' => $event->action,
            ':changes' => $changesJson,
            ':ip_address' => $event->ipAddress,
            ':user_agent' => $event->userAgent,
            ':occurred_at' => $event->occurredAt->format('Y-m-d H:i:s'),
        ]);
    }
}
