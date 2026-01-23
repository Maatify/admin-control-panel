<?php

declare(strict_types=1);

namespace Maatify\DeliveryOperations\DTO;

use DateTimeImmutable;
use Maatify\DeliveryOperations\Enum\DeliveryChannelEnum;
use Maatify\DeliveryOperations\Enum\DeliveryOperationTypeEnum;
use Maatify\DeliveryOperations\Enum\DeliveryStatusEnum;

readonly class DeliveryOperationRecordDTO
{
    /**
     * @param string $event_id
     * @param DeliveryChannelEnum $channel
     * @param DeliveryOperationTypeEnum $operation_type
     * @param DeliveryStatusEnum $status
     * @param DateTimeImmutable $occurred_at
     * @param int $attempt_no
     * @param string|null $actor_type
     * @param int|null $actor_id
     * @param string|null $target_type
     * @param int|null $target_id
     * @param DateTimeImmutable|null $scheduled_at
     * @param DateTimeImmutable|null $completed_at
     * @param string|null $correlation_id
     * @param string|null $request_id
     * @param string|null $provider
     * @param string|null $provider_message_id
     * @param string|null $error_code
     * @param string|null $error_message
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $event_id,
        public DeliveryChannelEnum $channel,
        public DeliveryOperationTypeEnum $operation_type,
        public DeliveryStatusEnum $status,
        public DateTimeImmutable $occurred_at,
        public int $attempt_no = 0,
        public ?string $actor_type = null,
        public ?int $actor_id = null,
        public ?string $target_type = null,
        public ?int $target_id = null,
        public ?DateTimeImmutable $scheduled_at = null,
        public ?DateTimeImmutable $completed_at = null,
        public ?string $correlation_id = null,
        public ?string $request_id = null,
        public ?string $provider = null,
        public ?string $provider_message_id = null,
        public ?string $error_code = null,
        public ?string $error_message = null,
        public array $metadata = []
    ) {
    }
}
