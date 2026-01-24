<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\DTO;

readonly class DeliveryOperationRecordDTO
{
    /**
     * @param string $eventId
     * @param string $operationType
     * @param string $channel
     * @param string $status
     * @param int $attemptCount
     * @param string|null $providerId
     * @param DeliveryOperationContextDTO $context
     * @param array<mixed>|null $metadata
     */
    public function __construct(
        public string $eventId,
        public string $operationType,
        public string $channel,
        public string $status,
        public int $attemptCount,
        public ?string $providerId,
        public DeliveryOperationContextDTO $context,
        public ?array $metadata
    ) {
    }
}
