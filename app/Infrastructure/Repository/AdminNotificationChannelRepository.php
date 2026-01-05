<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\AdminNotificationChannelRepositoryInterface;
use App\Domain\DTO\AdminNotificationChannelDTO;
use App\Domain\Enum\NotificationChannelType;
use PDO;
use RuntimeException;

class AdminNotificationChannelRepository implements AdminNotificationChannelRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function getEnabledChannelsForAdmin(int $adminId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, admin_id, channel_type, config, is_enabled
            FROM admin_notification_channels
            WHERE admin_id = ? AND is_enabled = 1
        ");
        $stmt->execute([$adminId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (! is_array($rows)) {
            return [];
        }

        $channels = [];
        foreach ($rows as $row) {
            /** @var array{id: int|string, admin_id: int|string, channel_type: string, config: string, is_enabled: int|string} $row */

            $channelType = NotificationChannelType::tryFrom($row['channel_type']);
            if ($channelType === null) {
                continue;
            }

            $config = json_decode($row['config'], true);
            if (! is_array($config)) {
                $config = [];
            }

            /** @var array<string, scalar> $config */
            $channels[] = new AdminNotificationChannelDTO(
                (int)$row['id'],
                (int)$row['admin_id'],
                $channelType,
                $config,
                (bool)$row['is_enabled']
            );
        }

        return $channels;
    }

    public function getChannelConfig(int $channelId): array
    {
        $stmt = $this->pdo->prepare("SELECT config FROM admin_notification_channels WHERE id = ?");
        $stmt->execute([$channelId]);
        $configJson = $stmt->fetchColumn();

        if ($configJson === false) {
             throw new RuntimeException("Channel not found: $channelId");
        }

        $config = json_decode((string)$configJson, true);
        if (! is_array($config)) {
            return [];
        }

        /** @var array<string, scalar> $config */
        return $config;
    }

    public function registerChannel(int $adminId, string $channelType, array $config): void
    {
        $stmt = $this->pdo->prepare("SELECT id FROM admin_notification_channels WHERE admin_id = ? AND channel_type = ?");
        $stmt->execute([$adminId, $channelType]);
        $id = $stmt->fetchColumn();

        $configJson = json_encode($config);
        if ($configJson === false) {
            throw new RuntimeException("Invalid config JSON");
        }

        if ($id !== false) {
            // Update
            $stmt = $this->pdo->prepare("UPDATE admin_notification_channels SET config = ?, is_enabled = 1 WHERE id = ?");
            $stmt->execute([$configJson, $id]);
        } else {
            // Insert
            $stmt = $this->pdo->prepare("INSERT INTO admin_notification_channels (admin_id, channel_type, config, is_enabled) VALUES (?, ?, ?, 1)");
            $stmt->execute([$adminId, $channelType, $configJson]);
        }
    }
}
