<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminNotificationChannelRepositoryInterface;
use App\Domain\Contracts\AdminNotificationPreferenceRepositoryInterface;
use App\Domain\Enum\NotificationChannelType;

readonly class AdminNotificationRoutingService
{
    public function __construct(
        private AdminNotificationChannelRepositoryInterface $channelRepository,
        private AdminNotificationPreferenceRepositoryInterface $preferenceRepository
    ) {
    }

    /**
     * @return array<NotificationChannelType>
     */
    public function route(int $adminId, string $notificationType): array
    {
        // 1. Get all configured and enabled channels for the admin (Global switch)
        $configuredChannels = $this->channelRepository->getEnabledChannelsForAdmin($adminId);

        if (empty($configuredChannels)) {
            return [];
        }

        // 2. Get preferred channels for this notification type (Per-notification switch)
        $preferredTypes = $this->preferenceRepository->getEnabledChannelsForNotification($adminId, $notificationType);

        if (empty($preferredTypes)) {
            return [];
        }

        // 3. Intersect: Only allow channels that are BOTH configured AND preferred
        $allowedTypes = [];
        foreach ($configuredChannels as $channel) {
            if (in_array($channel->channelType, $preferredTypes, true)) {
                $allowedTypes[] = $channel->channelType;
            }
        }

        // Remove duplicates if multiple channels of same type exist
        return array_values(array_unique($allowedTypes, SORT_REGULAR));
    }
}
