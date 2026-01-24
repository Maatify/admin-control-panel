<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Infrastructure\Mysql;

use DateTimeZone;
use Maatify\DeliveryOperations\Contract\DeliveryOperationsLoggerInterface;
use Maatify\DeliveryOperations\DTO\DeliveryOperationRecordDTO;
use Maatify\DeliveryOperations\Exception\DeliveryOperationsStorageException;
use PDO;
use PDOException;
use JsonException;

class DeliveryOperationsLoggerMysqlRepository implements DeliveryOperationsLoggerInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function log(DeliveryOperationRecordDTO $dto): void
    {
        $sql = <<<SQL
            INSERT INTO delivery_operations (
                event_id,
                operation_type,
                channel,
                status,
                attempt_count,
                provider_id,
                actor_type,
                actor_id,
                correlation_id,
                request_id,
                route_name,
                ip_address,
                user_agent,
                occurred_at,
                metadata
            ) VALUES (
                :event_id,
                :operation_type,
                :channel,
                :status,
                :attempt_count,
                :provider_id,
                :actor_type,
                :actor_id,
                :correlation_id,
                :request_id,
                :route_name,
                :ip_address,
                :user_agent,
                :occurred_at,
                :metadata
            )
        SQL;

        try {
            $stmt = $this->pdo->prepare($sql);

            $metadataJson = null;
            if ($dto->metadata !== null) {
                $metadataJson = json_encode($dto->metadata, JSON_THROW_ON_ERROR);
            }

            $occurredAt = $dto->context->occurredAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');

            $stmt->execute([
                ':event_id' => $dto->eventId,
                ':operation_type' => $dto->operationType,
                ':channel' => $dto->channel,
                ':status' => $dto->status,
                ':attempt_count' => $dto->attemptCount,
                ':provider_id' => $dto->providerId,
                ':actor_type' => $dto->context->actorType,
                ':actor_id' => $dto->context->actorId,
                ':correlation_id' => $dto->context->correlationId,
                ':request_id' => $dto->context->requestId,
                ':route_name' => $dto->context->routeName,
                ':ip_address' => $dto->context->ipAddress,
                ':user_agent' => $dto->context->userAgent,
                ':occurred_at' => $occurredAt,
                ':metadata' => $metadataJson,
            ]);
        } catch (PDOException $e) {
            throw new DeliveryOperationsStorageException('Database write failed: ' . $e->getMessage(), 0, $e);
        } catch (JsonException $e) {
            throw new DeliveryOperationsStorageException('Metadata encoding failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
