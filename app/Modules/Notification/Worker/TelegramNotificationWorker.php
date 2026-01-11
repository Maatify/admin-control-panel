<?php

declare(strict_types=1);

namespace App\Modules\Notification\Worker;

use App\Domain\DTO\Notification\NotificationDeliveryDTO;

/**
 * TelegramNotificationWorker
 *
 * Thin orchestration worker for Telegram notifications.
 *
 * RESPONSIBILITIES:
 * - Decrypts the payload.
 * - Delegates to the Telegram Sender/Handler.
 *
 * PROHIBITIONS:
 * - NO complex logic.
 * - NO retry logic.
 */
class TelegramNotificationWorker extends AbstractNotificationWorker
{
    public function process(NotificationDeliveryDTO $delivery): void
    {
        // 1. Decrypt
        // 2. Call Telegram Sender
    }
}
