<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Slim\Interfaces\RouteCollectorProxyInterface;

final class LogoutUiRoutes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->post(
            '/logout',
            [\Maatify\AdminKernel\Http\Controllers\Web\LogoutController::class, 'logout']
        )
            ->setName('auth.logout');

        $group->get(
            '/logout',
            [\Maatify\AdminKernel\Http\Controllers\Web\LogoutController::class, 'logout']
        )
            ->setName('auth.logout.web');
    }
}
