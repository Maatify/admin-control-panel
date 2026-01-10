<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 17:45
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Notification\Contract;

use App\Modules\Notification\Enum\NotificationChannel;
use RuntimeException;

/**
 * NotificationSenderRegistryInterface
 *
 * Resolves the appropriate sender for a notification channel.
 */
interface NotificationSenderRegistryInterface
{
    /**
     * Resolve sender for the given channel.
     *
     * @throws RuntimeException if no sender supports the channel
     */
    public function resolve(NotificationChannel $channel): NotificationSenderInterface;
}
