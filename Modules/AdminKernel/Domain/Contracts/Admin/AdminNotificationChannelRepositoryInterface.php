<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Admin;

use Maatify\AdminKernel\Domain\DTO\AdminNotificationChannelDTO;

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

    /**
     * Registers or updates a notification channel for an admin.
     * @param int $adminId
     * @param string $channelType
     * @param array<string, scalar> $config
     */
    public function registerChannel(int $adminId, string $channelType, array $config): void;
}
