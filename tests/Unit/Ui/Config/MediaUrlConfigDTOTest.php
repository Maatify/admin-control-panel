<?php

declare(strict_types=1);

namespace Tests\Unit\Ui\Config;

use Dotenv\Dotenv;
use Maatify\AdminControlPanel\Bootstrap\AdminEnvironmentAdapter;
use Maatify\AdminKernel\Ui\Config\MediaUrlConfigDTO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MediaUrlConfigDTOTest extends TestCase
{
    public function testReadsTheAdminNamespacedMediaEnvironmentContract(): void
    {
        $config = MediaUrlConfigDTO::fromArray(AdminEnvironmentAdapter::forAdminKernel([
            'ADMIN_ASSETS_CDN_URL' => 'https://cdn.example.com/assets/',
            'ADMIN_CDN_IMAGE_URL' => 'https://cdn.example.com/images/',
            'ADMIN_ASSET_VERSION' => '2026.09.01',
        ]));

        self::assertSame('https://cdn.example.com/assets', $config->assetsCdnUrl);
        self::assertSame('https://cdn.example.com/images', $config->cdnImageUrl);
        self::assertSame('2026.09.01', $config->assetVersion);
        self::assertSame(
            'https://cdn.example.com/assets/app.css?v=2026.09.01',
            $config->buildAssetUrl('/app.css')
        );
        self::assertSame(
            'https://cdn.example.com/images/avatar.png',
            $config->buildImageUrl('/avatar.png')
        );
    }

    public function testDoesNotFallbackToLegacyMediaEnvironmentKeys(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ASSETS_CDN_URL');

        MediaUrlConfigDTO::fromArray(AdminEnvironmentAdapter::forAdminKernel([
            'ASSETS_CDN_URL' => 'https://cdn.example.com/assets',
            'CDN_IMAGE_URL' => 'https://cdn.example.com/images',
            'ASSET_VERSION' => '2026.09.01',
        ]));
    }

    public function testCanonicalEnvironmentExampleProducesSafeDefaultImageUrl(): void
    {
        $environment = Dotenv::createArrayBacked(dirname(__DIR__, 4), '.env.example')->load();
        $config = MediaUrlConfigDTO::fromArray(
            AdminEnvironmentAdapter::forAdminKernel($environment)
        );

        self::assertSame('http://localhost', $config->assetsCdnUrl);
        self::assertSame('http://localhost', $config->cdnImageUrl);
        self::assertSame(
            'http://localhost/images/no-image-available.svg',
            $config->buildImageUrl(null)
        );
        self::assertStringNotContainsString(
            '/images/images/',
            $config->buildImageUrl(null)
        );
    }
}
