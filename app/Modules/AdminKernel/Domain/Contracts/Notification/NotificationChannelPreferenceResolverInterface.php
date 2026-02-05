<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Notification;

use Maatify\AdminKernel\Domain\DTO\Notification\ChannelResolutionResultDTO;
use Maatify\AdminKernel\Domain\DTO\Notification\NotificationRoutingContextDTO;

interface NotificationChannelPreferenceResolverInterface
{
    /**
     * Resolve the preferred channels for an admin and notification type.
     *
     * This method MUST be decision-only and side-effect free.
     *
     * @param NotificationRoutingContextDTO $context The routing context containing admin ID and notification type.
     *
     * @return ChannelResolutionResultDTO The result of the preference resolution.
     */
    public function resolvePreference(
        NotificationRoutingContextDTO $context
    ): ChannelResolutionResultDTO;
}
