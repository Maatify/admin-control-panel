<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\DTO;

use DateTimeImmutable;

readonly class DeliveryOperationRecordDTO
{
    /**
     * @param string $eventId
     * @param string $channel
     * @param string $operationType
     * @param string|null $actorType
     * @param int|null $actorId
     * @param string|null $targetType
     * @param int|null $targetId
     * @param string $status
     * @param int $attemptNo
     * @param DateTimeImmutable|null $scheduledAt
     * @param DateTimeImmutable|null $completedAt
     * @param string|null $correlationId
     * @param string|null $requestId
     * @param string|null $provider
     * @param string|null $providerMessageId
     * @param string|null $errorCode
     * @param string|null $errorMessage
     * @param array<mixed>|null $metadata
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $eventId,
        public string $channel,
        public string $operationType,
        public ?string $actorType,
        public ?int $actorId,
        public ?string $targetType,
        public ?int $targetId,
        public string $status,
        public int $attemptNo,
        public ?DateTimeImmutable $scheduledAt,
        public ?DateTimeImmutable $completedAt,
        public ?string $correlationId,
        public ?string $requestId,
        public ?string $provider,
        public ?string $providerMessageId,
        public ?string $errorCode,
        public ?string $errorMessage,
        public ?array $metadata,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
