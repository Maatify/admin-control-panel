<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Slim\Interfaces\RouteCollectorProxyInterface;

final class TelemetryUiRoutes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->get(
            '/telemetry',
            [\Maatify\AdminKernel\Http\Controllers\Ui\TelemetryListController::class, 'index']
        )
            ->setName('telemetry.list');
    }
}
