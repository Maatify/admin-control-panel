<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Notification\Preference\AdminNotificationPreferenceDTO;
use App\Domain\DTO\Notification\Preference\AdminNotificationPreferenceListDTO;

interface AdminNotificationPreferenceReaderInterface
{
    /**
     * @param int $adminId
     * @return AdminNotificationPreferenceListDTO
     */
    public function getPreferences(int $adminId): AdminNotificationPreferenceListDTO;

    /**
     * @param int $adminId
     * @param string $notificationType
     * @return AdminNotificationPreferenceListDTO
     */
    public function getPreferencesByType(int $adminId, string $notificationType): AdminNotificationPreferenceListDTO;
}
