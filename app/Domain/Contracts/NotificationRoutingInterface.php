<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Notification\NotificationChannelType;

interface NotificationRoutingInterface
{
    /**
     * Resolve which notification channels should be used
     * for a given admin and notification type.
     *
     * @return NotificationChannelType[]
     */
    public function resolveChannels(
        int $adminId,
        string $notificationType
    ): array;
}
