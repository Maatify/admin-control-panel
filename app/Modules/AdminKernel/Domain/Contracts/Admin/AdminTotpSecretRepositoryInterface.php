<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-17 23:28
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Admin;

use Maatify\AdminKernel\Domain\DTO\Crypto\EncryptedPayloadDTO;

interface AdminTotpSecretRepositoryInterface
{
    public function save(int $adminId, EncryptedPayloadDTO $encryptedSeed): void;

    public function get(int $adminId): ?EncryptedPayloadDTO;

    public function delete(int $adminId): void;
}
