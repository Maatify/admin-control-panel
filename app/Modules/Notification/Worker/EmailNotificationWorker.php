<?php

declare(strict_types=1);

namespace App\Modules\Notification\Worker;

use App\Domain\DTO\Notification\NotificationDeliveryDTO;

/**
 * EmailNotificationWorker
 *
 * Thin orchestration worker for Email notifications.
 *
 * RESPONSIBILITIES:
 * - Decrypts the payload.
 * - Maps the semantic payload to the Email Queue structure.
 * - Delegates actual enqueueing to EmailQueueWriterInterface.
 *
 * PROHIBITIONS:
 * - NO rendering (must pass semantic data to EmailQueue).
 * - NO direct SMTP usage.
 * - NO retry logic.
 */
class EmailNotificationWorker extends AbstractNotificationWorker
{
    public function process(NotificationDeliveryDTO $delivery): void
    {
        // 1. Decrypt (Handled by caller/worker infrastructure)
        // 2. Map payload to Email Queue
        // 3. Call EmailQueueWriter
    }
}
