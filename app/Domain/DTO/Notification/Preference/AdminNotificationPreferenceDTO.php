<?php

declare(strict_types=1);

namespace App\Domain\DTO\Notification\Preference;

use App\Domain\Notification\NotificationChannelType;

readonly class AdminNotificationPreferenceDTO implements \JsonSerializable
{
    public function __construct(
        public int $adminId,
        public string $notificationType,
        public NotificationChannelType $channelType,
        public bool $isEnabled,
        public string $createdAt,
        public string $updatedAt
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'admin_id' => $this->adminId,
            'notification_type' => $this->notificationType,
            'channel_type' => $this->channelType->value,
            'is_enabled' => $this->isEnabled,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
