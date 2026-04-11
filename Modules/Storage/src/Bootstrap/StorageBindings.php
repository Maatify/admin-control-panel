<?php

declare(strict_types=1);

namespace Maatify\Storage\Bootstrap;

use DI\Container;
use DI\ContainerBuilder;
use Maatify\SharedCommon\Path\AppPaths;
use Maatify\Storage\Config\StorageConfig;
use Maatify\Storage\Contracts\StorageAdapterInterface;
use Maatify\Storage\Factory\StorageAdapterFactory;
use Maatify\Storage\Services\FileUploadService;
use Psr\Container\ContainerInterface;

final class StorageBindings
{
    /**
     * @param ContainerBuilder<Container> $builder
     */
    public static function register(
        ContainerBuilder $builder,
        string $rootPath,
        StorageConfig $config,
    ): void {
        $builder->addDefinitions([

            // --- Path ---------------------------------------------------

            AppPaths::class => static fn(): AppPaths
            => new AppPaths($rootPath),

            // --- Config -------------------------------------------------

            StorageConfig::class => static fn(): StorageConfig
            => $config,

            // --- Adapter ------------------------------------------------

            StorageAdapterInterface::class => static function (ContainerInterface $c): StorageAdapterInterface {
                $paths = $c->get(AppPaths::class);
                assert($paths instanceof AppPaths);

                $config = $c->get(StorageConfig::class);
                assert($config instanceof StorageConfig);

                return StorageAdapterFactory::create(
                    paths:  $paths,
                    config: $config,
                );
            },

            // --- Services -----------------------------------------------

            FileUploadService::class => static function (ContainerInterface $c): FileUploadService {
                $adapter = $c->get(StorageAdapterInterface::class);
                assert($adapter instanceof StorageAdapterInterface);

                return new FileUploadService(
                    storage: $adapter,
                );
            },

        ]);
    }
}
