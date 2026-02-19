<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Slim\Interfaces\RouteCollectorProxyInterface;

final class ActivityLogsUiRoutes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->get(
            '/activity-logs',
            [\Maatify\AdminKernel\Http\Controllers\Ui\ActivityLogListController::class, 'index']
        )
            ->setName('activity_logs.view');
    }
}
