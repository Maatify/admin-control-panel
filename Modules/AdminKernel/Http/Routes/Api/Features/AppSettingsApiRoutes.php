<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api\Features;

use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

class AppSettingsApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        // ─────────────────────────────
        // App Settings Control
        // ─────────────────────────────
        $group->group('/app-settings', function (RouteCollectorProxyInterface $appSettings) {
            $appSettings->post(
                '/query',
                [
                    \Maatify\AdminKernel\Http\Controllers\Api\AppSettings\AppSettingsQueryController::class,
                    '__invoke'
                ]
            )
                ->setName('app_settings.list.api');

            $appSettings->post(
                '/create',
                [
                    \Maatify\AdminKernel\Http\Controllers\Api\AppSettings\AppSettingsCreateController::class,
                    '__invoke'
                ]
            )->setName('app_settings.create.api');

            $appSettings->post(
                '/metadata',
                [
                    \Maatify\AdminKernel\Http\Controllers\Api\AppSettings\AppSettingsMetadataController::class,
                    '__invoke'
                ]
            )->setName('app_settings.metadata.api');

            $appSettings->post(
                '/update',
                [
                    \Maatify\AdminKernel\Http\Controllers\Api\AppSettings\AppSettingsUpdateController::class,
                    '__invoke'
                ]
            )->setName('app_settings.update.api');

            $appSettings->post(
                '/set-active',
                [
                    \Maatify\AdminKernel\Http\Controllers\Api\AppSettings\AppSettingsSetActiveController::class,
                    '__invoke'
                ]
            )->setName('app_settings.set_active.api');

        });
    }
}
