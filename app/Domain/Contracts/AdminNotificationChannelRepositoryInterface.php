<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\AdminNotificationChannelDTO;

interface AdminNotificationChannelRepositoryInterface
{
    /**
     * @param int $adminId
     * @return array<AdminNotificationChannelDTO>
     */
    public function getEnabledChannelsForAdmin(int $adminId): array;

    /**
     * @param int $channelId
     * @return array<string, scalar>
     */
    public function getChannelConfig(int $channelId): array;
}
