<?php

declare(strict_types=1);

namespace App\Modules\Notification\Worker;

use App\Domain\DTO\Notification\NotificationDeliveryDTO;

abstract class AbstractNotificationWorker
{
    /**
     * @param NotificationDeliveryDTO $delivery
     */
    abstract public function process(NotificationDeliveryDTO $delivery): void;
}
