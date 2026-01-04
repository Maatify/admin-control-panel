<?php

declare(strict_types=1);

namespace App\Domain\DTO\Notification;

use App\Domain\Notification\NotificationChannelType;

final class PersistNotificationDTO
{
    public function __construct(
        public readonly int $adminId,
        public readonly string $notificationType,
        public readonly NotificationChannelType $channelType,
        public readonly ?string $intentId,
        public readonly ?\DateTimeImmutable $createdAt = null
    ) {
    }
}
