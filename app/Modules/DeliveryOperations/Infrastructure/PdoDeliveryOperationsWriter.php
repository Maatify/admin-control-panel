<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Infrastructure;

use Maatify\DeliveryOperations\Contract\DeliveryOperationsLoggerInterface;
use Maatify\DeliveryOperations\DTO\DeliveryOperationRecordDTO;
use Maatify\DeliveryOperations\Exception\DeliveryOperationsStorageException;
use PDO;
use PDOException;

class PdoDeliveryOperationsWriter implements DeliveryOperationsLoggerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(DeliveryOperationRecordDTO $dto): void
    {
        $sql = <<<SQL
            INSERT INTO delivery_operations (
                event_id,
                channel,
                operation_type,
                actor_type,
                actor_id,
                target_type,
                target_id,
                status,
                attempt_no,
                scheduled_at,
                completed_at,
                correlation_id,
                request_id,
                provider,
                provider_message_id,
                error_code,
                error_message,
                metadata,
                occurred_at
            ) VALUES (
                :event_id,
                :channel,
                :operation_type,
                :actor_type,
                :actor_id,
                :target_type,
                :target_id,
                :status,
                :attempt_no,
                :scheduled_at,
                :completed_at,
                :correlation_id,
                :request_id,
                :provider,
                :provider_message_id,
                :error_code,
                :error_message,
                :metadata,
                :occurred_at
            )
        SQL;

        try {
            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                ':event_id' => $dto->event_id,
                ':channel' => $dto->channel->value,
                ':operation_type' => $dto->operation_type->value,
                ':actor_type' => $dto->actor_type,
                ':actor_id' => $dto->actor_id,
                ':target_type' => $dto->target_type,
                ':target_id' => $dto->target_id,
                ':status' => $dto->status->value,
                ':attempt_no' => $dto->attempt_no,
                ':scheduled_at' => $dto->scheduled_at?->format('Y-m-d H:i:s.u'),
                ':completed_at' => $dto->completed_at?->format('Y-m-d H:i:s.u'),
                ':correlation_id' => $dto->correlation_id,
                ':request_id' => $dto->request_id,
                ':provider' => $dto->provider,
                ':provider_message_id' => $dto->provider_message_id,
                ':error_code' => $dto->error_code,
                ':error_message' => $dto->error_message,
                ':metadata' => json_encode($dto->metadata, JSON_THROW_ON_ERROR),
                ':occurred_at' => $dto->occurred_at->format('Y-m-d H:i:s.u'),
            ]);
        } catch (PDOException $e) {
            throw new DeliveryOperationsStorageException('Failed to write delivery operation to database', 0, $e);
        } catch (\JsonException $e) {
            throw new DeliveryOperationsStorageException('Failed to encode metadata for delivery operation', 0, $e);
        }
    }
}
