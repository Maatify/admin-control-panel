<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts;

use Maatify\AdminKernel\Domain\DTO\Notification\NotificationDeliveryDTO;

interface FailedNotificationRepositoryInterface
{
    public function recordFailure(NotificationDeliveryDTO $notification, string $reason): void;

    public function markRetried(int $failureId): void;
}
