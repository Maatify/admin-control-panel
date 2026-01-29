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
        return self::bootWithConfig(
            __DIR__ . '/../../',
            true,
            $builderHook
        );
    }

    /**
     * @param ?string $rootPath
     * @param bool $loadEnv
     * @param callable(mixed): void|null $builderHook
     * @return App<\Psr\Container\ContainerInterface>
     */
    public static function bootWithConfig(?string $rootPath = null, bool $loadEnv = true, ?callable $builderHook = null): App
    {
        $options = new KernelOptions();
        $options->rootPath = $rootPath;
        $options->loadEnv = $loadEnv;
        $options->builderHook = $builderHook;

        return self::bootWithOptions($options);
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
        $bootstrap = $options->bootstrap ?? require __DIR__ . '/../Bootstrap/http.php';
        $bootstrap($app);

        // Register Routes
        $routes = $options->routes ?? require __DIR__ . '/../../routes/web.php';
        $routes($app);

        return $app;
    }
}
