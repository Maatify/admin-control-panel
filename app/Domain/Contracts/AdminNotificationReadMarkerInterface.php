<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Notification\History\MarkNotificationReadDTO;

interface AdminNotificationReadMarkerInterface
{
    public function markAsRead(MarkNotificationReadDTO $dto): void;
}
