<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-18 00:12
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Service;

use Maatify\AdminKernel\Domain\Contracts\AdminTotpSecretStoreInterface;
use Maatify\AdminKernel\Domain\Contracts\AdminTotpSecretRepositoryInterface;
use Maatify\AdminKernel\Application\Crypto\TotpSecretCryptoServiceInterface;

final class AdminTotpSecretStore implements AdminTotpSecretStoreInterface
{
    public function __construct(
        private readonly AdminTotpSecretRepositoryInterface $repository,
        private readonly TotpSecretCryptoServiceInterface $crypto
    )
    {
    }

    public function store(int $adminId, string $plainSecret): void
    {
        $encrypted = $this->crypto->encryptTotpSeed($plainSecret);
        $this->repository->save($adminId, $encrypted);
    }

    public function retrieve(int $adminId): ?string
    {
        $encrypted = $this->repository->get($adminId);
        if ($encrypted === null) {
            return null;
        }

        return $this->crypto->decryptTotpSeed($encrypted);
    }

    public function exists(int $adminId): bool
    {
        return $this->repository->get($adminId) !== null;
    }

    public function delete(int $adminId): void
    {
        $this->repository->delete($adminId);
    }
}
