<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 17:46
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Notification\Sender;

use App\Modules\Notification\Contract\NotificationSenderInterface;
use App\Modules\Notification\Contract\NotificationSenderRegistryInterface;
use App\Modules\Notification\Enum\NotificationChannel;
use RuntimeException;

/**
 * NotificationSenderRegistry
 *
 * In-memory registry mapping channels to senders.
 */
final class NotificationSenderRegistry implements NotificationSenderRegistryInterface
{
    /**
     * @param   NotificationSenderInterface[]  $senders
     */
    public function __construct(
        private array $senders,
    )
    {
    }

    public function resolve(NotificationChannel $channel): NotificationSenderInterface
    {
        foreach ($this->senders as $sender) {
            if ($sender->supports($channel)) {
                return $sender;
            }
        }

        throw new RuntimeException(
            sprintf('No notification sender registered for channel [%s]', $channel->value)
        );
    }
}
