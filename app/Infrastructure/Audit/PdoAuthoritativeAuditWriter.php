<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit;

use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\DTO\AuditEventDTO;
use PDO;
use RuntimeException;

class PdoAuthoritativeAuditWriter implements AuthoritativeSecurityAuditWriterInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function write(AuditEventDTO $event): void
    {
        if (!$this->pdo->inTransaction()) {
            throw new RuntimeException('Authoritative Audit writes must be performed within an active transaction.');
        }

        // Fallback for actor_type when context is missing (e.g. early rejection)
        // If we have an actor_id, it is an ADMIN. Otherwise, it is SYSTEM/ANONYMOUS.
        $actorType = $event->actor_id !== null ? 'ADMIN' : 'SYSTEM';

        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_outbox (actor_type, actor_id, action, target_type, target_id, risk_level, payload, correlation_id, created_at)
             VALUES (:actor_type, :actor_id, :action, :target_type, :target_id, :risk_level, :payload, :correlation_id, :created_at)'
        );

        $payloadJson = json_encode($event->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $stmt->execute([
            ':actor_type' => $actorType,
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
