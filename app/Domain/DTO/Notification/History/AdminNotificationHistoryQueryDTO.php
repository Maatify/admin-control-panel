<?php

declare(strict_types=1);

namespace App\Domain\DTO\Notification\History;

use DateTimeImmutable;

final class AdminNotificationHistoryQueryDTO
{
    public function __construct(
        public readonly int $adminId,
        public readonly int $page,
        public readonly int $limit,
        public readonly ?string $notificationType = null,
        public readonly ?bool $isRead = null,
        public readonly ?DateTimeImmutable $fromDate = null,
        public readonly ?DateTimeImmutable $toDate = null
    ) {
    }
}
