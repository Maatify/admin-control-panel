<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\AdminNotificationPreferenceRepositoryInterface;
use App\Domain\Enum\NotificationChannelType;
use PDO;

class AdminNotificationPreferenceRepository implements AdminNotificationPreferenceRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function getEnabledChannelsForNotification(int $adminId, string $notificationType): array
    {
        $stmt = $this->pdo->prepare("
            SELECT channel_type
            FROM admin_notification_preferences
            WHERE admin_id = ? AND notification_type = ? AND is_enabled = 1
        ");
        $stmt->execute([$adminId, $notificationType]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (! is_array($rows)) {
            return [];
        }

        $types = [];
        foreach ($rows as $row) {
             /** @var array{channel_type: string} $row */
             $type = NotificationChannelType::tryFrom($row['channel_type']);
             if ($type !== null) {
                 $types[] = $type;
             }
        }

        return $types;
    }
}
