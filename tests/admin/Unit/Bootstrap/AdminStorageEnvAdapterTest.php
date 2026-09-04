<?php

declare(strict_types=1);

namespace Tests\Unit\Bootstrap;

use Maatify\AdminControlPanel\Bootstrap\AdminStorageEnvAdapter;
use Maatify\Storage\Config\StorageConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AdminStorageEnvAdapterTest extends TestCase
{
    public function testBuildsStorageConfigFromAdminOwnedKeys(): void
    {
        $config = AdminStorageEnvAdapter::forStorage([
            'ADMIN_LOCAL_BASE_PATH' => 'storage/admin-images',
            'ADMIN_LOCAL_BASE_URL' => '/images',
        ], '/srv/admin-control-panel');

        self::assertInstanceOf(StorageConfig::class, $config);
        self::assertSame(
            '/srv/admin-control-panel/storage/admin-images',
            $config->local?->basePath,
        );
        self::assertSame('/images', $config->local?->baseUrl);
    }

    public function testUsesAdminDefaultStoragePathWhenAdminPathIsEmpty(): void
    {
        $config = AdminStorageEnvAdapter::forStorage([
            'ADMIN_LOCAL_BASE_PATH' => '',
        ], '/srv/admin-control-panel');

        self::assertSame(
            '/srv/admin-control-panel/public/admin/images',
            $config->local?->basePath,
        );
    }

    public function testMapsAdminStorageKeysToTheReusableStorageContract(): void
    {
        $storageEnv = AdminStorageEnvAdapter::adapt([
            'STORAGE_DRIVER' => 'do_spaces',
            'ADMIN_LOCAL_BASE_PATH' => '/srv/admin/images',
            'ADMIN_LOCAL_BASE_URL' => '/images',
            'ADMIN_DO_SPACES_KEY' => 'key',
            'ADMIN_DO_SPACES_SECRET' => 'secret',
            'ADMIN_DO_SPACES_REGION' => 'fra1',
            'ADMIN_DO_SPACES_ENDPOINT' => 'https://fra1.digitaloceanspaces.com',
            'ADMIN_DO_SPACES_BUCKET' => 'admin',
            'ADMIN_DO_SPACES_CDN_URL' => 'https://cdn.example.com',
            'ADMIN_DO_SPACES_ACL' => 'private',
        ]);

        self::assertSame([
            'STORAGE_DRIVER' => 'do_spaces',
            'LOCAL_BASE_PATH' => '/srv/admin/images',
            'LOCAL_BASE_URL' => '/images',
            'DO_SPACES_KEY' => 'key',
            'DO_SPACES_SECRET' => 'secret',
            'DO_SPACES_REGION' => 'fra1',
            'DO_SPACES_ENDPOINT' => 'https://fra1.digitaloceanspaces.com',
            'DO_SPACES_BUCKET' => 'admin',
            'DO_SPACES_CDN_URL' => 'https://cdn.example.com',
            'DO_SPACES_ACL' => 'private',
        ], $storageEnv);
    }

    public function testDoesNotFallbackToLegacyStorageKeys(): void
    {
        self::assertSame([], AdminStorageEnvAdapter::adapt([
            'LOCAL_BASE_PATH' => '/legacy/images',
            'LOCAL_BASE_URL' => '/legacy-images',
            'DO_SPACES_BUCKET' => 'legacy',
        ]));
    }

    public function testRejectsNonStringAdminStorageValues(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_LOCAL_BASE_PATH');

        AdminStorageEnvAdapter::adapt([
            'ADMIN_LOCAL_BASE_PATH' => 123,
        ]);
    }
}
