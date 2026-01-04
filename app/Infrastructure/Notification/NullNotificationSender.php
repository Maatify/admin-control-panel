<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification;

use App\Domain\Contracts\NotificationSenderInterface;
use App\Domain\DTO\Notification\DeliveryResultDTO;
use App\Domain\DTO\Notification\NotificationDeliveryDTO;
use DateTimeImmutable;

class NullNotificationSender implements NotificationSenderInterface
{
    public function supports(string $channel): bool
    {
        return false;
    }

    public function send(NotificationDeliveryDTO $delivery): DeliveryResultDTO
    {
        // Should be unreachable in production if dispatcher logic is correct
        throw new \RuntimeException('NullNotificationSender should never be called');
    }
}
