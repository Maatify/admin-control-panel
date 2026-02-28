<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api;

use Maatify\AdminKernel\Http\Controllers\AuthController;
use Maatify\AdminKernel\Http\Middleware\ApiGuestGuardMiddleware;
use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Maatify\AdminKernel\Http\Middleware\SessionGuardMiddleware;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class ApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $app
     */
    public static function register(RouteCollectorProxyInterface $app): void
    {
        // ========================================
        // ================== API =================
        // ========================================
        // API Routes (JSON only)
        $app->group('/api', function (RouteCollectorProxyInterface $api) {

            // Public API
            $api->post('/auth/login', [AuthController::class, 'login'])
                ->add(ApiGuestGuardMiddleware::class)
                ->add(\Maatify\AbuseProtection\Middleware\AbuseProtectionMiddleware::class);

            // Step-Up API
            $api->post('/auth/step-up', [\Maatify\AdminKernel\Http\Controllers\StepUpController::class, 'verify'])
                ->add(\Maatify\AdminKernel\Http\Middleware\AdminContextMiddleware::class)
                ->add(SessionGuardMiddleware::class)
                ->setName('auth.stepup.verify');

            // Protected API
            ApiProtectedRoutes::register($api);

        });
    }
}
