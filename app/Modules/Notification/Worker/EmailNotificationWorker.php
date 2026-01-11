<?php

declare(strict_types=1);

namespace App\Modules\Notification\Worker;

use App\Domain\DTO\Notification\NotificationDeliveryDTO;

class EmailNotificationWorker extends AbstractNotificationWorker
{
    public function process(NotificationDeliveryDTO $delivery): void
    {
        // 1. Decrypt (Handled by caller/worker infrastructure)
        // 2. Map payload to Email Queue
        // 3. Call EmailQueueWriter
    }
}
