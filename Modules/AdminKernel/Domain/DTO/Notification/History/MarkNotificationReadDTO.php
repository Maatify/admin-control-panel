<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO\Notification\History;

final class MarkNotificationReadDTO
{
    public function __construct(
        public readonly int $adminId,
        public readonly int $notificationId
    ) {
    }
}
