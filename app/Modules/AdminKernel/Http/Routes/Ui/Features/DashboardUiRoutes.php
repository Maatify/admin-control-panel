<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Slim\Interfaces\RouteCollectorProxyInterface;

final class DashboardUiRoutes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->get('/', [\Maatify\AdminKernel\Http\Controllers\Ui\UiDashboardController::class, 'index']);
        $group->get('/dashboard', [\Maatify\AdminKernel\Http\Controllers\Ui\UiDashboardController::class, 'index']);
    }
}
