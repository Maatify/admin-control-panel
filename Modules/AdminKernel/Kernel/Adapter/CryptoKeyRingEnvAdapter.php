<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-30 16:08
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Kernel\Adapter;

use Maatify\AdminKernel\Kernel\DTO\AdminRuntimeConfigDTO;

final class CryptoKeyRingEnvAdapter
{
    /**
     * @return array<string, mixed>
     */
    public static function adapt(AdminRuntimeConfigDTO $config): array
    {
        return [
            'CRYPTO_KEYS' => $config->cryptoKeysJson,
            'CRYPTO_ACTIVE_KEY_ID' => $config->cryptoActiveKeyId,
        ];
    }
}
