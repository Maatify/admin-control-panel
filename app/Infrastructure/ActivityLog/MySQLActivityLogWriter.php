<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-12 12:48
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Infrastructure\ActivityLog;

use App\Infrastructure\Database\PDOFactory;
use App\Modules\ActivityLog\Contracts\ActivityLogWriterInterface;
use App\Modules\ActivityLog\DTO\ActivityLogDTO;
use PDO;
use RuntimeException;

final readonly class MySQLActivityLogWriter implements ActivityLogWriterInterface
{
    public function __construct(
        private PDOFactory $pdoFactory,
    )
    {
    }

    public function write(ActivityLogDTO $activity): void
    {
        $pdo = $this->pdoFactory->create();

        $sql = <<<SQL
            INSERT INTO activity_logs (
                action,
                actor_type, actor_id,
                entity_type, entity_id,
                metadata,
                ip_address, user_agent,
                request_id,
                occurred_at
            ) VALUES (
                :action,
                :actor_type, :actor_id,
                :entity_type, :entity_id,
                :metadata,
                :ip_address, :user_agent,
                :request_id,
                :occurred_at
            )
SQL;

        $stmt = $pdo->prepare($sql);

        $metadataJson = $activity->metadata !== null
            ? json_encode($activity->metadata, JSON_THROW_ON_ERROR)
            : null;

        $stmt->bindValue(':action', $activity->action);
        $stmt->bindValue(':actor_type', $activity->actorType);
        $stmt->bindValue(':actor_id', $activity->actorId, PDO::PARAM_INT);
        $stmt->bindValue(':entity_type', $activity->entityType);
        $stmt->bindValue(':entity_id', $activity->entityId, PDO::PARAM_INT);
        $stmt->bindValue(':metadata', $metadataJson);
        $stmt->bindValue(':ip_address', $activity->ipAddress);
        $stmt->bindValue(':user_agent', $activity->userAgent);
        $stmt->bindValue(':request_id', $activity->requestId);
        $stmt->bindValue(':occurred_at', $activity->occurredAt->format('Y-m-d H:i:s.u'));

        if (!$stmt->execute()) {
            // We should throw an exception here so that ActivityLogService can catch it
            // if configured to be fail-open. The interface says "throws Throwable Infrastructure failures only"
            throw new RuntimeException('Failed to write activity log: ' . implode(', ', $stmt->errorInfo()));
        }
    }
}
