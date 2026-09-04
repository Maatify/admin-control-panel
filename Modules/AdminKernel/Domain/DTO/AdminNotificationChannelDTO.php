<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\DTO;

use Maatify\AdminKernel\Domain\Enum\NotificationChannelType;

final class AdminNotificationChannelDTO
{
    /**
     * @param array<string, scalar> $config
     */
    public function __construct(
        public readonly int $id,
        public readonly int $adminId,
        public readonly NotificationChannelType $channelType,
        public readonly array $config,
        public readonly bool $isEnabled
    ) {
    }
}
