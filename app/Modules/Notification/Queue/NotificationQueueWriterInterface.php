<?php

declare(strict_types=1);

namespace App\Modules\Notification\Queue;

use App\Domain\DTO\Notification\NotificationDeliveryDTO;

interface NotificationQueueWriterInterface
{
    public function enqueue(NotificationDeliveryDTO $dto): void;
}
