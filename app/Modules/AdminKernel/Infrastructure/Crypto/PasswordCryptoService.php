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

namespace Maatify\AdminKernel\Infrastructure\Crypto;

use Maatify\AdminKernel\Application\Crypto\PasswordCryptoServiceInterface;
use Maatify\AdminKernel\Application\Crypto\DTO\PasswordHashDTO;
use Maatify\AdminKernel\Domain\Service\PasswordService;

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
 * - Pepper Ring application (Deterministic)
 * - NO legacy fallback
 *
 * STATUS:
 * - Phase 2 implementation (Pepper Ring)
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
         * - Applies active pepper from ring
         * - Uses Argon2id
         * - Returns array {hash, pepper_id}
         */
        $result = $this->passwordService->hash($plainPassword);

        return new PasswordHashDTO(
            hash     : $result['hash'],
            algorithm: 'argon2id',
            params   : ['pepper_id' => $result['pepper_id']]
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
         * - Looks up pepper by ID from params
         * - Verifies using that specific pepper
         * - Fails if pepper ID is unknown
         */
        $pepperId = (string)($passwordHash->params['pepper_id'] ?? '');
        
        return $this->passwordService->verify(
            $plainPassword,
            $passwordHash->hash,
            $pepperId
        );
    }
}
