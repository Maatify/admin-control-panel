<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\FailedNotificationRepositoryInterface;
use App\Domain\DTO\Notification\NotificationDeliveryDTO;
use Throwable;

class NotificationFailureHandler
{
    public function __construct(
        private FailedNotificationRepositoryInterface $repository
    ) {
    }

    public function handle(NotificationDeliveryDTO $notification, Throwable $exception): void
    {
        $this->repository->recordFailure(
            $notification,
            $exception->getMessage()
        );
    }
}
