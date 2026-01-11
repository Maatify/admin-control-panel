<?php

declare(strict_types=1);

namespace App\Modules\Notification\Queue;

use App\Domain\DTO\Notification\NotificationDeliveryDTO;

/**
 * NotificationQueueWriterInterface
 *
 * Persists notification delivery instructions.
 *
 * PAYLOAD CONTRACT:
 * The payload passed via NotificationDeliveryDTO MUST be semantic-only.
 * - It MUST contain raw data keys (e.g. 'template_key', 'language', 'context').
 * - It MUST NOT contain rendered content (e.g. 'subject', 'html_body').
 *
 * ARCHITECTURAL RULE:
 * Rendering is strictly forbidden at this layer.
 * All rendering responsibilities belong to the worker/subsystem (e.g. EmailWorker).
 */
interface NotificationQueueWriterInterface
{
    public function enqueue(NotificationDeliveryDTO $dto): void;
}
