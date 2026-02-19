<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes;

use Maatify\AdminKernel\Http\DTO\AdminMiddlewareOptionsDTO;
use Maatify\AdminKernel\Http\Middleware\HttpRequestTelemetryMiddleware;
use Maatify\AdminKernel\Http\Middleware\RequestContextMiddleware;
use Maatify\AdminKernel\Http\Middleware\RequestIdMiddleware;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class AdminRoutes
{
    /**
     * @phpstan-param RouteCollectorProxyInterface<\Psr\Container\ContainerInterface|null> $app
     */
    public static function register(
        RouteCollectorProxyInterface $app,
        ?AdminMiddlewareOptionsDTO $options = null
    ): void {
        $options ??= new AdminMiddlewareOptionsDTO();

        $group = $app->group('', function (RouteCollectorProxyInterface $app) {

            HealthRoutes::register($app);
            Ui\UiRoutes::register($app);
            Api\ApiRoutes::register($app);
            WebhookRoutes::register($app);
        });

        // Middleware applied to the group (LIFO execution: Input -> Recovery)
        $group
            ->add(\Maatify\AdminKernel\Http\Middleware\RecoveryStateMiddleware::class)
            ->add(\Maatify\InputNormalization\Middleware\InputNormalizationMiddleware::class);

        // Explicit Infrastructure Middleware (Outer Layer)
        // Execution Order (LIFO): RequestId -> Context -> Telemetry -> Input -> Recovery
        if ($options->withInfrastructure) {
            $group
                ->add(HttpRequestTelemetryMiddleware::class)
                ->add(RequestContextMiddleware::class)
                ->add(RequestIdMiddleware::class);
        }
    }
}
