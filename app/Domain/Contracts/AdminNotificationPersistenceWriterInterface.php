<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Notification\AckNotificationReadDTO;
use App\Domain\DTO\Notification\PersistNotificationDTO;

interface AdminNotificationPersistenceWriterInterface
{
    /**
     * Persists a notification fact.
     *
     * @param PersistNotificationDTO $dto
     * @return int The ID of the persisted notification.
     */
    public function persist(PersistNotificationDTO $dto): int;

    /**
     * Acknowledges a notification as read.
     *
     * @param AckNotificationReadDTO $dto
     * @return void
     */
    public function acknowledgeRead(AckNotificationReadDTO $dto): void;
}
