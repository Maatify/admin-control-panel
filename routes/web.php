<?php

declare(strict_types=1);

use App\Http\Routes\AdminRoutes;
use Slim\App;

return function (App $app) {
    // Mount the admin routes at the root level (maintaining backward compatibility)
    // This allows the admin panel to work as a standalone application.
    // In a host application, they can call AdminRoutes::register($app) under a prefix.

    AdminRoutes::register($app);

    // Note: Middleware registration is handled in app/Bootstrap/http.php for the standalone app.
    // This file is strictly for Route Registration.
};
