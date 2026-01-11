<?php

declare(strict_types=1);

namespace App\Modules\Notification\Worker;

use App\Domain\DTO\Notification\NotificationDeliveryDTO;

/**
 * AbstractNotificationWorker
 *
 * CONTRACT:
 * This worker acts as a thin orchestration layer.
 *
 * RESPONSIBILITIES:
 * - MUST decrypt queued payloads only.
 * - MUST delegate execution to existing subsystems.
 *
 * PROHIBITIONS:
 * - MUST NOT render templates.
 * - MUST NOT build subjects or message bodies.
 * - MUST NOT perform SMTP / Telegram / external API calls directly.
 * - MUST NOT implement retry, backoff, scheduling, or state machines.
 * - MUST NOT duplicate logic already owned by Email or Notification subsystems.
 *
 * All rendering and transport logic is strictly delegated to the specific subsystem.
 */
abstract class AbstractNotificationWorker
{
    /**
     * @param NotificationDeliveryDTO $delivery
     */
    abstract public function process(NotificationDeliveryDTO $delivery): void;
}
