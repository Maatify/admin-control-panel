<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification;

use App\Domain\Contracts\NotificationSenderInterface;
use App\Domain\DTO\Notification\DeliveryResultDTO;
use App\Domain\DTO\Notification\NotificationDeliveryDTO;
use DateTimeImmutable;

class EmailNotificationSender implements NotificationSenderInterface
{
    public function supports(string $channel): bool
    {
        return $channel === 'email';
    }

    public function send(NotificationDeliveryDTO $delivery): DeliveryResultDTO
    {
        // Simple validation simulation
        if (trim($delivery->recipient) === '') {
            return new DeliveryResultDTO(
                $delivery->notificationId,
                $delivery->channel,
                false,
                'Empty recipient',
                new DateTimeImmutable()
            );
        }

        // Simulate sending success
        return new DeliveryResultDTO(
            $delivery->notificationId,
            $delivery->channel,
            true,
            null,
            new DateTimeImmutable()
        );
    }
}
