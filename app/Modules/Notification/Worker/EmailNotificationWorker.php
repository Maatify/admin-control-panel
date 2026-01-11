<?php

declare(strict_types=1);

namespace App\Modules\Notification\Worker;

use App\Domain\DTO\Notification\NotificationDeliveryDTO;

/**
 * EmailNotificationWorker
 *
 * CONTRACT:
 * This worker acts as a thin orchestration layer for Email.
 *
 * RESPONSIBILITIES:
 * - MUST decrypt queued payloads only.
 * - MUST delegate execution to the Email Subsystem (EmailQueueWriter).
 *
 * PROHIBITIONS:
 * - MUST NOT render templates.
 * - MUST NOT build subjects or message bodies.
 * - MUST NOT perform SMTP / Telegram / external API calls directly.
 * - MUST NOT implement retry, backoff, scheduling, or state machines.
 * - MUST NOT duplicate logic already owned by Email or Notification subsystems.
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
