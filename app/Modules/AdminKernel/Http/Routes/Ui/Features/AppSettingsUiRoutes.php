<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class AppSettingsUiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
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
