<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\NotificationRoutingInterface;
use App\Domain\Notification\NotificationChannelType;

class AdminNotificationRoutingService implements NotificationRoutingInterface
{
    /**
     * @return NotificationChannelType[]
     */
    public function resolveChannels(int $adminId, string $notificationType): array
    {
        // Phase 10 implementation pending
        return [];
    }
}
