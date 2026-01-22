<?php

declare(strict_types=1);

namespace App\Modules\ActivityLog\Infrastructure\Mysql;

use App\Modules\ActivityLog\Contracts\ActivityLogWriterInterface;
use App\Modules\ActivityLog\DTO\ActivityLogDTO;
use App\Modules\ActivityLog\Exceptions\ActivityLogMappingException;
use App\Modules\ActivityLog\Exceptions\ActivityLogStorageException;
use JsonException;
use PDO;
use PDOException;

final readonly class ActivityLogLoggerMysqlRepository implements ActivityLogWriterInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function write(ActivityLogDTO $activity): void
    {
        try {
            $metadataJson = $activity->metadata !== null
                ? json_encode($activity->metadata, JSON_THROW_ON_ERROR)
                : null;

            $stmt = $this->pdo->prepare(
                <<<SQL
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
                SQL
            );

            $stmt->execute([
                ':actor_type'  => $activity->actorType,
                ':actor_id'    => $activity->actorId,
                ':action'      => $activity->action,
                ':entity_type' => $activity->entityType,
                ':entity_id'   => $activity->entityId,
                ':metadata'    => $metadataJson,
                ':ip_address'  => $activity->ipAddress,
                ':user_agent'  => $activity->userAgent,
                ':request_id'  => $activity->requestId,
                ':occurred_at' => $activity->occurredAt->format('Y-m-d H:i:s.u'),
            ]);
        } catch (JsonException $e) {
            throw new ActivityLogMappingException('Failed to encode activity metadata.', $e);
        } catch (PDOException $e) {
            throw new ActivityLogStorageException('Failed to persist activity log.', $e);
        }
    }
}
