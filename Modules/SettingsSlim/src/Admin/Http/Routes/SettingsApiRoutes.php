<?php

declare(strict_types=1);

namespace Maatify\SettingsSlim\Admin\Http\Routes;

use Maatify\SettingsSlim\Admin\Http\Controllers\Api\SettingsDropdownController;
use Maatify\SettingsSlim\Admin\Http\Controllers\Api\SettingsGetController;
use Maatify\SettingsSlim\Admin\Http\Controllers\Api\SettingsListController;
use Maatify\SettingsSlim\Admin\Http\Controllers\Api\SettingsUpdateController;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

class SettingsApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->group('/settings', function (RouteCollectorProxyInterface $settings) {
            $settings->post('/query', [SettingsListController::class, '__invoke'])
                ->setName('settings.list.api');

            $settings->post('/dropdown', [SettingsDropdownController::class, '__invoke'])
                ->setName('settings.dropdown.api');

            $settings->post('/get', [SettingsGetController::class, '__invoke'])
                ->setName('settings.get.api');

            $settings->post('/update', [SettingsUpdateController::class, '__invoke'])
                ->setName('settings.update.api');
        });
    }
}
