<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Notification;

readonly class NotificationRoutingContextDTO
{
    /**
     * @param int $adminId The ID of the admin receiving the notification.
     * @param string $notificationType The type of notification being sent.
     */
    public function __construct(
        public int $adminId,
        public string $notificationType
    ) {
    }
}
