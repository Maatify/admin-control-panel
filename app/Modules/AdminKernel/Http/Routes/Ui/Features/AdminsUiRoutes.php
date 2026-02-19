<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Ui\Features;

use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class AdminsUiRoutes
{
    public static function register(RouteCollectorProxyInterface $group): void
    {
        $group->get(
            '/admins',
            [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, 'index']
        )
            ->setName('admins.list.ui')
            ->add(AuthorizationGuardMiddleware::class);

        $group->get(
            '/admins/create',
            [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminCreateController::class, 'index']
        )
            ->setName('admin.create.ui')
            ->add(AuthorizationGuardMiddleware::class);

        // ===============================
        // Admin Profile (VIEW)
        // ===============================
        $group->get(
            '/admins/{id:[0-9]+}/profile',
            [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, 'profile']
        )
            ->setName('admins.profile.view')
            ->add(AuthorizationGuardMiddleware::class);

        // ===============================
        // Admin Profile (EDIT FORM)
        // ===============================
        $group->get(
            '/admins/{id:[0-9]+}/profile/edit',
            [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, 'editProfile']
        )
            ->setName('admins.profile.edit.view')
            ->add(AuthorizationGuardMiddleware::class);

        // ===============================
        // Admin by ID
        // ===============================
        $group->group('/admins', function (RouteCollectorProxyInterface $adminsGroup) {

            // ===============================
            // Admin Profile (UPDATE)
            // ===============================
            $adminsGroup->post(
                '/{id:[0-9]+}/profile/edit',
                [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, 'updateProfile']
            )
                ->setName('admins.profile.edit');

            // ─────────────────────────────
            // Admin Email Control
            // ─────────────────────────────
            $adminsGroup->get(
                '/{id:[0-9]+}/emails',
                [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, 'emails']
            )
                ->setName('admin.email.list.ui');

            // ─────────────────────────────
            // Admin Session Control
            // ─────────────────────────────
            $adminsGroup->get(
                '/{id:[0-9]+}/sessions',
                [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, 'sessions']
            )
                ->setName('admins.session.list');

            // ─────────────────────────────
            // Admin Permissions Control
            // ─────────────────────────────
            $adminsGroup->get(
                '/{id:[0-9]+}/permissions',
                [\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, 'permissions']
            )
                ->setName('admins.permissions');

        })->add(AuthorizationGuardMiddleware::class);
    }
}
