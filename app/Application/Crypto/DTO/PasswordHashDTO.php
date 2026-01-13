<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-13 09:58
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Application\Crypto\DTO;

/**
 * PasswordHashDTO
 *
 * Canonical password hash container.
 *
 * Hash is produced using:
 * - Argon2id
 * - Server-side Pepper (NOT stored here)
 *
 * STATUS: LOCKED
 */
final readonly class PasswordHashDTO
{
    /**
     * @param string $hash
     * @param string $algorithm
     * @param array<string, int|string|float> $params
     */
    public function __construct(
        public string $hash,
        public string $algorithm, // e.g. argon2id
        public array $params = [] // cost, memory, threads (optional, informational)
    ) {}
}

