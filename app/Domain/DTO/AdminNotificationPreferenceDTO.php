<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\Enum\NotificationChannelType;

final class AdminNotificationPreferenceDTO
{
    public function __construct(
        public readonly int $adminId,
        public readonly string $notificationType,
        public readonly NotificationChannelType $channelType,
        public readonly bool $isEnabled
    ) {
    }
}
