<?php

declare(strict_types=1);

use App\Http\Routes\AdminRoutes;
use Slim\App;

return function (App $app) {
    // Mount the admin routes at the root level (maintaining backward compatibility)
    // This allows the admin panel to work as a standalone application.
    // In a host application, they can call AdminRoutes::register($app) under a prefix.

    AdminRoutes::register($app);

    // IMPORTANT:
    // InputNormalizationMiddleware MUST run before validation and guards.
    // It is added last to ensure it executes first in Slim's middleware stack.

    $app->add(\App\Http\Middleware\RecoveryStateMiddleware::class);
    $app->add(\App\Modules\InputNormalization\Middleware\InputNormalizationMiddleware::class);
    $app->add(\App\Http\Middleware\RequestContextMiddleware::class);
    $app->add(\App\Http\Middleware\RequestIdMiddleware::class);
    $app->add(\App\Http\Middleware\HttpRequestTelemetryMiddleware::class);
};
