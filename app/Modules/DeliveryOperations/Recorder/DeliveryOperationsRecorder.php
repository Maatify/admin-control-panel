<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\Recorder;

use DateTimeImmutable;
use Maatify\DeliveryOperations\Contract\DeliveryOperationsClockInterface;
use Maatify\DeliveryOperations\Contract\DeliveryOperationsLoggerInterface;
use Maatify\DeliveryOperations\DTO\DeliveryOperationRecordDTO;
use Maatify\DeliveryOperations\Enum\DeliveryChannelEnum;
use Maatify\DeliveryOperations\Enum\DeliveryOperationTypeEnum;
use Maatify\DeliveryOperations\Enum\DeliveryStatusEnum;
use Ramsey\Uuid\Uuid;
use Throwable;

class DeliveryOperationsRecorder
{
    public function __construct(
        private readonly DeliveryOperationsLoggerInterface $logger,
        private readonly DeliveryOperationsClockInterface $clock
    ) {
    }

    /**
     * @param DeliveryChannelEnum $channel
     * @param DeliveryOperationTypeEnum $operationType
     * @param DeliveryStatusEnum $status
     * @param int $attemptNo
     * @param string|null $actorType
     * @param int|null $actorId
     * @param string|null $targetType
     * @param int|null $targetId
     * @param DateTimeImmutable|null $scheduledAt
     * @param DateTimeImmutable|null $completedAt
     * @param string|null $correlationId
     * @param string|null $requestId
     * @param string|null $provider
     * @param string|null $providerMessageId
     * @param string|null $errorCode
     * @param string|null $errorMessage
     * @param array<string, mixed> $metadata
     * @return void
     */
    public function record(
        DeliveryChannelEnum $channel,
        DeliveryOperationTypeEnum $operationType,
        DeliveryStatusEnum $status,
        int $attemptNo = 0,
        ?string $actorType = null,
        ?int $actorId = null,
        ?string $targetType = null,
        ?int $targetId = null,
        ?DateTimeImmutable $scheduledAt = null,
        ?DateTimeImmutable $completedAt = null,
        ?string $correlationId = null,
        ?string $requestId = null,
        ?string $provider = null,
        ?string $providerMessageId = null,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        array $metadata = []
    ): void {
        try {
            $eventId = Uuid::uuid4()->toString();
            $occurredAt = $this->clock->now();

            $dto = new DeliveryOperationRecordDTO(
                event_id: $eventId,
                channel: $channel,
                operation_type: $operationType,
                status: $status,
                occurred_at: $occurredAt,
                attempt_no: $attemptNo,
                actor_type: $actorType,
                actor_id: $actorId,
                target_type: $targetType,
                target_id: $targetId,
                scheduled_at: $scheduledAt,
                completed_at: $completedAt,
                correlation_id: $correlationId,
                request_id: $requestId,
                provider: $provider,
                provider_message_id: $providerMessageId,
                error_code: $errorCode,
                error_message: $errorMessage,
                metadata: $metadata
            );

            $this->logger->log($dto);
        } catch (Throwable $e) {
            // Fail-open: swallow all exceptions
            // In a real application, we might log this to PSR-3 logger if injected,
            // but for this pure library implementation, we simply ensure we don't crash.
        }
    }
}
