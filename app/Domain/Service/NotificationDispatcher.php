<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\NotificationSenderInterface;
use App\Domain\DTO\Notification\NotificationDeliveryDTO;
use App\Domain\Exception\UnsupportedNotificationChannelException;
use RuntimeException;

class NotificationDispatcher
{
    /**
     * @param iterable<NotificationSenderInterface> $senders
     */
    public function __construct(
        private iterable $senders
    ) {
    }

    public function dispatch(NotificationDeliveryDTO $notification): void
    {
        foreach ($this->senders as $sender) {
            if ($sender->supports($notification->channel)) {
                $result = $sender->send($notification);

                if (! $result->success) {
                    throw new RuntimeException(
                        sprintf(
                            'Notification delivery failed via %s: %s',
                            $notification->channel,
                            $result->errorReason ?? 'Unknown error'
                        )
                    );
                }

                return;
            }
        }

        throw new UnsupportedNotificationChannelException($notification->channel);
    }
}
