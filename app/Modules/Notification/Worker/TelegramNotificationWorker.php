<?php

declare(strict_types=1);

namespace App\Modules\Notification\Worker;

use App\Domain\DTO\Notification\NotificationDeliveryDTO;

class TelegramNotificationWorker extends AbstractNotificationWorker
{
    public function process(NotificationDeliveryDTO $delivery): void
    {
        // 1. Decrypt
        // 2. Call Telegram Sender
    }
}
