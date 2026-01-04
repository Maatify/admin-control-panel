<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminNotificationChannelRepositoryInterface;
use App\Domain\Contracts\NotificationSenderInterface;
use App\Domain\DTO\Notification\NotificationDeliveryDTO;
use App\Domain\Exception\UnsupportedNotificationChannelException;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

class NotificationDispatcher
{
    /**
     * @param iterable<NotificationSenderInterface> $senders
     */
    public function __construct(
        private iterable $senders,
        private NotificationFailureHandler $failureHandler,
        private AdminNotificationRoutingService $routingService,
        private AdminNotificationChannelRepositoryInterface $channelRepository
    ) {
    }

    /**
     * @param array<string, scalar> $context
     */
    public function dispatchIntent(
        int $adminId,
        string $notificationType,
        string $title,
        string $body,
        array $context = []
    ): void {
        // 1. Resolve channels
        $allowedTypes = $this->routingService->route($adminId, $notificationType);

        if (empty($allowedTypes)) {
            return; // No channels enabled/preferred, silent exit
        }

        // 2. Get configs to find recipients
        $channels = $this->channelRepository->getEnabledChannelsForAdmin($adminId);

        // 3. Dispatch for each allowed channel
        foreach ($channels as $channel) {
            // Must be in allowed types
            if (! in_array($channel->channelType, $allowedTypes, true)) {
                continue;
            }

            // Determine recipient from config
            $recipient = $channel->config['recipient'] ?? null;

            if (empty($recipient)) {
                $recipient = match ($channel->channelType->value) {
                    'email' => $channel->config['email'] ?? $channel->config['email_address'] ?? null,
                    'telegram' => $channel->config['chat_id'] ?? null,
                    'webhook' => $channel->config['url'] ?? null,
                };
            }

            if (empty($recipient) || ! is_scalar($recipient)) {
                continue;
            }

            $deliveryDTO = new NotificationDeliveryDTO(
                uniqid('notif_', true),
                $channel->channelType->value,
                (string)$recipient,
                $title,
                $body,
                $context,
                new DateTimeImmutable()
            );

            $this->dispatch($deliveryDTO);
        }
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
        } catch (Throwable $e) {
            $this->failureHandler->handle($notification, $e);
            throw $e;
        }
    }
}
