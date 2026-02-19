<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api\Features;

use Maatify\AdminKernel\Http\Controllers\AdminNotificationPreferenceController;
use Maatify\AdminKernel\Http\Controllers\Api\Admin\AdminController;
use Maatify\AdminKernel\Http\Controllers\Api\Admin\AdminQueryController;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;

class AdminsApiRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $group
     */
    public static function register(RouteCollectorProxyInterface $group): void
    {
        // ─────────────────────────────
        // Admins Control
        // ─────────────────────────────
        $group->group('/admins', function (RouteCollectorProxyInterface $admins) {
            $admins->post('/query', [AdminQueryController::class, '__invoke'])
                ->setName('admins.list.api');

            $admins->post('/create', [AdminController::class, 'create'])
                ->setName('admin.create.api');

            $admins->get('/{admin_id:[0-9]+}/preferences', [AdminNotificationPreferenceController::class, 'getPreferences'])
                ->setName('admin.preferences.read');

            $admins->put('/{admin_id:[0-9]+}/preferences', [AdminNotificationPreferenceController::class, 'upsertPreference'])
                ->setName('admin.preferences.write');

            $admins->get('/{admin_id:[0-9]+}/notifications', [\Maatify\AdminKernel\Http\Controllers\AdminNotificationHistoryController::class, 'index'])
                ->setName('admin.notifications.history');

            // ─────────────────────────────
            // Admin Roles Query
            // ─────────────────────────────
            $admins->post('/{admin_id:[0-9]+}/roles/query',
                [\Maatify\AdminKernel\Http\Controllers\Api\Admin\AdminRolesQueryController::class, '__invoke']
            )
                ->setName('admin.roles.query');

            $admins->post('/{admin_id:[0-9]+}/permissions/effective',
                [\Maatify\AdminKernel\Http\Controllers\Api\Admin\EffectivePermissionsQueryController::class, '__invoke']
            )
                ->setName('admin.permissions.effective');

            $admins->post(
                '/{admin_id:[0-9]+}/permissions/direct/query',
                [\Maatify\AdminKernel\Http\Controllers\Api\Admin\DirectPermissionsQueryController::class, '__invoke']
            )
                ->setName('admin.permissions.direct.query');

            $admins->post(
                '/{admin_id:[0-9]+}/permissions/direct/assign',
                [\Maatify\AdminKernel\Http\Controllers\Api\Admin\AssignDirectPermissionController::class, '__invoke']
            )->setName('admin.permissions.direct.assign');

            $admins->post(
                '/{admin_id:[0-9]+}/permissions/direct/revoke',
                [\Maatify\AdminKernel\Http\Controllers\Api\Admin\RevokeDirectPermissionController::class, '__invoke']
            )->setName('admin.permissions.direct.revoke');

            // ─────────────────────────────
            // Direct Permissions (Assignable) — QUERY
            // ─────────────────────────────
            $admins->post(
                '/{admin_id:[0-9]+}/permissions/direct/assignable/query',
                [\Maatify\AdminKernel\Http\Controllers\Api\Admin\DirectPermissionsAssignableQueryController::class, '__invoke']
            )
                ->setName('admin.permissions.direct.assignable.query');

            // ─────────────────────────────
            // Admin Email Control
            // ─────────────────────────────
            $admins->get('/{id:[0-9]+}/emails', [AdminController::class, 'getEmails'])
                ->setName('admin.email.list.api');
            $admins->post('/{id:[0-9]+}/emails', [AdminController::class, 'addEmail'])
                ->setName('admin.email.add');
        });
    }
}
