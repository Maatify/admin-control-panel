<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-13 09:50
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Application\Crypto;

use App\Application\Crypto\DTO\EncryptedPayloadDTO;

/**
 * NotificationCryptoServiceInterface
 *
 * CANONICAL AUTHORITY for all Notification / Email Queue cryptographic operations.
 *
 * This interface governs:
 * - Email recipient encryption / decryption
 * - Email payload encryption / decryption
 *
 * HARD RULES:
 * - Email Queue writers and workers MUST NOT select crypto contexts directly.
 * - All cryptographic operations MUST be delegated to this service.
 *
 * This service is intentionally isolated from Admin Identity cryptography.
 *
 * STATUS: LOCKED (Skeleton Phase)
 */
interface NotificationCryptoServiceInterface
{
    /**
     * Encrypt an email recipient address.
     *
     * @param   string  $email
     *
     * @return EncryptedPayloadDTO Encrypted recipient DTO
     */
    public function encryptRecipient(string $email): EncryptedPayloadDTO;

    /**
     * Decrypt an email recipient address.
     *
     * @param   EncryptedPayloadDTO  $encryptedRecipient
     *
     * @return string Plain email address
     */
    public function decryptRecipient(EncryptedPayloadDTO $encryptedRecipient): string;

    /**
     * Encrypt an email payload.
     *
     * @param array<string, mixed> $payload
     */
    public function encryptPayload(array $payload): EncryptedPayloadDTO;

    /**
     * Decrypt an email payload.
     *
     * @param   EncryptedPayloadDTO  $encryptedPayload
     *
     * @return array<string, mixed>
     */
    public function decryptPayload(EncryptedPayloadDTO $encryptedPayload): array;
}
