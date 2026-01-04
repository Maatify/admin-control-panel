<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Notification\ChannelResolutionResultDTO;

interface NotificationChannelPreferenceResolverInterface
{
    /**
     * Resolve the preferred channels for an admin and notification type.
     *
     * This method MUST be decision-only and side-effect free.
     *
     * @param int $adminId The ID of the admin receiving the notification.
     * @param string $notificationType The type of notification being sent.
     *
     * @return ChannelResolutionResultDTO The result of the preference resolution.
     */
    public function resolvePreference(
        int $adminId,
        string $notificationType
    ): ChannelResolutionResultDTO;
}
