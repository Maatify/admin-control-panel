<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api\Features;

use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

class PermissionsApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        // ─────────────────────────────
        // Permissions Control
        // ─────────────────────────────
        $group->group('/permissions', function (RouteCollectorProxyInterface $permissions) {
            $permissions->post('/query', [\Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionsController::class, '__invoke'])
                ->setName('permissions.query.api');

            $permissions->post('/{id:[0-9]+}/metadata', [\Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionMetadataUpdateController::class, '__invoke'])
                ->setName('permissions.metadata.update');
        });

        // ─────────────────────────────
        // Permission → Roles (Query)
        // ─────────────────────────────
        $group->post(
            '/permissions/{permission_id:[0-9]+}/roles/query',
            \Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionRolesQueryController::class
        )
            ->setName('permissions.roles.query');

        // ─────────────────────────────
        // Permission → Admins (Direct Overrides Query)
        // ─────────────────────────────
        $group->post(
            '/permissions/{permission_id:[0-9]+}/admins/query',
            \Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionAdminsQueryController::class
        )
            ->setName('permissions.admins.query');
    }
}
