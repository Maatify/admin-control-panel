<?php

declare(strict_types=1);

namespace App\Application\Services;

use DateTimeImmutable;
use Maatify\DeliveryOperations\Enum\DeliveryActorTypeInterface;
use Maatify\DeliveryOperations\Enum\DeliveryChannelEnum;
use Maatify\DeliveryOperations\Enum\DeliveryOperationTypeEnum;
use Maatify\DeliveryOperations\Enum\DeliveryStatusEnum;
use Maatify\DeliveryOperations\Recorder\DeliveryOperationsRecorder;

class DeliveryOperationsService
{
    public function __construct(
        private readonly DeliveryOperationsRecorder $recorder
    ) {
    }

    /**
     * Records a delivery operation event.
     *
     * This method acts as a project-facing wrapper for the DeliveryOperationsRecorder.
     * It enforces Fail-Open behavior (Best Effort), meaning exceptions during recording
     * are suppressed (logged to fallback) and will NOT crash the application.
     *
     * @param DeliveryChannelEnum|string $channel
     * @param DeliveryOperationTypeEnum|string $operationType
     * @param DeliveryStatusEnum|string $status
     * @param int $attemptNo
     * @param DeliveryActorTypeInterface|string|null $actorType
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
     * @param array<mixed>|null $metadata
     */
    public function record(
        DeliveryChannelEnum|string $channel,
        DeliveryOperationTypeEnum|string $operationType,
        DeliveryStatusEnum|string $status,
        int $attemptNo = 0,
        DeliveryActorTypeInterface|string|null $actorType = null,
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
        ?array $metadata = null
    ): void {
        try {
            $this->recorder->record(
                $channel,
                $operationType,
                $status,
                $attemptNo,
                $actorType,
                $actorId,
                $targetType,
                $targetId,
                $scheduledAt,
                $completedAt,
                $correlationId,
                $requestId,
                $provider,
                $providerMessageId,
                $errorCode,
                $errorMessage,
                $metadata
            );
        } catch (\Throwable $e) {
            // Fail-open: suppress all exceptions to prevent application crash
        }
    }
}
