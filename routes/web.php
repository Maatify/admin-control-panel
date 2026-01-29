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
    // Slim executes middleware in LIFO order (Last Added = First Executed).
    //
    // Desired Stack (Outer -> Inner):
    // 1. RequestId (Infrastructure, generates ID)
    // 2. RequestContext (Infrastructure, needs ID)
    // 3. Telemetry (Infrastructure, needs Context)
    // ... Handler ...
    //
    // Therefore, we add them in REVERSE order:

    $app->add(\App\Http\Middleware\HttpRequestTelemetryMiddleware::class);
    $app->add(\App\Http\Middleware\RequestContextMiddleware::class);
    $app->add(\App\Http\Middleware\RequestIdMiddleware::class);
};
