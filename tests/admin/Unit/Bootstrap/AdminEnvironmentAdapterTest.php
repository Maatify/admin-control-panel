<?php

declare(strict_types=1);

namespace Tests\Unit\Bootstrap;

use Maatify\AdminControlPanel\Bootstrap\AdminEnvironmentAdapter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AdminEnvironmentAdapterTest extends TestCase
{
    public function testMapsNamespacedAdminKeysToTheKernelContract(): void
    {
        $environment = [
            'ADMIN_APP_ENV' => 'testing',
            'ADMIN_URL' => 'https://admin.test',
            'ADMIN_DB_NAME' => 'admin_test',
            'ADMIN_LOG_PATH' => 'storage/logs/admin',
            'ADMIN_ASSETS_CDN_URL' => 'https://cdn.test/assets',
        ];

        self::assertSame([
            'APP_ENV' => 'testing',
            'ADMIN_URL' => 'https://admin.test',
            'DB_NAME' => 'admin_test',
            'LOG_PATH' => 'storage/logs/admin',
            'ASSETS_CDN_URL' => 'https://cdn.test/assets',
        ], AdminEnvironmentAdapter::forAdminKernel($environment));
        self::assertArrayNotHasKey('APP_ENV', $environment);
    }

    public function testDoesNotAcceptGenericKeysAsAHostFallback(): void
    {
        self::assertSame([], AdminEnvironmentAdapter::forAdminKernel([
            'APP_ENV' => 'testing',
            'DB_NAME' => 'admin_test',
        ]));
    }

    public function testRejectsNonStringAdminValues(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_DB_NAME');

        AdminEnvironmentAdapter::forAdminKernel([
            'ADMIN_DB_NAME' => 123,
        ]);
    }
}
