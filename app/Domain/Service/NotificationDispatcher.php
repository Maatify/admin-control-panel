<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminNotificationChannelRepositoryInterface;
use App\Domain\DTO\Notification\NotificationDeliveryDTO;
use App\Modules\Notification\Queue\NotificationQueueWriterInterface;
use DateTimeImmutable;

class NotificationDispatcher
{
    public function __construct(
        private readonly AdminNotificationRoutingService $routingService,
        private readonly AdminNotificationChannelRepositoryInterface $channelRepository,
        private readonly NotificationQueueWriterInterface $queueWriter
    ) {
    }

    /**
     * @param int $adminId
     * @param string $notificationType
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $channelMeta
     */
    public function dispatchIntent(
        int $adminId,
        string $notificationType,
        array $payload,
        array $channelMeta = []
    ): void {
        // 1. Resolve allowed channels
        $allowedTypes = $this->routingService->route($adminId, $notificationType);

        if (empty($allowedTypes)) {
            return;
        }

        // 2. Get active channel configurations for the admin
        $channels = $this->channelRepository->getEnabledChannelsForAdmin($adminId);

        $now = new DateTimeImmutable();
        $intentId = uniqid('notif_', true);

        // 3. Dispatch to queue for each allowed channel
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
                    default => null,
                };
            }

            if (empty($recipient) || ! is_scalar($recipient)) {
                continue;
            }

            $dto = new NotificationDeliveryDTO(
                $intentId,
                $channel->channelType->value,
                'admin', // entity_type locked to admin
                (string)$adminId,
                (string)$recipient,
                $payload,
                $channelMeta,
                5, // Default priority
                $now, // Scheduled immediately
                $now
            );

            $this->queueWriter->enqueue($dto);
        }
    }
}
