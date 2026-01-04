<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Notification\ChannelResolutionResultDTO;
use App\Domain\DTO\Notification\NotificationRoutingContextDTO;

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
