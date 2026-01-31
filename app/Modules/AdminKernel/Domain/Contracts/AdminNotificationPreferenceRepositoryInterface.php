<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts;

use Maatify\AdminKernel\Domain\Enum\NotificationChannelType;

interface AdminNotificationPreferenceRepositoryInterface
{
    /**
     * @param int $adminId
     * @param string $notificationType
     * @return array<NotificationChannelType>
     */
    public function getEnabledChannelsForNotification(int $adminId, string $notificationType): array;
}
