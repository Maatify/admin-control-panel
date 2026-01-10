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
 * NotificationPayloadEncryptorInterface
 *
 * Encrypts rendered notification payloads.
 */
interface NotificationPayloadEncryptorInterface
{
    /**
     * Encrypt a rendered notification payload.
     *
     * @param   string  $payload  Rendered message body
     */
    public function encrypt(string $payload): NotificationEncryptedValueDTO;
}
