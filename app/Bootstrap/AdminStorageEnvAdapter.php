<?php

declare(strict_types=1);

namespace Maatify\AdminControlPanel\Bootstrap;

use Maatify\Storage\Config\StorageConfig;
use RuntimeException;

final class AdminStorageEnvAdapter
{
    /**
     * Build the reusable Storage configuration from the Admin-owned
     * environment without exposing the reusable module's generic keys to the
     * host composition root.
     *
     * @param array<string, mixed> $adminEnv
     */
    public static function forStorage(array $adminEnv, string $appRoot): StorageConfig
    {
        $storageEnv = self::adapt($adminEnv);
        $localBasePath = $storageEnv['LOCAL_BASE_PATH'] ?? '';

        if ($localBasePath === '') {
            $localBasePath = 'public/admin/images';
        }

        if (!str_starts_with($localBasePath, '/')) {
            $localBasePath = rtrim($appRoot, '/') . '/' . ltrim($localBasePath, '/');
        }

        $storageEnv['LOCAL_BASE_PATH'] = $localBasePath;

        return StorageConfig::fromEnv($storageEnv);
    }

    /**
     * Translate Admin-owned storage keys to the reusable Storage module contract.
     *
     * @param array<string, mixed> $adminEnv
     * @return array<string, string>
     */
    public static function adapt(array $adminEnv): array
    {
        $mapping = [
            'STORAGE_DRIVER' => 'STORAGE_DRIVER',
            'ADMIN_LOCAL_BASE_PATH' => 'LOCAL_BASE_PATH',
            'ADMIN_LOCAL_BASE_URL' => 'LOCAL_BASE_URL',
            'ADMIN_DO_SPACES_KEY' => 'DO_SPACES_KEY',
            'ADMIN_DO_SPACES_SECRET' => 'DO_SPACES_SECRET',
            'ADMIN_DO_SPACES_REGION' => 'DO_SPACES_REGION',
            'ADMIN_DO_SPACES_ENDPOINT' => 'DO_SPACES_ENDPOINT',
            'ADMIN_DO_SPACES_BUCKET' => 'DO_SPACES_BUCKET',
            'ADMIN_DO_SPACES_CDN_URL' => 'DO_SPACES_CDN_URL',
            'ADMIN_DO_SPACES_ACL' => 'DO_SPACES_ACL',
        ];

        $storageEnv = [];

        foreach ($mapping as $adminKey => $storageKey) {
            if (!array_key_exists($adminKey, $adminEnv)) {
                continue;
            }

            $value = $adminEnv[$adminKey];
            if (!is_string($value)) {
                throw new RuntimeException("Storage config key {$adminKey} must be a string.");
            }

            $storageEnv[$storageKey] = $value;
        }

        return $storageEnv;
    }
}
