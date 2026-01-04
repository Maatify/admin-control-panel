<?php

declare(strict_types=1);

namespace App\Domain\DTO\Notification;

use DateTimeImmutable;

readonly class NotificationDeliveryDTO
{
    /**
     * @param string $notificationId
     * @param string $channel
     * @param string $recipient
     * @param string $title
     * @param string $body
     * @param array<string, scalar> $context
     * @param DateTimeImmutable $createdAt
     */
    public function __construct(
        public string $notificationId,
        public string $channel,
        public string $recipient,
        public string $title,
        public string $body,
        public array $context,
        public DateTimeImmutable $createdAt
    ) {
    }
}
