<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 17:43
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Notification\Crypto;

/**
 * NotificationDecryptorInterface
 *
 * Decrypts encrypted notification values
 * stored in the delivery queue.
 */
interface NotificationDecryptorInterface
{
    /**
     * Decrypt an encrypted notification value.
     *
     * @param   NotificationEncryptedValueDTO  $value
     *
     * @return string Decrypted plain value
     */
    public function decrypt(NotificationEncryptedValueDTO $value): string;
}
