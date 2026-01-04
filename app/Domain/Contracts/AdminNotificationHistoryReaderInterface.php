<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Notification\History\AdminNotificationHistoryQueryDTO;
use App\Domain\DTO\Notification\History\AdminNotificationHistoryViewDTO;

interface AdminNotificationHistoryReaderInterface
{
    /**
     * @param AdminNotificationHistoryQueryDTO $query
     * @return AdminNotificationHistoryViewDTO[]
     */
    public function getHistory(AdminNotificationHistoryQueryDTO $query): array;
}
