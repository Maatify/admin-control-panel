<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\AdminNotificationReadMarkerInterface;
use App\Domain\DTO\Notification\History\MarkNotificationReadDTO;
use PDO;

final class PdoAdminNotificationReadMarker implements AdminNotificationReadMarkerInterface
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function markAsRead(MarkNotificationReadDTO $dto): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE admin_notifications
             SET read_at = NOW()
             WHERE id = :id
               AND admin_id = :admin_id
               AND read_at IS NULL'
        );

        $stmt->bindValue(':id', $dto->notificationId, PDO::PARAM_INT);
        $stmt->bindValue(':admin_id', $dto->adminId, PDO::PARAM_INT);

        $stmt->execute();
    }
}
