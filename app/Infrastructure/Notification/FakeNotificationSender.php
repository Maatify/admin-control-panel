<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification;

use App\Domain\Contracts\NotificationSenderInterface;
use App\Domain\DTO\Notification\DeliveryResultDTO;
use App\Domain\DTO\Notification\NotificationDeliveryDTO;
use DateTimeImmutable;

class FakeNotificationSender implements NotificationSenderInterface
{
    public function supports(string $channel): bool
    {
        return $channel === 'fake' || $channel === 'test';
    }

    public function send(NotificationDeliveryDTO $delivery): DeliveryResultDTO
    {
        // Always succeed
        return new DeliveryResultDTO(
            $delivery->notificationId,
            $delivery->channel,
            true,
            null,
            new DateTimeImmutable()
        );
    }
}
