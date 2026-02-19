<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Slim\Interfaces\RouteCollectorProxyInterface;

final class AppSettingsUiRoutes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->get(
            '/app-settings',
            [
                \Maatify\AdminKernel\Http\Controllers\Ui\AppSettings\AppSettingsListUiController::class,
                '__invoke'
            ]
        )->setName('app_settings.list.ui');
    }
}
