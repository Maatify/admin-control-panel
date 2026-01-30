<?php

declare(strict_types=1);

namespace App\Kernel;

use App\Bootstrap\Container;
use App\Context\RequestContext;
use App\Http\Middleware\HttpRequestTelemetryMiddleware;
use App\Http\Middleware\RequestContextMiddleware;
use App\Http\Middleware\RequestIdMiddleware;
use App\Kernel\DTO\AdminRuntimeConfigDTO;
use RuntimeException;
use Slim\App;
use Slim\Factory\AppFactory;

final class AdminKernel
{
    /**
     * Infrastructure middleware required for Admin runtime.
     *
     * NOTE:
     * Slim executes middleware in LIFO order (last added = first executed),
     * so we add them in REVERSE of execution order.
     */
    private const INFRASTRUCTURE_MIDDLEWARE = [
        HttpRequestTelemetryMiddleware::class,
        RequestContextMiddleware::class,
        RequestIdMiddleware::class,
    ];

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
        if ($options->registerInfrastructureMiddleware === true) {
            self::registerInfrastructureMiddlewareOnce($app);

            if ($options->strictInfrastructure === true) {
                // Fail-fast guard
                $app->add(function ($request, $handler) {
                    if (!$request->getAttribute(RequestContext::class)) {
                        throw new RuntimeException(
                            'Infrastructure middleware missing. ' .
                            'Ensure RequestIdMiddleware and RequestContextMiddleware are registered.'
                        );
                    }

                    return $handler->handle($request);
                });
            }
        }

        // Register routes
        $routes = $options->routes ?? require __DIR__ . '/../../routes/web.php';
        $routes($app);

        return $app;
    }

    /**
     * Register infrastructure middleware only once.
     *
     * @param App<\Psr\Container\ContainerInterface> $app
     */
    private static function registerInfrastructureMiddlewareOnce(App $app): void
    {
        $markerKey = '__infra_registered';

        $container = $app->getContainer();

        // PSR-11 safe check
        if ($container->has($markerKey)) {
            return;
        }

        foreach (self::INFRASTRUCTURE_MIDDLEWARE as $middleware) {
            $app->add($middleware);
        }

        // Mark infra as registered (only if container supports mutation)
        if (method_exists($container, 'set')) {
            $container->set($markerKey, true);
        }
    }
}
