<?php

declare(strict_types=1);

namespace App\Modules\Notification\Worker;

use App\Domain\DTO\Notification\NotificationDeliveryDTO;

/**
 * ARCHITECTURAL CONTRACT — NOTIFICATION WORKER
 *
 * This worker MUST:
 * - Decrypt payloads retrieved from notification_delivery_queue
 * - Delegate execution to existing subsystems (Email, Telegram, etc.)
 *
 * This worker MUST NOT:
 * - Render templates or messages
 * - Construct subjects, titles, or bodies
 * - Perform SMTP, HTTP, or external API calls
 * - Implement retry, backoff, scheduling, or delivery logic
 * - Duplicate logic owned by other modules
 *
 * UNDER NO CIRCUMSTANCES should this worker contain business logic.
 *
 * Any violation of this contract is considered an architectural defect.
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
