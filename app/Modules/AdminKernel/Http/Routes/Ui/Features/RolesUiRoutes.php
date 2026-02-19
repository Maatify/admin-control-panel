<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class RolesUiRoutes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->get(
            '/roles',
            [\Maatify\AdminKernel\Http\Controllers\Ui\Roles\UiRolesController::class, 'index']
        )
            ->setName('roles.query.ui')
            ->add(AuthorizationGuardMiddleware::class);

        $group->get(
            '/roles/{id:[0-9]+}',
            [\Maatify\AdminKernel\Http\Controllers\Ui\Roles\UiRoleDetailsController::class, '__invoke']
        )
            ->setName('roles.view.ui')
            ->add(AuthorizationGuardMiddleware::class);
    }
}
