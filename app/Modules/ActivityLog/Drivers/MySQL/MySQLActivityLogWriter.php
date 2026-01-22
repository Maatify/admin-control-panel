<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 20:02
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\ActivityLog\Drivers\MySQL;

use App\Modules\ActivityLog\Contracts\ActivityLogWriterInterface;
use App\Modules\ActivityLog\DTO\ActivityLogDTO;
use App\Modules\ActivityLog\Exceptions\ActivityLogStorageException;
use PDO;
use Throwable;

final readonly class MySQLActivityLogWriter implements ActivityLogWriterInterface
{
    public function __construct(
        private PDO $pdo,
    )
    {
    }

    public function write(ActivityLogDTO $activity): void
    {
        try {
            $sql = <<<SQL
INSERT INTO activity_logs (
    actor_type,
    actor_id,
    action,
    entity_type,
    entity_id,
    metadata,
    ip_address,
    user_agent,
    request_id,
    occurred_at
) VALUES (
    :actor_type,
    :actor_id,
    :action,
    :entity_type,
    :entity_id,
    :metadata,
    :ip_address,
    :user_agent,
    :request_id,
    :occurred_at
)
SQL;

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                ':actor_type'  => $activity->actorType,
                ':actor_id'    => $activity->actorId,
                ':action'      => $activity->action,
                ':entity_type' => $activity->entityType,
                ':entity_id'   => $activity->entityId,
                ':metadata'    => $activity->metadata !== null
                    ? json_encode($activity->metadata, JSON_THROW_ON_ERROR)
                    : null,
                ':ip_address'  => $activity->ipAddress,
                ':user_agent'  => $activity->userAgent,
                ':request_id'  => $activity->requestId,
                ':occurred_at' => $activity->occurredAt->format('Y-m-d H:i:s.u'),
            ]);
        } catch (Throwable $e) {
            throw new ActivityLogStorageException('Failed to write activity log', $e);
        }
    }
}
