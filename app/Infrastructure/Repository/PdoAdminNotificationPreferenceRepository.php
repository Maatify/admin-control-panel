<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\AdminNotificationPreferenceReaderInterface;
use App\Domain\Contracts\AdminNotificationPreferenceWriterInterface;
use App\Domain\Contracts\AdminNotificationPreferenceRepositoryInterface;
use App\Domain\DTO\Notification\Preference\AdminNotificationPreferenceDTO;
use App\Domain\DTO\Notification\Preference\AdminNotificationPreferenceListDTO;
use App\Domain\DTO\Notification\Preference\UpdateAdminNotificationPreferenceDTO;
use App\Domain\Enum\NotificationChannelType as LegacyNotificationChannelType;
use App\Domain\Notification\NotificationChannelType;
use PDO;

class PdoAdminNotificationPreferenceRepository implements
    AdminNotificationPreferenceReaderInterface,
    AdminNotificationPreferenceWriterInterface,
    AdminNotificationPreferenceRepositoryInterface
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function getPreferences(int $adminId): AdminNotificationPreferenceListDTO
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM admin_notification_preferences WHERE admin_id = :admin_id'
        );
        $stmt->execute(['admin_id' => $adminId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $preferences = [];
        if ($rows !== false) {
            foreach ($rows as $row) {
                $preferences[] = $this->mapRowToDTO($row);
            }
        }

        return new AdminNotificationPreferenceListDTO($preferences);
    }

    public function getPreferencesByType(int $adminId, string $notificationType): AdminNotificationPreferenceListDTO
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM admin_notification_preferences WHERE admin_id = :admin_id AND notification_type = :notification_type'
        );
        $stmt->execute(['admin_id' => $adminId, 'notification_type' => $notificationType]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $preferences = [];
        if ($rows !== false) {
            foreach ($rows as $row) {
                $preferences[] = $this->mapRowToDTO($row);
            }
        }

        return new AdminNotificationPreferenceListDTO($preferences);
    }

    public function upsertPreference(UpdateAdminNotificationPreferenceDTO $dto): AdminNotificationPreferenceDTO
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO admin_notification_preferences (admin_id, notification_type, channel_type, is_enabled, created_at, updated_at)
             VALUES (:admin_id, :notification_type, :channel_type, :is_enabled, NOW(), NOW())
             ON DUPLICATE KEY UPDATE is_enabled = :is_enabled_update, updated_at = NOW()'
        );

        $stmt->execute([
            'admin_id' => $dto->adminId,
            'notification_type' => $dto->notificationType,
            'channel_type' => $dto->channelType->value,
            'is_enabled' => $dto->isEnabled ? 1 : 0,
            'is_enabled_update' => $dto->isEnabled ? 1 : 0
        ]);

        // Fetch and return the updated/created record
        $stmt = $this->connection->prepare(
            'SELECT * FROM admin_notification_preferences WHERE admin_id = :admin_id AND notification_type = :notification_type AND channel_type = :channel_type'
        );
        $stmt->execute([
            'admin_id' => $dto->adminId,
            'notification_type' => $dto->notificationType,
            'channel_type' => $dto->channelType->value
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
             throw new \RuntimeException('Failed to retrieve upserted preference');
        }

        /** @var array{id: int, admin_id: int, notification_type: string, channel_type: string, is_enabled: int, created_at: string, updated_at: string} $row */
        return $this->mapRowToDTO($row);
    }

    /**
     * Legacy support for AdminNotificationPreferenceRepositoryInterface
     *
     * @param int $adminId
     * @param string $notificationType
     * @return array<LegacyNotificationChannelType>
     */
    public function getEnabledChannelsForNotification(int $adminId, string $notificationType): array
    {
         $preferencesList = $this->getPreferencesByType($adminId, $notificationType);
         $enabledChannels = [];

         foreach ($preferencesList->preferences as $preference) {
             if ($preference->isEnabled) {
                 // Map new Enum to Legacy Enum
                 $legacyChannel = LegacyNotificationChannelType::tryFrom($preference->channelType->value);
                 if ($legacyChannel) {
                     $enabledChannels[] = $legacyChannel;
                 }
             }
         }

         return $enabledChannels;
    }

    /**
     * @param array<string, mixed> $row
     * @return AdminNotificationPreferenceDTO
     */
    private function mapRowToDTO(array $row): AdminNotificationPreferenceDTO
    {
        /** @var int $adminId */
        $adminId = $row['admin_id'];
        /** @var string $notificationType */
        $notificationType = $row['notification_type'];
        /** @var string $channelTypeStr */
        $channelTypeStr = $row['channel_type'];
        /** @var int|bool $isEnabledRaw */
        $isEnabledRaw = $row['is_enabled'];
        /** @var string $createdAt */
        $createdAt = $row['created_at'];
        /** @var string $updatedAt */
        $updatedAt = $row['updated_at'];

        $channelType = NotificationChannelType::from($channelTypeStr);
        $isEnabled = (bool)$isEnabledRaw;

        return new AdminNotificationPreferenceDTO(
            $adminId,
            $notificationType,
            $channelType,
            $isEnabled,
            $createdAt,
            $updatedAt
        );
    }
}
