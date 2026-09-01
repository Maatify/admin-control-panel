<?php

declare(strict_types=1);

namespace Maatify\Storage\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Maatify\SharedCommon\Path\AppPaths;
use Maatify\Storage\Config\StorageConfig;
use Maatify\Storage\Contracts\StorageAdapterInterface;
use Maatify\Storage\Factory\StorageAdapterFactory;
use Maatify\Storage\Services\AudioUploadService;
use Maatify\Storage\Http\FileServeResponder;
use Maatify\Storage\Services\FileServeService;
use Maatify\Storage\Services\FileUploadService;
use Psr\Http\Message\ResponseFactoryInterface;
use Maatify\Storage\Services\ImageUploadService;
use Maatify\Storage\Services\VideoUploadService;
use Psr\Container\ContainerInterface;

/**
 * Storage module dependency injection bindings.
 *
 * Per Maatify Module Building Standard section 16.
 *
 * This class supports two modes:
 *
 * 1. Single-Storage Mode (legacy):
 *    Use register() for simple projects with a single storage configuration
 *
 * 2. Multi-Disk Mode (recommended):
 *    Use registerMultiDisk() for projects requiring separate public/private uploads
 *    with independent buckets, credentials, and CDN settings
 */
final class StorageBindings
{
    /**
     * Register single-storage bindings (legacy mode).
     *
     * @param ContainerBuilder<Container> $builder
     * @param string $rootPath
     * @param StorageConfig $config
     * @return void
     */
    public static function register(
        ContainerBuilder $builder,
        string $rootPath,
        StorageConfig $config,
    ): void {
        $builder->addDefinitions([

            // ── Path ────────────────────────────────────────────────────

            AppPaths::class => static fn(): AppPaths
            => new AppPaths($rootPath),

            // ── Config ───────────────────────────────────────────────────

            StorageConfig::class => static fn(): StorageConfig
            => $config,

            // ── Adapter ──────────────────────────────────────────────────

            StorageAdapterInterface::class => static function (ContainerInterface $c): StorageAdapterInterface {
                /** @var AppPaths $paths */
                $paths = $c->get(AppPaths::class);

                /** @var StorageConfig $storageConfig */
                $storageConfig = $c->get(StorageConfig::class);

                return StorageAdapterFactory::create(
                    paths:  $paths,
                    config: $storageConfig,
                );
            },

            // ── Services ─────────────────────────────────────────────────

            FileUploadService::class => static function (ContainerInterface $c): FileUploadService {
                /** @var StorageAdapterInterface $adapter */
                $adapter = $c->get(StorageAdapterInterface::class);

                return new FileUploadService(storage: $adapter);
            },

            ImageUploadService::class => static function (ContainerInterface $c): ImageUploadService {
                /** @var FileUploadService $uploadService */
                $uploadService = $c->get(FileUploadService::class);

                return new ImageUploadService($uploadService);
            },

            VideoUploadService::class => static function (ContainerInterface $c): VideoUploadService {
                /** @var FileUploadService $uploadService */
                $uploadService = $c->get(FileUploadService::class);

                return new VideoUploadService($uploadService);
            },

            AudioUploadService::class => static function (ContainerInterface $c): AudioUploadService {
                /** @var FileUploadService $uploadService */
                $uploadService = $c->get(FileUploadService::class);

                return new AudioUploadService($uploadService);
            },

        ]);

        // ── Serve (local driver only) ────────────────────────────────────────
        // Only register FileServeService when driver = 'local'.
        // DO Spaces files are served via CDN / presigned URLs — no PHP streaming needed.
        // For multi-disk mode use MediaServiceProvider::registerServe() explicitly.

        if ($config->getDriver() === 'local') {
            $local = $config->getLocalConfig();

            if ($local !== null && $local->basePath !== '') {
                $builder->addDefinitions([
                    FileServeService::class => static function (ContainerInterface $c) use ($local): FileServeService {
                        $clock = $c->get(ClockInterface::class);
                        assert($clock instanceof ClockInterface);

                        return new FileServeService($local->basePath, $clock);
                    },

                    // PSR-7 responder — autowired by PHP-DI from whatever
                    // ResponseFactoryInterface the framework provides.
                    // StreamFactoryInterface is no longer needed: body streaming
                    // is handled by LimitedResourceStream internally.
                    FileServeResponder::class => static function (ContainerInterface $c): FileServeResponder {
                        /** @var ResponseFactoryInterface $responseFactory */
                        $responseFactory = $c->get(ResponseFactoryInterface::class);

                        return new FileServeResponder($responseFactory);
                    },
                ]);
            }
        }
    }

    /**
     * Register multi-disk bindings (recommended for production).
     *
     * Registers 6 separate upload services with independent buckets, credentials, and ACLs:
     * - ImageUploadPublicService (CDN enabled)
     * - ImageUploadPrivateService (authenticated/signed URLs)
     * - VideoUploadPublicService (CDN enabled)
     * - VideoUploadPrivateService (authenticated/signed URLs)
     * - AudioUploadPublicService (CDN enabled)
     * - AudioUploadPrivateService (authenticated/signed URLs)
     *
     * @param ContainerBuilder<Container> $builder
     * @param string $rootPath
     * @return void
     *
     * @throws \Exception If required environment variables are missing
     */
    public static function registerMultiDisk(
        ContainerBuilder $builder,
        string $rootPath,
    ): void {
        // Delegate to MediaServiceProvider for 6-service registration
        // (AppPaths is registered within MediaServiceProvider)
        MediaServiceProvider::register($builder, $rootPath);
    }
}
