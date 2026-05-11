<?php

declare(strict_types=1);

namespace Maatify\SettingsSlim\Admin\Http\Routes;

use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Maatify\SettingsSlim\Admin\Http\Controllers\Ui\SettingsListUiController;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class SettingsUiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/settings', function (RouteCollectorProxyInterface $settingsGroup) {

            $settingsGroup->get('', [SettingsListUiController::class, '__invoke'])
                ->setName('settings.list.ui');

        })->add(AuthorizationGuardMiddleware::class);
    }
}
