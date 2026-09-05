<?php

declare(strict_types=1);

namespace Tests\Unit\Bootstrap;

use DI\ContainerBuilder;
use Maatify\AdminControlPanel\Bootstrap\AdminEnvironmentAdapter;
use Maatify\AdminKernel\Kernel\AdminKernel;
use Maatify\AdminKernel\Kernel\DTO\AdminRuntimeConfigDTO;
use Maatify\AdminKernel\Kernel\KernelOptions;
use Maatify\AdminKernel\Ui\Config\MediaUrlConfigDTO;
use PHPUnit\Framework\TestCase;
use Slim\Views\Twig;

final class ContainerAssetUrlTest extends TestCase
{
    public function testKernelOptionsAssetBaseOverrideHasPriority(): void
    {
        self::assertSame(
            'https://override.example.test/admin-assets/assets/app.js?v=2026.09.05',
            $this->renderAsset(
                assetsBaseUrl: 'https://override.example.test/admin-assets/',
                adminAssetBaseUrl: '/admin-assets',
                assetVersion: '2026.09.05'
            )
        );
    }

    public function testNullKernelOptionsOverrideUsesAdminAssetBaseUrl(): void
    {
        self::assertSame(
            '/admin-assets/assets/app.js?v=2026.09.05',
            $this->renderAsset(
                assetsBaseUrl: null,
                adminAssetBaseUrl: '/admin-assets/',
                assetVersion: '2026.09.05'
            )
        );
    }

    public function testRootAdminAssetBaseUrlProducesAssetsPath(): void
    {
        self::assertSame(
            '/assets/app.js',
            $this->renderAsset(
                assetsBaseUrl: null,
                adminAssetBaseUrl: '/',
                assetVersion: null
            )
        );
    }

    public function testAssetVersionIsAppliedWhenConfigured(): void
    {
        self::assertSame(
            '/admin-assets/assets/app.js?v=2026.09.05',
            $this->renderAsset(
                assetsBaseUrl: null,
                adminAssetBaseUrl: '/admin-assets',
                assetVersion: '2026.09.05'
            )
        );
    }

    public function testAssetsCdnUrlDoesNotReplaceAdminAssetBaseUrlForTwigAsset(): void
    {
        $assetUrl = $this->renderAsset(
            assetsBaseUrl: null,
            adminAssetBaseUrl: '/',
            assetVersion: '2026.09.05'
        );

        self::assertSame('/assets/app.js?v=2026.09.05', $assetUrl);
        self::assertStringNotContainsString('cdn.example.test', $assetUrl);
    }

    private function renderAsset(
        ?string $assetsBaseUrl,
        string $adminAssetBaseUrl,
        ?string $assetVersion
    ): string {
        $environment = $_ENV;
        $environment['ADMIN_ASSET_BASE_URL'] = $adminAssetBaseUrl;

        $runtime = AdminRuntimeConfigDTO::fromArray(
            AdminEnvironmentAdapter::forAdminKernel($environment)
        );
        $mediaUrlConfig = new MediaUrlConfigDTO(
            assetsCdnUrl: 'https://cdn.example.test/assets',
            cdnImageUrl: 'https://cdn.example.test/images',
            assetVersion: $assetVersion
        );

        $options = new KernelOptions();
        $options->runtimeConfig = $runtime;
        $options->assetsBaseUrl = $assetsBaseUrl;
        $options->routes = static function (): void {};
        $options->builderHook = static function (ContainerBuilder $builder) use ($mediaUrlConfig): void {
            $builder->addDefinitions([
                MediaUrlConfigDTO::class => static fn (): MediaUrlConfigDTO => $mediaUrlConfig,
            ]);
        };

        $app = AdminKernel::bootWithOptions($options);
        $twig = $app->getContainer()->get(Twig::class);

        return trim(
            $twig->getEnvironment()
                ->createTemplate('{{ asset("assets/app.js") }}')
                ->render([])
        );
    }
}
