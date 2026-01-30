<?php

declare(strict_types=1);

use App\Http\Routes\AdminRoutes;
use Slim\App;

return function (App $app) {
    // Mount the admin routes at the root level (maintaining backward compatibility)
    // This allows the admin panel to work as a standalone application.
    // In a host application, they can call AdminRoutes::register($app) under a prefix.

    // Note: AdminRoutes::register() now applies the canonical infrastructure middleware
    // (RequestId, Context, Telemetry) by default.
    AdminRoutes::register($app);
};
