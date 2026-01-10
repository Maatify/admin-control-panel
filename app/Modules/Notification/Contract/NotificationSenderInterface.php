<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 17:41
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Notification\Contract;

use App\Modules\Notification\DTO\NotificationDeliveryResultDTO;
use App\Modules\Notification\Enum\NotificationChannel;

/**
 * NotificationSenderInterface
 *
 * Contract for channel-specific senders (Email, Telegram, SMS, Push).
 */
interface NotificationSenderInterface
{
    /**
     * Whether this sender supports the given channel.
     */
    public function supports(NotificationChannel $channel): bool;

    /**
     * Send a decrypted payload to a decrypted recipient.
     *
     * @param   string  $recipient
     * @param   string  $payload
     * @param   array<string, mixed>  $channelMeta
     *
     * @return NotificationDeliveryResultDTO
     */
    public function send(
        string $recipient,
        string $payload,
        array $channelMeta
    ): NotificationDeliveryResultDTO;
}
