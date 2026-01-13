<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-13 10:39
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Infrastructure\Crypto;

use App\Application\Crypto\PasswordCryptoServiceInterface;
use App\Application\Crypto\DTO\PasswordHashDTO;
use App\Domain\Service\PasswordService;

/**
 * PasswordCryptoService
 *
 * Infrastructure-level implementation of PasswordCryptoServiceInterface.
 *
 * IMPORTANT:
 * - This class performs NO new cryptographic logic.
 * - It delegates ALL behavior to the existing PasswordService.
 * - It exists solely to establish a canonical authority boundary.
 *
 * BEHAVIOR:
 * - Argon2id hashing
 * - Pepper application
 * - Old pepper fallback
 * - Legacy plaintext fallback (if present in PasswordService)
 *
 * STATUS:
 * - Phase 1 implementation
 * - NO behavior change
 * - SAFE wrapper
 */
final class PasswordCryptoService implements PasswordCryptoServiceInterface
{
    private PasswordService $passwordService;

    public function __construct(PasswordService $passwordService)
    {
        $this->passwordService = $passwordService;
    }

    /**
     * Hash a plaintext password.
     *
     * Delegates directly to PasswordService.
     */
    public function hashPassword(string $plainPassword): PasswordHashDTO
    {
        /**
         * PasswordService::hash()
         * - Applies pepper
         * - Uses Argon2id
         * - Returns string hash
         */
        $hash = $this->passwordService->hash($plainPassword);

        return new PasswordHashDTO(
            hash     : $hash,
            algorithm: 'argon2id',
            params   : []
        );
    }

    /**
     * Verify a plaintext password against a stored hash.
     *
     * Delegates directly to PasswordService.
     */
    public function verifyPassword(string $plainPassword, PasswordHashDTO $passwordHash): bool
    {
        /**
         * PasswordService::verify()
         * - Handles pepper
         * - Handles old pepper fallback
         * - Handles legacy plaintext fallback (if implemented)
         */
        return $this->passwordService->verify(
            $plainPassword,
            $passwordHash->hash
        );
    }
}
