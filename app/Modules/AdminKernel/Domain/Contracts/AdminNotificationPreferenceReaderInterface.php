<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts;

use Maatify\AdminKernel\Domain\DTO\Notification\Preference\AdminNotificationPreferenceListDTO;
use Maatify\AdminKernel\Domain\DTO\Notification\Preference\GetAdminPreferencesQueryDTO;
use Maatify\AdminKernel\Domain\DTO\Notification\Preference\GetAdminPreferencesByTypeQueryDTO;

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
