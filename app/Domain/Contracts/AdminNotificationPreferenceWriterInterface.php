<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Notification\Preference\AdminNotificationPreferenceDTO;
use App\Domain\DTO\Notification\Preference\UpdateAdminNotificationPreferenceDTO;

interface AdminNotificationPreferenceWriterInterface
{
    /**
     * @param UpdateAdminNotificationPreferenceDTO $dto
     * @return AdminNotificationPreferenceDTO
     */
    public function upsertPreference(UpdateAdminNotificationPreferenceDTO $dto): AdminNotificationPreferenceDTO;
}
