<?php

declare(strict_types=1);

namespace App\Modules\Notification\Worker;

use App\Domain\DTO\Notification\NotificationDeliveryDTO;

/**
 * AbstractNotificationWorker
 *
 * This worker acts as a thin orchestration layer.
 * It is responsible ONLY for:
 * 1. Decrypting notification payloads
 * 2. Delegating execution to existing subsystems (e.g. EmailQueueWriter)
 *
 * It MUST NOT:
 * - Perform any template rendering
 * - Execute direct transport calls (SMTP, API)
 * - Implement retry strategies or scheduling logic
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
