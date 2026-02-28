<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api\Features;

use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

class RolesApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        // ─────────────────────────────
        // Roles Control
        // ─────────────────────────────
        $group->group('/roles', function (RouteCollectorProxyInterface $roles) {
            $roles->post(
                '/query',
                [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RolesControllerQuery::class, '__invoke']
            )
                ->setName('roles.query.api');

            $roles->post(
                '/{id:[0-9]+}/metadata',
                [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleMetadataUpdateController::class, '__invoke']
            )
                ->setName('roles.metadata.update');

            $roles->post(
                '/{id:[0-9]+}/toggle',
                [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleToggleController::class, '__invoke']
            )
                ->setName('roles.toggle');

            $roles->post(
                '/{id:[0-9]+}/rename',
                [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleRenameController::class, '__invoke']
            )
                ->setName('roles.rename');

            $roles->post(
                '/create',
                [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleCreateController::class, '__invoke']
            )
                ->setName('roles.create');

            // ─────────────────────────────
            // Role → Permissions (QUERY)
            // ─────────────────────────────
            $roles->post(
                '/{id:[0-9]+}/permissions/query',
                [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RolePermissionsQueryController::class, '__invoke']
            )
                ->setName('roles.permissions.query');

            // ─────────────────────────────
            // Role → Permissions (ASSIGN)
            // ─────────────────────────────
            $roles->post(
                '/{id:[0-9]+}/permissions/assign',
                [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RolePermissionAssignController::class, '__invoke']
            )
                ->setName('roles.permissions.assign');

            // ─────────────────────────────
            // Role → Permissions (UNASSIGN)
            // ─────────────────────────────
            $roles->post(
                '/{id:[0-9]+}/permissions/unassign',
                [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RolePermissionUnassignController::class, '__invoke']
            )
                ->setName('roles.permissions.unassign');

            // AdminRoutes.php
            $roles->post(
                '/{id:[0-9]+}/admins/query',
                [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleAdminsQueryController::class, '__invoke']
            )->setName('roles.admins.query');

            $roles->post(
                '/{id:[0-9]+}/admins/assign',
                [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleAdminAssignController::class, '__invoke']
            )->setName('roles.admins.assign');

            $roles->post(
                '/{id}/admins/unassign',
                [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleAdminUnassignController::class, '__invoke']
            )->setName('roles.admins.unassign');
        });
    }
}
