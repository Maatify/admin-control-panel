<?php

declare(strict_types=1);

use Maatify\AdminKernel\Http\Routes\AdminRoutes;
use Slim\App;

return function (App $app) {
    // Mount the admin routes at the root level (maintaining backward compatibility)
    // This allows the admin panel to work as a standalone application.
    // In a host application, they can call AdminRoutes::register($app) under a prefix.

    // 🔴 FIRST: normalize URL
    $app->add(\Maatify\AdminKernel\Http\Middleware\TrailingSlashMiddleware::class);

    // Note: AdminRoutes::register() now applies the canonical infrastructure middleware
    // (RequestId, Context, Telemetry) by default.
    AdminRoutes::register($app);
};
