<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Admin;

use Maatify\AdminKernel\Domain\DTO\Notification\Preference\AdminNotificationPreferenceDTO;
use Maatify\AdminKernel\Domain\DTO\Notification\Preference\UpdateAdminNotificationPreferenceDTO;

interface AdminNotificationPreferenceWriterInterface
{
    /**
     * @param UpdateAdminNotificationPreferenceDTO $dto
     * @return AdminNotificationPreferenceDTO
     */
    public function upsertPreference(UpdateAdminNotificationPreferenceDTO $dto): AdminNotificationPreferenceDTO;
}
