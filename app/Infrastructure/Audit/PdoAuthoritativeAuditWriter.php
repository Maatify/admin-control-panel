<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit;

use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\DTO\AuditEventDTO;
use App\Domain\Exception\Audit\AuditMappingException;
use App\Domain\Exception\Audit\AuditStorageException;
use PDO;
use PDOException;
use Throwable;

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
            throw new AuditStorageException('Authoritative Audit writes must be performed within an active transaction.');
        }

        try {
            $payloadJson = json_encode($event->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            throw new AuditMappingException('Failed to encode audit payload: ' . $e->getMessage(), 0, $e);
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO audit_outbox (actor_id, action, target_type, target_id, risk_level, payload, correlation_id, created_at)
             VALUES (:actor_id, :action, :target_type, :target_id, :risk_level, :payload, :correlation_id, :created_at)'
            );

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
        } catch (PDOException $e) {
            throw new AuditStorageException('Failed to write audit event: ' . $e->getMessage(), 0, $e);
        } catch (Throwable $e) {
            throw new AuditStorageException('Unexpected error writing audit event: ' . $e->getMessage(), 0, $e);
        }
    }
}
