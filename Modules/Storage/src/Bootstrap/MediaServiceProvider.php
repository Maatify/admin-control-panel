<?php

declare(strict_types=1);

namespace Maatify\Storage\Bootstrap;

use DI\ContainerBuilder;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Maatify\SharedCommon\Path\AppPaths;
use Maatify\Storage\Config\StorageConfigs;
use Maatify\Storage\Services\AudioUploadPrivateService;
use Maatify\Storage\Services\AudioUploadPublicService;
use Maatify\Storage\Services\AudioUploadService;
use Maatify\Storage\Services\AudioReaderPrivateService;
use Maatify\Storage\Services\AudioReaderPublicService;
use Maatify\Storage\Services\AudioReaderService;
use Maatify\Storage\Http\FileServeResponder;
use Maatify\Storage\Services\FileServeService;
use Maatify\Storage\Services\FileUploadService;
use Psr\Http\Message\ResponseFactoryInterface;
use Maatify\Storage\Services\FileReaderService;
use Maatify\Storage\Services\ImageUploadPrivateService;
use Maatify\Storage\Services\ImageUploadPublicService;
use Maatify\Storage\Services\ImageUploadService;
use Maatify\Storage\Services\ImageReaderPrivateService;
use Maatify\Storage\Services\ImageReaderPublicService;
use Maatify\Storage\Services\ImageReaderService;
use Maatify\Storage\Services\VideoUploadPrivateService;
use Maatify\Storage\Services\VideoUploadPublicService;
use Maatify\Storage\Services\VideoUploadService;
use Maatify\Storage\Services\VideoReaderPrivateService;
use Maatify\Storage\Services\VideoReaderPublicService;
use Maatify\Storage\Services\VideoReaderService;
use Maatify\Storage\Factory\StorageAdapterFactory;
use Maatify\Storage\Factory\ReaderAdapterFactory;
use Psr\Container\ContainerInterface;

/**
 * Service provider for registering all media upload, reader, and serve services.
 *
 * Registers the following services in the DI container:
 * - ImageUploadPublicService  / ImageUploadPrivateService
 * - VideoUploadPublicService  / VideoUploadPrivateService
 * - AudioUploadPublicService  / AudioUploadPrivateService
 * - ImageReaderPublicService  / ImageReaderPrivateService
 * - VideoReaderPublicService  / VideoReaderPrivateService
 * - AudioReaderPublicService  / AudioReaderPrivateService
 * - FileServeService (local-only — call registerServe() with explicit basePath)
 *
 * Upload/Reader services are configured with independent DO Spaces buckets.
 * FileServeService is local-only: call registerServe() separately per disk that
 * uses local storage, passing the absolute path where files are stored.
 */
