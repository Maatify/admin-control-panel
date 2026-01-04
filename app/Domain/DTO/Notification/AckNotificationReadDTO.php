<?php

declare(strict_types=1);

namespace App\Domain\DTO\Notification;

final class AckNotificationReadDTO
{
    public function __construct(
        public readonly int $notificationId,
        public readonly \DateTimeImmutable $readAt
    ) {
    }
}
