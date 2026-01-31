<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository;

use Maatify\AdminKernel\Domain\Contracts\AdminNotificationPersistenceWriterInterface;
use Maatify\AdminKernel\Domain\DTO\Notification\AckNotificationReadDTO;
use Maatify\AdminKernel\Domain\DTO\Notification\PersistNotificationDTO;
use PDO;

final class PdoAdminNotificationPersistenceRepository implements AdminNotificationPersistenceWriterInterface
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function persist(PersistNotificationDTO $dto): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_notifications (
                admin_id,
                notification_type,
                channel_type,
                intent_id,
                created_at
            ) VALUES (
                :admin_id,
                :notification_type,
                :channel_type,
                :intent_id,
                :created_at
            )'
        );

        $createdAt = $dto->createdAt ?? new \DateTimeImmutable();

        $stmt->bindValue(':admin_id', $dto->adminId, PDO::PARAM_INT);
        $stmt->bindValue(':notification_type', $dto->notificationType, PDO::PARAM_STR);
        $stmt->bindValue(':channel_type', $dto->channelType->value, PDO::PARAM_STR);
        $stmt->bindValue(':intent_id', $dto->intentId, $dto->intentId === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':created_at', $createdAt->format('Y-m-d H:i:s'), PDO::PARAM_STR);

        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    public function acknowledgeRead(AckNotificationReadDTO $dto): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE admin_notifications
             SET read_at = :read_at
             WHERE id = :id'
        );

        $stmt->bindValue(':read_at', $dto->readAt->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->bindValue(':id', $dto->notificationId, PDO::PARAM_INT);

        $stmt->execute();
    }
}
