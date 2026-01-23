<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Infrastructure\Mysql;

use Maatify\BehaviorTrace\Contract\BehaviorTraceWriterInterface;
use Maatify\BehaviorTrace\DTO\BehaviorTraceEventDTO;
use Maatify\BehaviorTrace\Exception\BehaviorTraceStorageException;
use PDO;
use PDOException;
use JsonException;

class BehaviorTraceWriterMysqlRepository implements BehaviorTraceWriterInterface
{
    private const TABLE_NAME = 'operational_activity';

    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function write(BehaviorTraceEventDTO $dto): void
    {
        $sql = sprintf(
            'INSERT INTO %s (
                event_id,
                action,
                entity_type,
                entity_id,
                actor_type,
                actor_id,
                correlation_id,
                request_id,
                route_name,
                ip_address,
                user_agent,
                metadata,
                occurred_at
            ) VALUES (
                :event_id,
                :action,
                :entity_type,
                :entity_id,
                :actor_type,
                :actor_id,
                :correlation_id,
                :request_id,
                :route_name,
                :ip_address,
                :user_agent,
                :metadata,
                :occurred_at
            )',
            self::TABLE_NAME
        );

        try {
            $metadataJson = $dto->metadata ? json_encode($dto->metadata, JSON_THROW_ON_ERROR) : '{}';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':event_id' => $dto->eventId,
                ':action' => $dto->action,
                ':entity_type' => $dto->entityType,
                ':entity_id' => $dto->entityId,
                ':actor_type' => $dto->context->actorType->value(),
                ':actor_id' => $dto->context->actorId,
                ':correlation_id' => $dto->context->correlationId,
                ':request_id' => $dto->context->requestId,
                ':route_name' => $dto->context->routeName,
                ':ip_address' => $dto->context->ipAddress,
                ':user_agent' => $dto->context->userAgent,
                ':metadata' => $metadataJson,
                ':occurred_at' => $dto->context->occurredAt->format('Y-m-d H:i:s.u'),
            ]);
        } catch (PDOException $e) {
            throw new BehaviorTraceStorageException('Failed to write behavior trace: ' . $e->getMessage(), 0, $e);
        } catch (JsonException $e) {
             throw new BehaviorTraceStorageException('Failed to encode metadata: ' . $e->getMessage(), 0, $e);
        }
    }
}
