<?php

declare(strict_types=1);

namespace App\Kernel;

use App\Bootstrap\Container;
use Slim\App;
use Slim\Factory\AppFactory;

class AdminKernel
{
    /**
     * @param callable(mixed): void|null $builderHook
     * @return App<\Psr\Container\ContainerInterface>
     */
    public static function boot(?callable $builderHook = null): App
    {
        return self::bootWithOptions(new KernelOptions(
            rootPath: __DIR__ . '/../../',
            loadEnv: true,
            builderHook: $builderHook
        ));
    }

    /**
     * @param ?string $rootPath
     * @param bool $loadEnv
     * @param callable(mixed): void|null $builderHook
     * @return App<\Psr\Container\ContainerInterface>
     */
    public static function bootWithConfig(?string $rootPath = null, bool $loadEnv = true, ?callable $builderHook = null): App
    {
        return self::bootWithOptions(new KernelOptions(
            rootPath: $rootPath,
            loadEnv: $loadEnv,
            builderHook: $builderHook
        ));
    }

    /**
     * @param KernelOptions $options
     * @return App<\Psr\Container\ContainerInterface>
     */
    public static function bootWithOptions(KernelOptions $options): App
    {
        // Create Container (This handles ENV loading and AdminConfigDTO)
        $container = Container::create($options->builderHook, $options->rootPath, $options->loadEnv);

        // Create App
        AppFactory::setContainer($container);
        /** @var App<\Psr\Container\ContainerInterface> $app */
        $app = AppFactory::create();

        // Delegate HTTP bootstrap logic
        if ($options->bootstrap !== null) {
            ($options->bootstrap)($app);
        } else {
            (require __DIR__ . '/../Bootstrap/http.php')($app);
        }

        return $app;
    }
}
