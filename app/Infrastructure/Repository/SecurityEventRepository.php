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
        $stmt = $this->pdo->prepare(
            'INSERT INTO security_events (admin_id, event_name, context, ip_address, user_agent, occurred_at)
             VALUES (:admin_id, :event_name, :context, :ip_address, :user_agent, :occurred_at)'
        );

        $contextJson = json_encode($event->context);
        assert($contextJson !== false);

        $stmt->execute([
            ':admin_id' => $event->adminId,
            ':event_name' => $event->eventName,
            ':context' => $contextJson,
            ':ip_address' => $event->ipAddress,
            ':user_agent' => $event->userAgent,
            ':occurred_at' => $event->occurredAt->format('Y-m-d H:i:s'),
        ]);
    }
}
