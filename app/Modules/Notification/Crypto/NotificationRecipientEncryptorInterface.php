<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 17:35
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Notification\Crypto;

/**
 * NotificationRecipientEncryptorInterface
 *
 * Encrypts notification recipients (email, phone, chat_id, device token).
 */
interface NotificationRecipientEncryptorInterface
{
    /**
     * Encrypt a recipient identifier.
     *
     * @param   string  $recipient  Plain recipient value
     */
    public function encrypt(string $recipient): NotificationEncryptedValueDTO;
}
