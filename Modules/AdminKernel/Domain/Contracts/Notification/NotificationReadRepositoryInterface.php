<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Notification;

use DateTimeImmutable;
use Maatify\AdminKernel\Domain\DTO\NotificationSummaryDTO;

interface NotificationReadRepositoryInterface
{
    /**
     * @param int $adminId
     * @return array<NotificationSummaryDTO>
     */
    public function findByAdminId(int $adminId): array;

    /**
     * @param string $status
     * @return array<NotificationSummaryDTO>
     */
    public function findByStatus(string $status): array;

    /**
     * @param string $channel
     * @return array<NotificationSummaryDTO>
     */
    public function findByChannel(string $channel): array;

    /**
     * @param DateTimeImmutable $from
     * @param DateTimeImmutable $to
     * @return array<NotificationSummaryDTO>
     */
    public function findByDateRange(DateTimeImmutable $from, DateTimeImmutable $to): array;
}
