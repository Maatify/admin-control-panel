<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-13 09:49
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Crypto;

use Maatify\AdminKernel\Domain\DTO\Crypto\EncryptedPayloadDTO;

/**
 * AdminIdentifierCryptoServiceInterface
 *
 * CANONICAL AUTHORITY for all Admin Identifier cryptographic operations.
 *
 * This interface defines the ONLY allowed contract for:
 * - Admin email encryption / decryption
 * - Admin email blind index derivation
 *
 * HARD RULES:
 * - No controller, reader, or repository may perform:
 *   - openssl_* calls
 *   - hash_hmac for admin identifiers
 * - All such operations MUST be routed through an implementation of this interface.
 *
 * This interface is INTENTIONALLY behavior-agnostic.
 * No algorithms, contexts, or providers are implied here.
 *
 * STATUS: LOCKED (Skeleton Phase)
 */
interface AdminIdentifierCryptoServiceInterface
{
    /**
     * Encrypt an admin email identifier.
     *
     * @param   string  $plainEmail
     *
     * @return EncryptedPayloadDTO Encrypted recipient DTO
     */
    public function encryptEmail(string $plainEmail): EncryptedPayloadDTO;

    /**
     * Decrypt an admin email identifier.
     *
     * @param   EncryptedPayloadDTO  $encryptedIdentifier
     *
     * @return string Plain admin email
     */
    public function decryptEmail(EncryptedPayloadDTO $encryptedIdentifier): string;

    /**
     * Derive a blind index for an admin email.
     *
     * @param   string  $plainEmail
     *
     * @return string Blind index value
     */
    public function deriveEmailBlindIndex(string $plainEmail): string;
}
