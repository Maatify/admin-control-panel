<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Contracts\SecurityEventLoggerInterface;
use App\Domain\DTO\SecurityEventDTO;
use App\Domain\Exception\Security\SecurityEventMappingException;
use App\Domain\Exception\Security\SecurityEventStorageException;
use PDO;
use PDOException;
use Throwable;

class PdoSecurityEventLogger implements SecurityEventLoggerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(SecurityEventDTO $event): void
    {
        // Merge extra fields into context to preserve them if columns don't exist,
        // but also write to specific columns if the legacy schema requires them.
        $context = $event->context;
        $context['severity'] = $event->severity;
        // ip_address and user_agent are columns in the legacy schema, so we don't strictly need them in context,
        // but adding them provides redundancy/safety if the schema changes.

        try {
            $contextJson = json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            throw new SecurityEventMappingException('Failed to encode security event context: ' . $e->getMessage(), 0, $e);
        }

        try {
            // Using legacy schema columns based on SecurityEventRepository:
            // admin_id, event_name, context, ip_address, user_agent, occurred_at
            $stmt = $this->pdo->prepare(
                'INSERT INTO security_events (admin_id, event_name, context, ip_address, user_agent, occurred_at)
             VALUES (:admin_id, :event_name, :context, :ip_address, :user_agent, :occurred_at)'
            );

            $stmt->execute([
                ':admin_id'    => $event->adminId,
                ':event_name'  => $event->eventName,
                ':context'     => $contextJson,
                ':ip_address'  => $event->ipAddress,
                ':user_agent'  => $event->userAgent,
                ':occurred_at' => $event->occurredAt->format('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            throw new SecurityEventStorageException('Failed to write security event: ' . $e->getMessage(), 0, $e);
        } catch (Throwable $e) {
            throw new SecurityEventStorageException('Unexpected error writing security event: ' . $e->getMessage(), 0, $e);
        }
    }
}
