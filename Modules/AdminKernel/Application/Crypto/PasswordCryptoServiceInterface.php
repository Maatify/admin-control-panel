<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-13 09:53
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Crypto;

use Maatify\AdminKernel\Application\Crypto\DTO\PasswordHashDTO;

/**
 * PasswordCryptoServiceInterface
 *
 * CANONICAL POLICY HOLDER for password hashing and verification.
 *
 * This interface defines the SINGLE authority for:
 * - Password hashing
 * - Password verification
 *
 * Password hashing policy:
 * - Argon2id + server-side Pepper
 *
 * Pepper handling is INTERNAL to the implementation and MUST NOT
 * be exposed or serialized.
 *
 * NOTE:
 * - This interface intentionally does NOT dictate algorithm choice.
 * - Migration and compatibility decisions are handled at implementation time.
 *
 * HARD RULES:
 * - No service may hash or verify passwords outside this interface.
 *
 * STATUS: LOCKED (Skeleton Phase)
 */
interface PasswordCryptoServiceInterface
{
    /**
     * Hash a plaintext password.
     *
     * @param   string  $plainPassword
     *
     * @return PasswordHashDTO Password hash DTO (implementation-defined)
     */
    public function hashPassword(string $plainPassword): PasswordHashDTO;

    /**
     * Verify a plaintext password against a stored hash.
     *
     * @param   string  $plainPassword
     * @param   PasswordHashDTO  $passwordHash
     *
     * @return bool
     */
    public function verifyPassword(string $plainPassword, PasswordHashDTO $passwordHash): bool;
}
