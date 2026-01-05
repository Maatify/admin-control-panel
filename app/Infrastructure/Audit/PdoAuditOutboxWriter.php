<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit;

use App\Domain\Contracts\AuditOutboxWriterInterface;
use App\Domain\DTO\AuditEventDTO;
use PDO;

class PdoAuditOutboxWriter implements AuditOutboxWriterInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function write(AuditEventDTO $event): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_outbox (actor_id, action, target_type, target_id, risk_level, payload, correlation_id, created_at)
             VALUES (:actor_id, :action, :target_type, :target_id, :risk_level, :payload, :correlation_id, :created_at)'
        );

        $payloadJson = json_encode($event->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $stmt->execute([
            ':actor_id' => $event->actor_id,
            ':action' => $event->action,
            ':target_type' => $event->target_type,
            ':target_id' => $event->target_id,
            ':risk_level' => $event->risk_level,
            ':payload' => $payloadJson,
            ':correlation_id' => $event->correlation_id,
            ':created_at' => $event->created_at->format('Y-m-d H:i:s'),
        ]);
    }
}
