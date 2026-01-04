<?php

declare(strict_types=1);

namespace App\Domain\DTO\Notification\Preference;

use App\Domain\Notification\NotificationChannelType;

readonly class UpdateAdminNotificationPreferenceDTO
{
    public function __construct(
        public int $adminId,
        public string $notificationType,
        public NotificationChannelType $channelType,
        public bool $isEnabled
    ) {
    }
}
