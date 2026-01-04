<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Domain\Contracts\NotificationDispatcherInterface;
use App\Domain\DTO\NotificationMessageDTO;

final class NullNotificationDispatcher implements NotificationDispatcherInterface
{
    public function dispatch(NotificationMessageDTO $message): void
    {
        // No-op
    }
}
