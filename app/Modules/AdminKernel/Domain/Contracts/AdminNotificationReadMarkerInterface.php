<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts;

use Maatify\AdminKernel\Domain\DTO\Notification\History\MarkNotificationReadDTO;

interface AdminNotificationReadMarkerInterface
{
    public function markAsRead(MarkNotificationReadDTO $dto): void;
}
