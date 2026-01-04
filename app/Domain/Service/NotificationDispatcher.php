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
        private iterable $senders,
        private NotificationFailureHandler $failureHandler
    ) {
    }

    public function dispatch(NotificationDeliveryDTO $notification): void
    {
        try {
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
        } catch (RuntimeException $e) { // Catch runtime exceptions from delivery failure
            $this->failureHandler->handle($notification, $e);
            throw $e;
        } catch (UnsupportedNotificationChannelException $e) {
             // We might want to record unsupported channel as a failure too,
             // but strictly speaking it's a configuration/routing error, not a delivery failure of a supported channel.
             // However, to be robust, let's record it.
             $this->failureHandler->handle($notification, $e);
             throw $e;
        } catch (\Throwable $e) {
            $this->failureHandler->handle($notification, $e);
            throw $e;
        }
    }
}
