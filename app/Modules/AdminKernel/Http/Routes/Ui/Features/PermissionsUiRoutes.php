<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class PermissionsUiRoutes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->get(
            '/permissions/{permission_id:[0-9]+}',
            [\Maatify\AdminKernel\Http\Controllers\Ui\Permissions\UiAPermissionDetailsController::class, 'index']
        )
            ->setName('permission.details.ui')
            ->add(AuthorizationGuardMiddleware::class);

        $group->get(
            '/permissions',
            [\Maatify\AdminKernel\Http\Controllers\Ui\Permissions\UiPermissionsController::class, 'index']
        )
            ->setName('permissions.query.ui')
            ->add(AuthorizationGuardMiddleware::class);
    }
}
