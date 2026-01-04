<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Notification\Preference\AdminNotificationPreferenceListDTO;
use App\Domain\DTO\Notification\Preference\GetAdminPreferencesQueryDTO;
use App\Domain\DTO\Notification\Preference\GetAdminPreferencesByTypeQueryDTO;

interface AdminNotificationPreferenceReaderInterface
{
    /**
     * @param GetAdminPreferencesQueryDTO $query
     * @return AdminNotificationPreferenceListDTO
     */
    public function getPreferences(GetAdminPreferencesQueryDTO $query): AdminNotificationPreferenceListDTO;

    /**
     * @param GetAdminPreferencesByTypeQueryDTO $query
     * @return AdminNotificationPreferenceListDTO
     */
    public function getPreferencesByType(GetAdminPreferencesByTypeQueryDTO $query): AdminNotificationPreferenceListDTO;
}
