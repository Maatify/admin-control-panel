<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-18 00:11
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Admin;

interface AdminTotpSecretStoreInterface
{
    /**
     * Persist a plaintext TOTP secret for the given admin.
     * Implementations MUST handle encryption internally.
     */
    public function store(int $adminId, string $plainSecret): void;

    /**
     * Retrieve the plaintext TOTP secret for the given admin.
     * Returns null if no secret is enrolled.
     */
    public function retrieve(int $adminId): ?string;

    /**
     * Check whether a TOTP secret exists for the given admin.
     * MUST NOT decrypt the secret.
     */
    public function exists(int $adminId): bool;

    /**
     * Remove the stored TOTP secret for the given admin.
     */
    public function delete(int $adminId): void;
}
