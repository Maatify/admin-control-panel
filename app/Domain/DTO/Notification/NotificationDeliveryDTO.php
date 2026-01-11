<?php

declare(strict_types=1);

namespace App\Domain\DTO\Notification;

use DateTimeImmutable;

readonly class NotificationDeliveryDTO
{
    /**
     * @param string $intentId
     * @param string $channel
     * @param string $entityType
     * @param string $entityId
     * @param string $recipient
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $channelMeta
     * @param int $priority
     * @param DateTimeImmutable $scheduledAt
     * @param DateTimeImmutable $createdAt
     */
    public function __construct(
        public string $intentId,
        public string $channel,
        public string $entityType,
        public string $entityId,
        public string $recipient,
        public array $payload,
        public array $channelMeta,
        public int $priority,
        public DateTimeImmutable $scheduledAt,
        public DateTimeImmutable $createdAt
    ) {
    }
}
