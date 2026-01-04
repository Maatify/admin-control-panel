<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Notification\DeliveryResultDTO;
use App\Domain\DTO\Notification\NotificationDeliveryDTO;

interface NotificationSenderInterface
{
    public function supports(string $channel): bool;

    public function send(NotificationDeliveryDTO $delivery): DeliveryResultDTO;
}
