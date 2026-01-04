<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Enum\NotificationChannelType;

interface AdminNotificationPreferenceRepositoryInterface
{
    /**
     * @param int $adminId
     * @param string $notificationType
     * @return array<NotificationChannelType>
     */
    public function getEnabledChannelsForNotification(int $adminId, string $notificationType): array;
}
