<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Repository;

use Maatify\AdminKernel\Domain\Contracts\Admin\AdminNotificationHistoryReaderInterface;
use Maatify\AdminKernel\Domain\DTO\Notification\History\AdminNotificationHistoryQueryDTO;
use Maatify\AdminKernel\Domain\DTO\Notification\History\AdminNotificationHistoryViewDTO;
use PDO;

final class PdoAdminNotificationHistoryReader implements AdminNotificationHistoryReaderInterface
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    /**
     * @param AdminNotificationHistoryQueryDTO $query
     * @return AdminNotificationHistoryViewDTO[]
     */
    public function getHistory(AdminNotificationHistoryQueryDTO $query): array
    {
        $sql = 'SELECT id, notification_type, channel, created_at, read_at
                FROM admin_notifications
                WHERE admin_id = :admin_id';

        $params = [
            ':admin_id' => $query->adminId,
        ];

        if ($query->notificationType !== null) {
            $sql .= ' AND notification_type = :notification_type';
            $params[':notification_type'] = $query->notificationType;
        }

        if ($query->isRead === true) {
            $sql .= ' AND read_at IS NOT NULL';
        } elseif ($query->isRead === false) {
            $sql .= ' AND read_at IS NULL';
        }

        if ($query->fromDate !== null) {
            $sql .= ' AND created_at >= :from_date';
            $params[':from_date'] = $query->fromDate->format('Y-m-d H:i:s');
        }

        if ($query->toDate !== null) {
            $sql .= ' AND created_at <= :to_date';
            $params[':to_date'] = $query->toDate->format('Y-m-d H:i:s');
        }

        $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $params[':limit'] = $query->limit;
        $params[':offset'] = ($query->page - 1) * $query->limit;

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            /**
             * @var array{
             *     id: int,
             *     notification_type: string,
             *     channel: string,
             *     created_at: string,
             *     read_at: ?string
             * } $row
             */
            $readAt = isset($row['read_at']) ? new \DateTimeImmutable($row['read_at']) : null;
            $results[] = new AdminNotificationHistoryViewDTO(
                notificationId: $row['id'],
                notificationType: $row['notification_type'],
                channel: $row['channel'],
                createdAt: new \DateTimeImmutable($row['created_at']),
                readAt: $readAt,
                isRead: $readAt !== null
            );
        }

        return $results;
    }
}
