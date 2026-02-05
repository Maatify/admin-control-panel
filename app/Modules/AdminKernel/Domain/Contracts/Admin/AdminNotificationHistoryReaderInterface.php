<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Admin;

use Maatify\AdminKernel\Domain\DTO\Notification\History\AdminNotificationHistoryQueryDTO;
use Maatify\AdminKernel\Domain\DTO\Notification\History\AdminNotificationHistoryViewDTO;

interface AdminNotificationHistoryReaderInterface
{
    /**
     * @param AdminNotificationHistoryQueryDTO $query
     * @return AdminNotificationHistoryViewDTO[]
     */
    public function getHistory(AdminNotificationHistoryQueryDTO $query): array;
}
