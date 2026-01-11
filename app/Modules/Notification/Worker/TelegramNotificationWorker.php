<?php

declare(strict_types=1);

namespace App\Modules\Notification\Worker;

use App\Domain\DTO\Notification\NotificationDeliveryDTO;

/**
 * TelegramNotificationWorker
 *
 * CONTRACT:
 * This worker acts as a thin orchestration layer for Telegram.
 *
 * RESPONSIBILITIES:
 * - MUST decrypt queued payloads only.
 * - MUST delegate execution to the Telegram Subsystem.
 *
 * PROHIBITIONS:
 * - MUST NOT render templates.
 * - MUST NOT build subjects or message bodies.
 * - MUST NOT perform SMTP / Telegram / external API calls directly.
 * - MUST NOT implement retry, backoff, scheduling, or state machines.
 * - MUST NOT duplicate logic already owned by Email or Notification subsystems.
 */
class TelegramNotificationWorker extends AbstractNotificationWorker
{
    public function process(NotificationDeliveryDTO $delivery): void
    {
        // 1. Decrypt
        // 2. Call Telegram Sender
    }
}
