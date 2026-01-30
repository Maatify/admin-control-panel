<?php

declare(strict_types=1);

namespace App\Kernel;

use App\Bootstrap\Container;
use App\Kernel\DTO\AdminRuntimeConfigDTO;
use RuntimeException;
use Slim\App;
use Slim\Factory\AppFactory;

final class AdminKernel
{
    /**
     * Simplest boot entrypoint.
     *
     * @param AdminRuntimeConfigDTO $runtimeConfig
     * @param callable(mixed): void|null $builderHook
     * @return App<\Psr\Container\ContainerInterface>
     */
    public static function boot(
        AdminRuntimeConfigDTO $runtimeConfig,
        ?callable $builderHook = null
    ): App {
        $options = new KernelOptions();
        $options->runtimeConfig = $runtimeConfig;
        $options->builderHook = $builderHook;

        return self::bootWithOptions($options);
    }

    /**
     * Canonical boot method.
     *
     * @param KernelOptions $options
     * @return App<\Psr\Container\ContainerInterface>
     */
    public static function bootWithOptions(KernelOptions $options): App
    {
        if (!isset($options->runtimeConfig)) {
            throw new RuntimeException('AdminRuntimeConfigDTO is required to boot AdminKernel.');
        }

        // Create Container (NO env loading, NO filesystem assumptions)
        $container = Container::create(
            $options->runtimeConfig,
            $options->builderHook,
            $options->templatesPath,
            $options->assetsBaseUrl
        );

        // Create Slim App
        AppFactory::setContainer($container);
        /** @var App<\Psr\Container\ContainerInterface> $app */
        $app = AppFactory::create();

        // HTTP bootstrap (body parsing, error middleware, etc.)
        $bootstrap = $options->bootstrap ?? require __DIR__ . '/../Bootstrap/http.php';
        $bootstrap($app);

        // Register infrastructure middleware (Kernel-owned)
        // NOTE: Infrastructure middleware (RequestId, Context, Telemetry) are now
        // explicitly registered by AdminRoutes::register() via AdminMiddlewareOptionsDTO.
        // The properties $options->registerInfrastructureMiddleware and $options->strictInfrastructure
        // are effectively deprecated/ignored by the Kernel as middleware is now group-scoped.

        // Register routes
        $routes = $options->routes;

        if ($routes === null) {
            $routesPath = $options->routesFilePath ?? __DIR__ . '/../../routes/web.php';

            if (!file_exists($routesPath)) {
                throw new RuntimeException("Routes file not found: $routesPath");
            }

            /** @var callable(App<\Psr\Container\ContainerInterface>): void $routes */
            $routes = require $routesPath;
        }

        $routes($app);

        return $app;
    }
}