final class MediaServiceProvider
{
    /**
     * Register all media upload services in the DI container.
     *
     * Each of the 6 services is independently configured with its own bucket,
     * credentials, region, and ACL settings read from environment variables.
     *
     * @param ContainerBuilder<\DI\Container> $builder PHP-DI container builder
     * @param string $projectRoot Absolute path to project root
     *
     * @return void
     *
     * @throws \Exception If environment variables are missing
     */
    public static function register(ContainerBuilder $builder, string $projectRoot): void
    {
        // Register AppPaths first for all services to use
        $builder->addDefinitions([
            AppPaths::class => static fn(): AppPaths => new AppPaths($projectRoot),
        ]);

        $builder->addDefinitions([

            // ========== PUBLIC IMAGES ==========
            ImageUploadPublicService::class => static function (ContainerInterface $c) {
                /** @var AppPaths $paths */
                $paths = $c->get(AppPaths::class);
                $config = StorageConfigs::publicImagesConfig();
                $adapter = StorageAdapterFactory::create($paths, $config);
                $fileService = new FileUploadService($adapter);
                $imageService = new ImageUploadService($fileService);
                return new ImageUploadPublicService($imageService);
            },

            // ========== PRIVATE IMAGES ==========
            ImageUploadPrivateService::class => static function (ContainerInterface $c) {
                /** @var AppPaths $paths */
                $paths = $c->get(AppPaths::class);
                $config = StorageConfigs::privateImagesConfig();
                $adapter = StorageAdapterFactory::create($paths, $config);
                $fileService = new FileUploadService($adapter);
                $imageService = new ImageUploadService($fileService);
                return new ImageUploadPrivateService($imageService);
            },

            // ========== PUBLIC VIDEOS ==========
            VideoUploadPublicService::class => static function (ContainerInterface $c) {
                /** @var AppPaths $paths */
                $paths = $c->get(AppPaths::class);
                $config = StorageConfigs::publicVideosConfig();
                $adapter = StorageAdapterFactory::create($paths, $config);
                $fileService = new FileUploadService($adapter);
                $videoService = new VideoUploadService($fileService);
                return new VideoUploadPublicService($videoService);
            },

            // ========== PRIVATE VIDEOS ==========
            VideoUploadPrivateService::class => static function (ContainerInterface $c) {
                /** @var AppPaths $paths */
                $paths = $c->get(AppPaths::class);
                $config = StorageConfigs::privateVideosConfig();
                $adapter = StorageAdapterFactory::create($paths, $config);
                $fileService = new FileUploadService($adapter);
                $videoService = new VideoUploadService($fileService);
                return new VideoUploadPrivateService($videoService);
            },

            // ========== PUBLIC AUDIO ==========
            AudioUploadPublicService::class => static function (ContainerInterface $c) {
                /** @var AppPaths $paths */
                $paths = $c->get(AppPaths::class);
                $config = StorageConfigs::publicAudioConfig();
                $adapter = StorageAdapterFactory::create($paths, $config);
                $fileService = new FileUploadService($adapter);
                $audioService = new AudioUploadService($fileService);
                return new AudioUploadPublicService($audioService);
            },

            // ========== PRIVATE AUDIO ==========
            AudioUploadPrivateService::class => static function (ContainerInterface $c) {
                /** @var AppPaths $paths */
                $paths = $c->get(AppPaths::class);
                $config = StorageConfigs::privateAudioConfig();
                $adapter = StorageAdapterFactory::create($paths, $config);
                $fileService = new FileUploadService($adapter);
                $audioService = new AudioUploadService($fileService);
                return new AudioUploadPrivateService($audioService);
            },

            // ========== READER SERVICES ==========

            // ========== PUBLIC IMAGES READER ==========
            ImageReaderPublicService::class => static function (ContainerInterface $c) {
                /** @var AppPaths $paths */
                $paths = $c->get(AppPaths::class);
                $config = StorageConfigs::publicImagesConfig();
                $clock = $c->get(ClockInterface::class);
                assert($clock instanceof ClockInterface);
                $adapter = ReaderAdapterFactory::create($paths, $config, $clock);
                $fileService = new FileReaderService($adapter);
                $imageService = new ImageReaderService($fileService);
                return new ImageReaderPublicService($imageService);
            },

            // ========== PRIVATE IMAGES READER ==========
            ImageReaderPrivateService::class => static function (ContainerInterface $c) {
                /** @var AppPaths $paths */
                $paths = $c->get(AppPaths::class);
                $config = StorageConfigs::privateImagesConfig();
                $clock = $c->get(ClockInterface::class);
                assert($clock instanceof ClockInterface);
                $adapter = ReaderAdapterFactory::create($paths, $config, $clock);
                $fileService = new FileReaderService($adapter);
                $imageService = new ImageReaderService($fileService);
                return new ImageReaderPrivateService($imageService);
            },

            // ========== PUBLIC VIDEOS READER ==========
            VideoReaderPublicService::class => static function (ContainerInterface $c) {
                /** @var AppPaths $paths */
                $paths = $c->get(AppPaths::class);
                $config = StorageConfigs::publicVideosConfig();
                $clock = $c->get(ClockInterface::class);
                assert($clock instanceof ClockInterface);
                $adapter = ReaderAdapterFactory::create($paths, $config, $clock);
                $fileService = new FileReaderService($adapter);
                $videoService = new VideoReaderService($fileService);
                return new VideoReaderPublicService($videoService);
            },

            // ========== PRIVATE VIDEOS READER ==========
            VideoReaderPrivateService::class => static function (ContainerInterface $c) {
                /** @var AppPaths $paths */
                $paths = $c->get(AppPaths::class);
                $config = StorageConfigs::privateVideosConfig();
                $clock = $c->get(ClockInterface::class);
                assert($clock instanceof ClockInterface);
                $adapter = ReaderAdapterFactory::create($paths, $config, $clock);
                $fileService = new FileReaderService($adapter);
                $videoService = new VideoReaderService($fileService);
                return new VideoReaderPrivateService($videoService);
            },

            // ========== PUBLIC AUDIO READER ==========
            AudioReaderPublicService::class => static function (ContainerInterface $c) {
                /** @var AppPaths $paths */
                $paths = $c->get(AppPaths::class);
                $config = StorageConfigs::publicAudioConfig();
                $clock = $c->get(ClockInterface::class);
                assert($clock instanceof ClockInterface);
                $adapter = ReaderAdapterFactory::create($paths, $config, $clock);
                $fileService = new FileReaderService($adapter);
                $audioService = new AudioReaderService($fileService);
                return new AudioReaderPublicService($audioService);
            },

            // ========== PRIVATE AUDIO READER ==========
            AudioReaderPrivateService::class => static function (ContainerInterface $c) {
                /** @var AppPaths $paths */
                $paths = $c->get(AppPaths::class);
                $config = StorageConfigs::privateAudioConfig();
                $clock = $c->get(ClockInterface::class);
                assert($clock instanceof ClockInterface);
                $adapter = ReaderAdapterFactory::create($paths, $config, $clock);
                $fileService = new FileReaderService($adapter);
                $audioService = new AudioReaderService($fileService);
                return new AudioReaderPrivateService($audioService);
            },

        ]);
    }

