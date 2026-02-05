<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Service;

use Maatify\AdminKernel\Domain\Contracts\Admin\AdminNotificationChannelRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Admin\AdminNotificationPreferenceRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Notification\NotificationRoutingInterface;
use Maatify\AdminKernel\Domain\Enum\NotificationChannelType;
use Maatify\AdminKernel\Domain\Notification\NotificationChannelType as RoutingChannelType;

readonly class AdminNotificationRoutingService implements NotificationRoutingInterface
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

    /**
     * Resolve which notification channels should be used
     * for a given admin and notification type.
     *
     * This method MUST be decision-only.
     * No delivery, no side effects.
     *
     * @return RoutingChannelType[]
     */
    public function resolveChannels(
        int $adminId,
        string $notificationType
    ): array {
        $legacyChannels = $this->route($adminId, $notificationType);

        $channels = [];
        foreach ($legacyChannels as $channel) {
            $mapped = RoutingChannelType::tryFrom($channel->value);
            if ($mapped !== null) {
                $channels[] = $mapped;
            }
        }

        return $channels;
    }
}
