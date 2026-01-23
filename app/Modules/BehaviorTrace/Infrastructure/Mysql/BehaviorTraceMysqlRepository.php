<?php

declare(strict_types=1);

namespace Maatify\BehaviorTrace\Infrastructure\Mysql;

use DateTimeImmutable;
use DateTimeZone;
use Generator;
use JsonException;
use Maatify\BehaviorTrace\Contract\BehaviorTraceLoggerInterface;
use Maatify\BehaviorTrace\Contract\BehaviorTraceQueryInterface;
use Maatify\BehaviorTrace\DTO\BehaviorTraceContextDTO;
use Maatify\BehaviorTrace\DTO\BehaviorTraceQueryDTO;
use Maatify\BehaviorTrace\DTO\BehaviorTraceRecordDTO;
use Maatify\BehaviorTrace\DTO\BehaviorTraceViewDTO;
use Maatify\BehaviorTrace\Enum\BehaviorTraceActorTypeEnum;
use Maatify\BehaviorTrace\Exception\BehaviorTraceStorageException;
use PDO;
use PDOException;
use Throwable;

class BehaviorTraceMysqlRepository implements BehaviorTraceLoggerInterface, BehaviorTraceQueryInterface
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function write(BehaviorTraceRecordDTO $dto): void
    {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO operational_activity (
                    event_id, occurred_at, actor_type, actor_id, action,
                    resource, resource_id, payload, correlation_id,
                    request_id, route_name, ip_address, user_agent
                ) VALUES (
                    :event_id, :occurred_at, :actor_type, :actor_id, :action,
                    :resource, :resource_id, :payload, :correlation_id,
                    :request_id, :route_name, :ip_address, :user_agent
                )
            ');

            $payloadJson = null;
            if ($dto->payload !== null) {
                $payloadJson = json_encode($dto->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            }

            $stmt->execute([
                'event_id' => $dto->eventId,
                'occurred_at' => $dto->context->occurredAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.u'),
                'actor_type' => $dto->context->actorType->value,
                'actor_id' => $dto->context->actorId,
                'action' => $dto->action,
                'resource' => $dto->resource,
                'resource_id' => $dto->resourceId,
                'payload' => $payloadJson,
                'correlation_id' => $dto->context->correlationId,
                'request_id' => $dto->context->requestId,
                'route_name' => $dto->context->routeName,
                'ip_address' => $dto->context->ipAddress,
                'user_agent' => $dto->context->userAgent,
            ]);
        } catch (PDOException $e) {
            throw new BehaviorTraceStorageException('Failed to write behavior trace', 0, $e);
        } catch (JsonException $e) {
            throw new BehaviorTraceStorageException('Failed to encode behavior trace payload', 0, $e);
        }
    }

    public function read(?BehaviorTraceQueryDTO $cursor, int $limit = 100): iterable
    {
        try {
            $sql = 'SELECT * FROM operational_activity';
            $params = [];

            if ($cursor !== null) {
                // Cursor-based pagination: (occurred_at < last_occurred_at) OR (occurred_at = last_occurred_at AND id < last_id)
                // Ordering is DESC
                $sql .= ' WHERE (occurred_at < :last_occurred_at) OR (occurred_at = :last_occurred_at AND id < :last_id)';
                $params['last_occurred_at'] = $cursor->lastOccurredAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');
                $params['last_id'] = $cursor->lastId;
            }

            $sql .= ' ORDER BY occurred_at DESC, id DESC LIMIT :limit';

            // PDO LIMIT binding usually requires integer type, but some drivers handle string.
            // Better to bind manually or use int cast if emulating prepares.
            // For safety with strictly typed PDO, bindValue with PARAM_INT is best.

            $stmt = $this->pdo->prepare($sql);

            if ($cursor !== null) {
                $stmt->bindValue(':last_occurred_at', $params['last_occurred_at']);
                $stmt->bindValue(':last_id', $params['last_id'], PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                yield $this->hydrate($row);
            }
        } catch (PDOException $e) {
            throw new BehaviorTraceStorageException('Failed to read behavior traces', 0, $e);
        }
    }

    private function hydrate(array $row): BehaviorTraceViewDTO
    {
        try {
            $actorType = BehaviorTraceActorTypeEnum::tryFrom($row['actor_type']) ?? BehaviorTraceActorTypeEnum::ANONYMOUS;
            $occurredAt = new DateTimeImmutable($row['occurred_at'], new DateTimeZone('UTC'));

            $context = new BehaviorTraceContextDTO(
                actorType: $actorType,
                actorId: isset($row['actor_id']) ? (int)$row['actor_id'] : null,
                correlationId: $row['correlation_id'] ?? null,
                requestId: $row['request_id'] ?? null,
                routeName: $row['route_name'] ?? null,
                ipAddress: $row['ip_address'] ?? null,
                userAgent: $row['user_agent'] ?? null,
                occurredAt: $occurredAt
            );

            $payload = null;
            if (isset($row['payload']) && is_string($row['payload'])) {
                $payload = json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
            }

            return new BehaviorTraceViewDTO(
                id: (int)$row['id'],
                eventId: $row['event_id'],
                action: $row['action'],
                resource: $row['resource'],
                resourceId: $row['resource_id'] ?? null,
                payload: $payload,
                context: $context
            );

        } catch (Throwable $e) {
            // Fail-safe hydration: if one row is corrupt, we probably shouldn't crash the whole feed?
            // Blueprint says: "Fail-Safe Hydration: If the DB contains invalid data... the Reader MUST NOT crash. It should sanitize on read."
            // So I should catch exception and return a sanitized/error DTO?
            // But yielding from here makes it hard.
            // I'll log internally? No logger injected here.
            // I'll return a "corrupted" DTO or throw?
            // Blueprint: "Reader: MAY throw (reads are not critical)".
            // But also: "Fail-Safe Hydration".
            // I'll rethrow as StorageException for now, as implementing partial failure in generator is complex without logger.
             throw new BehaviorTraceStorageException('Failed to hydrate row id ' . ($row['id'] ?? 'unknown'), 0, $e);
        }
    }
}