    /**
     * Register a FileServeService for local private storage in multi-disk mode.
     *
     * Call this once per local disk that needs PHP file streaming.
     * DO Spaces disks do NOT need this — files are served via CDN / presigned URLs.
     *
     * Because each disk can have a different local basePath, registration is
     * explicit: the caller provides the absolute path to the storage root.
     *
     * **Typical usage (single private local disk):**
     * ```php
     * MediaServiceProvider::register($builder, $projectRoot);
     * MediaServiceProvider::registerServe($builder, '/var/www/storage/private');
     * ```
     *
     * **Multiple local disks (e.g. images + videos stored locally):**
     * ```php
     * // Override the binding per disk by registering your own named factories,
     * // or instantiate FileServeService directly in the controller with the right basePath.
     * $imageServe = new FileServeService('/var/www/storage/private/images');
     * $videoServe = new FileServeService('/var/www/storage/private/videos');
     * ```
     *
     * @param ContainerBuilder<\DI\Container> $builder
     * @param string $basePath Absolute path to the local private storage root.
     *
     * @return void
     */
    public static function registerServe(ContainerBuilder $builder, string $basePath): void
    {
        $builder->addDefinitions([
            FileServeService::class => static function (ContainerInterface $c) use ($basePath): FileServeService {
                $clock = $c->get(ClockInterface::class);
                assert($clock instanceof ClockInterface);

                return new FileServeService($basePath, $clock);
            },

            // PSR-7 responder — autowired from whatever ResponseFactoryInterface
            // the framework registers (e.g. Slim\Psr7\Factory\ResponseFactory).
            // StreamFactoryInterface is no longer a dependency: body streaming
            // is handled internally by LimitedResourceStream.
            FileServeResponder::class => static function (ContainerInterface $c): FileServeResponder {
                /** @var ResponseFactoryInterface $responseFactory */
                $responseFactory = $c->get(ResponseFactoryInterface::class);

                return new FileServeResponder($responseFactory);
            },
        ]);
    }
}
