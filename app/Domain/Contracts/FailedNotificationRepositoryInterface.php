<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Notification\NotificationDeliveryDTO;

interface FailedNotificationRepositoryInterface
{
    public function recordFailure(NotificationDeliveryDTO $notification, string $reason): void;

    public function markRetried(int $failureId): void;
}
