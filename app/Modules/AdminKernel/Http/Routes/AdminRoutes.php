<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes;

use Maatify\AdminKernel\Http\Controllers\AdminController;
use Maatify\AdminKernel\Http\Controllers\AdminEmailVerificationController;
use Maatify\AdminKernel\Http\Controllers\AdminNotificationPreferenceController;
use Maatify\AdminKernel\Http\Controllers\Api\AdminQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\LanguagesClearFallbackController;
use Maatify\AdminKernel\Http\Controllers\Api\LanguagesCreateController;
use Maatify\AdminKernel\Http\Controllers\Api\TranslationKeysCreateController;
use Maatify\AdminKernel\Http\Controllers\Api\LanguagesQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\LanguagesSetActiveController;
use Maatify\AdminKernel\Http\Controllers\Api\LanguagesSetFallbackController;
use Maatify\AdminKernel\Http\Controllers\Api\LanguagesUpdateCodeController;
use Maatify\AdminKernel\Http\Controllers\Api\LanguagesUpdateNameController;
use Maatify\AdminKernel\Http\Controllers\Api\LanguagesUpdateSettingsController;
use Maatify\AdminKernel\Http\Controllers\Api\LanguagesUpdateSortOrderController;
use Maatify\AdminKernel\Http\Controllers\Api\TranslationKeysQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\TranslationKeysUpdateDescriptionController;
use Maatify\AdminKernel\Http\Controllers\Api\TranslationKeysUpdateNameController;
use Maatify\AdminKernel\Http\Controllers\AuthController;
use Maatify\AdminKernel\Http\Controllers\NotificationQueryController;
use Maatify\AdminKernel\Http\Controllers\Ui\LanguagesListController;
use Maatify\AdminKernel\Http\Controllers\Ui\TranslationKeysListController;
use Maatify\AdminKernel\Http\DTO\AdminMiddlewareOptionsDTO;
use Maatify\AdminKernel\Http\Middleware\ApiGuestGuardMiddleware;
use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Maatify\AdminKernel\Http\Middleware\HttpRequestTelemetryMiddleware;
use Maatify\AdminKernel\Http\Middleware\RequestContextMiddleware;
use Maatify\AdminKernel\Http\Middleware\RequestIdMiddleware;
use Maatify\AdminKernel\Http\Middleware\SessionGuardMiddleware;
use Maatify\AdminKernel\Http\Middleware\WebGuestGuardMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteCollectorProxyInterface;

class AdminRoutes
{
    /**
     * @phpstan-param RouteCollectorProxyInterface<\Psr\Container\ContainerInterface|null> $app
     */
    public static function register(
        RouteCollectorProxyInterface $app,
        ?AdminMiddlewareOptionsDTO $options = null
    ): void {
        $options ??= new AdminMiddlewareOptionsDTO();

        $group = $app->group('', function (RouteCollectorProxyInterface $app) {
            $app->get('/health', function (Request $request, Response $response) {
                $payload = json_encode(['status' => 'ok']);
                $response->getBody()->write((string)$payload);
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
            });

            // User-facing UI Routes (Clean URLs)
            $app->group('', function (RouteCollectorProxyInterface $group) {
                // Guest Routes
                $group->group('', function (RouteCollectorProxyInterface $guestGroup) {
                    $guestGroup->get('/login', [\Maatify\AdminKernel\Http\Controllers\Ui\UiLoginController::class, 'index']);

                    $guestGroup->post('/login', [\Maatify\AdminKernel\Http\Controllers\Ui\UiLoginController::class, 'login'])
                        ->add(\Maatify\AbuseProtection\Middleware\AbuseProtectionMiddleware::class)
                        ->add(\Maatify\AdminKernel\Http\Middleware\AbuseCookieReaderMiddleware::class);

                    $guestGroup->get('/verify-email', [\Maatify\AdminKernel\Http\Controllers\Ui\UiVerificationController::class, 'index']);
                    $guestGroup->post('/verify-email', [\Maatify\AdminKernel\Http\Controllers\Ui\UiVerificationController::class, 'verify']);
                    $guestGroup->post('/verify-email/resend', [\Maatify\AdminKernel\Http\Controllers\Ui\UiVerificationController::class, 'resend']);

                    $guestGroup->get('/error', [\Maatify\AdminKernel\Http\Controllers\Ui\UiErrorController::class, 'index']);

                    // Change Password (Forced / Initial)
                    $guestGroup->get(
                        '/auth/change-password',
                        [\Maatify\AdminKernel\Http\Controllers\Web\ChangePasswordController::class, 'index']
                    );

                    $guestGroup->post(
                        '/auth/change-password',
                        [\Maatify\AdminKernel\Http\Controllers\Web\ChangePasswordController::class, 'change']
                    );
                })->add(WebGuestGuardMiddleware::class);

                // Step-Up Verification (Session only, no Active check)
                $group->group('', function (RouteCollectorProxyInterface $stepUpGroup) {
                    $stepUpGroup->get('/2fa/verify', [\Maatify\AdminKernel\Http\Controllers\Ui\UiStepUpController::class, 'verify']);
                    $stepUpGroup->post('/2fa/verify', [\Maatify\AdminKernel\Http\Controllers\Ui\UiStepUpController::class, 'doVerify']);
                })
                ->add(\Maatify\AdminKernel\Http\Middleware\AdminContextMiddleware::class)
                ->add(SessionGuardMiddleware::class);

                // Protected UI Routes (Dashboard)
                $group->group('', function (RouteCollectorProxyInterface $protectedGroup) {
                    $protectedGroup->get('/', [\Maatify\AdminKernel\Http\Controllers\Ui\UiDashboardController::class, 'index']);
                    $protectedGroup->get('/dashboard', [\Maatify\AdminKernel\Http\Controllers\Ui\UiDashboardController::class, 'index']);

                    // ─────────────────────────────
                    // 2FA Setup (Enrollment)
                    // ─────────────────────────────

                    $protectedGroup->get(
                        '/2fa/setup',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiTwoFactorSetupController::class, 'index']
                    )
                        ->setName('2fa.setup');

                    $protectedGroup->post(
                        '/2fa/setup',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiTwoFactorSetupController::class, 'enable']
                    )
                        ->setName('2fa.enable');


                    $protectedGroup->get(
                        '/admins',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiAdminsController::class, 'index']
                    )
                        ->setName('admins.list.ui')
                        ->add(AuthorizationGuardMiddleware::class);

                    $protectedGroup->get(
                        '/admins/create',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiAdminCreateController::class, 'index']
                    )
                        ->setName('admin.create.ui')
                        ->add(AuthorizationGuardMiddleware::class);

                    // ===============================
                    // Admin Profile (VIEW)
                    // ===============================
                    $protectedGroup->get(
                        '/admins/{id:[0-9]+}/profile',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiAdminsController::class, 'profile']
                    )
                        ->setName('admins.profile.view')
                        ->add(AuthorizationGuardMiddleware::class);

                    // ===============================
                    // Admin Profile (EDIT FORM)
                    // ===============================
                    $protectedGroup->get(
                        '/admins/{id:[0-9]+}/profile/edit',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiAdminsController::class, 'editProfile']
                    )
                        ->setName('admins.profile.edit.view')
                        ->add(AuthorizationGuardMiddleware::class);


                    // ===============================
                    // Admin Profile (UPDATE)
                    // ===============================
                    $protectedGroup->post(
                        '/admins/{id:[0-9]+}/profile/edit',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiAdminsController::class, 'updateProfile']
                    )
                        ->setName('admins.profile.edit')
                        ->add(AuthorizationGuardMiddleware::class);

                    // ─────────────────────────────
                    // Admin Email Control
                    // ─────────────────────────────
                    $protectedGroup->get(
                        '/admins/{id:[0-9]+}/emails',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiAdminsController::class, 'emails']
                    )
                        ->setName('admin.email.list.ui')
                        ->add(AuthorizationGuardMiddleware::class);

                    // ─────────────────────────────
                    // Admin Session Control
                    // ─────────────────────────────
                    $protectedGroup->get(
                        '/admins/{id:[0-9]+}/sessions',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiAdminsController::class, 'sessions']
                    )
                        ->setName('admins.session.list')
                        ->add(AuthorizationGuardMiddleware::class);

                    // ─────────────────────────────
                    // Admin Permissions Control
                    // ─────────────────────────────
                    $protectedGroup->get(
                        '/admins/{id:[0-9]+}/permissions',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiAdminsController::class, 'permissions']
                    )
                        ->setName('admins.permissions')
                        ->add(AuthorizationGuardMiddleware::class);

                    // ─────────────────────────────
                    // Permission By ID Control
                    // ─────────────────────────────
                    $protectedGroup->get(
                        '/permissions/{permission_id:[0-9]+}',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiAPermissionDetailsController::class, 'index']
                    )
                        ->setName('permission.details.ui')
                        ->add(AuthorizationGuardMiddleware::class);

                    $protectedGroup->get(
                        '/roles',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiRolesController::class, 'index']
                    )
                        ->setName('roles.query.ui')
                        ->add(AuthorizationGuardMiddleware::class);

                    $protectedGroup->get(
                        '/roles/{id:[0-9]+}',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiRoleDetailsController::class, '__invoke']
                    )
                        ->setName('roles.view.ui')
                        ->add(AuthorizationGuardMiddleware::class);

                    $protectedGroup->get(
                        '/permissions',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiPermissionsController::class, 'index']
                    )
                        ->setName('permissions.query.ui')
                        ->add(AuthorizationGuardMiddleware::class);

                    $protectedGroup->get('/languages', [LanguagesListController::class, '__invoke'])
                        ->setName('languages.list.ui')
                        ->add(AuthorizationGuardMiddleware::class);

                    $protectedGroup->get('/i18n/keys', [TranslationKeysListController::class, '__invoke'])
                        ->setName('i18n.keys.list.ui')
                        ->add(AuthorizationGuardMiddleware::class);

                    $protectedGroup->get('/settings', [\Maatify\AdminKernel\Http\Controllers\Ui\UiSettingsController::class, 'index']);

                    // UI sandbox for Twig/layout experimentation (non-canonical page)
                    $protectedGroup->get('/examples', [\Maatify\AdminKernel\Http\Controllers\Ui\UiExamplesController::class, 'index']);

                    // Phase 14.3: Sessions LIST
                    $protectedGroup->get(
                        '/sessions',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\SessionListController::class, '__invoke']
                    )
                        ->setName('sessions.list.ui')
                        ->add(AuthorizationGuardMiddleware::class);

                    // ─────────────────────────────
                    // Activity Logs
                    // ─────────────────────────────

                    $protectedGroup->get('/activity-logs', [\Maatify\AdminKernel\Http\Controllers\Ui\ActivityLogListController::class, 'index'])
                        ->setName('activity_logs.view');

                    $protectedGroup->get('/telemetry', [\Maatify\AdminKernel\Http\Controllers\Ui\TelemetryListController::class, 'index'])
                        ->setName('telemetry.list');

                    // Allow logout from UI
                    $protectedGroup->post('/logout', [\Maatify\AdminKernel\Http\Controllers\Web\LogoutController::class, 'logout'])
                        ->setName('auth.logout');
                    $protectedGroup->get('/logout', [\Maatify\AdminKernel\Http\Controllers\Web\LogoutController::class, 'logout'])
                        ->setName('auth.logout.web');
                })
                    // NOTE [Slim Middleware Order]:
                    // Slim executes middlewares in LIFO order (last added = first executed).
                    // This ordering is intentional so AdminContextMiddleware runs
                    // BEFORE TwigAdminContextMiddleware, allowing Twig to safely
                    // consume AdminContext and expose `current_admin` as a global.
                ->add(\Maatify\AdminKernel\Http\Middleware\TwigAdminContextMiddleware::class)
                ->add(\Maatify\AdminKernel\Http\Middleware\ScopeGuardMiddleware::class)
                ->add(\Maatify\AdminKernel\Http\Middleware\SessionStateGuardMiddleware::class)
                ->add(\Maatify\AdminKernel\Http\Middleware\AdminContextMiddleware::class)
                ->add(SessionGuardMiddleware::class)
                ->add(\Maatify\AdminKernel\Http\Middleware\RememberMeMiddleware::class);

            })->add(\Maatify\AdminKernel\Http\Middleware\UiRedirectNormalizationMiddleware::class);

            // ========================================
            // ================== API =================
            // ========================================
            // API Routes (JSON only)
            $app->group('/api', function (RouteCollectorProxyInterface $api) {
                // Public API
                $api->post('/auth/login', [AuthController::class, 'login'])
                    ->add(ApiGuestGuardMiddleware::class)
                    ->add(\Maatify\AbuseProtection\Middleware\AbuseProtectionMiddleware::class);

                // Step-Up API
                $api->post('/auth/step-up', [\Maatify\AdminKernel\Http\Controllers\StepUpController::class, 'verify'])
                    ->add(\Maatify\AdminKernel\Http\Middleware\AdminContextMiddleware::class)
                    ->add(SessionGuardMiddleware::class)
                    ->setName('auth.stepup.verify');

                // Protected API
                $api->group('', function (RouteCollectorProxyInterface $group) {
                    // Phase 14.3: Sessions Query
                    $group->group('/sessions', function (RouteCollectorProxyInterface $sessions) {
                        $sessions->post('/query', [\Maatify\AdminKernel\Http\Controllers\Api\SessionQueryController::class, '__invoke'])
                            ->setName('sessions.list.api');

                        $sessions->delete('/{session_id}', [\Maatify\AdminKernel\Http\Controllers\Api\SessionRevokeController::class, '__invoke'])
                            ->setName('sessions.revoke.id');

                        $sessions->post('/revoke-bulk', [\Maatify\AdminKernel\Http\Controllers\Api\SessionBulkRevokeController::class, '__invoke'])
                            ->setName('sessions.revoke.bulk');
                    });

                    // ─────────────────────────────
                    // i18n Keys Control
                    // ─────────────────────────────
                    $group->group('/i18n', function (RouteCollectorProxyInterface $i18n) {
                        $i18n->group('/keys', function (RouteCollectorProxyInterface $keys) {
                            $keys->post('/query', [TranslationKeysQueryController::class, '__invoke'])
                                ->setName('i18n.keys.list.api');

                            $keys->post('/create', [TranslationKeysCreateController::class, '__invoke'])
                                ->setName('i18n.keys.create.api');

                            $keys->post('/update-name', [TranslationKeysUpdateNameController::class, '__invoke'])
                                ->setName('i18n.keys.update.name.api');

                            $keys->post('/update-description', [TranslationKeysUpdateDescriptionController::class, '__invoke'])
                                ->setName('i18n.keys.update.description.api');
                        });
                    });

                    // ─────────────────────────────
                    // languages Control
                    // ─────────────────────────────
                    $group->group('/languages', function (RouteCollectorProxyInterface $languages) {
                        $languages->post('/query', [LanguagesQueryController::class, '__invoke'])
                            ->setName('languages.list.api');

                        $languages->post('/create', [LanguagesCreateController::class, '__invoke'])
                            ->setName('languages.create.api');

                        $languages->post('/update-settings', [LanguagesUpdateSettingsController::class, '__invoke'])
                            ->setName('languages.update.settings.api');

                        $languages->post('/set-active', [LanguagesSetActiveController::class, '__invoke'])
                            ->setName('languages.set.active.api');

                        $languages->post('/set-fallback', [LanguagesSetFallbackController::class, '__invoke'])
                            ->setName('languages.set.fallback.api');

                        $languages->post('/clear-fallback', [LanguagesClearFallbackController::class, '__invoke'])
                            ->setName('languages.clear.fallback.api');

                        $languages->post('/update-sort', [LanguagesUpdateSortOrderController::class, '__invoke'])
                            ->setName('languages.update.sort.api');

                        $languages->post('/update-name', [LanguagesUpdateNameController::class, '__invoke'])
                            ->setName('languages.update.name.api');

                        $languages->post('/update-code', [LanguagesUpdateCodeController::class, '__invoke'])
                            ->setName('languages.update.code.api');

                    });

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

                    $group->group('/admin-emails', function (RouteCollectorProxyInterface $adminEmails) {
                        $adminEmails->post('/{emailId:[0-9]+}/verify', [AdminEmailVerificationController::class, 'verify'])
                            ->setName('admin.email.verify');
                        $adminEmails->post('/{emailId:[0-9]+}/replace', [AdminEmailVerificationController::class, 'replace'])
                            ->setName('admin.email.replace');
                        $adminEmails->post('/{emailId:[0-9]+}/fail', [AdminEmailVerificationController::class, 'fail'])
                            ->setName('admin.email.fail');
                        $adminEmails->post('/{emailId}/restart-verification', [AdminEmailVerificationController::class, 'restart'])
                            ->setName('admin.email.restart');
                    });

                    // ─────────────────────────────
                    // Permissions Control
                    // ─────────────────────────────
                    $group->group('/permissions', function (RouteCollectorProxyInterface $permissions) {
                        $permissions->post('/query', [\Maatify\AdminKernel\Http\Controllers\Api\PermissionsController::class, '__invoke'])
                            ->setName('permissions.query.api');

                        $permissions->post('/{id:[0-9]+}/metadata', [\Maatify\AdminKernel\Http\Controllers\Api\PermissionMetadataUpdateController::class, '__invoke'])
                            ->setName('permissions.metadata.update');
                    });

                    // ─────────────────────────────
                    // Permission → Roles (Query)
                    // ─────────────────────────────
                    $group->post(
                        '/permissions/{permission_id:[0-9]+}/roles/query',
                        \Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionRolesQueryController::class
                    )
                        ->setName('permissions.roles.query')
                        ->add(AuthorizationGuardMiddleware::class);

                    // ─────────────────────────────
                    // Permission → Admins (Direct Overrides Query)
                    // ─────────────────────────────
                    $group->post(
                        '/permissions/{permission_id:[0-9]+}/admins/query',
                        \Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionAdminsQueryController::class
                    )
                        ->setName('permissions.admins.query')
                        ->add(AuthorizationGuardMiddleware::class);



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


                    $group->get('/notifications', [NotificationQueryController::class, 'index'])
                        ->setName('notifications.list');

                    $group->post('/admin/notifications/{id}/read', [\Maatify\AdminKernel\Http\Controllers\AdminNotificationReadController::class, 'markAsRead'])
                        ->setName('admin.notifications.read');

                })
                    // NOTE [Slim Middleware Order]:
                    // Slim executes middlewares in LIFO order (last added = first executed).
                    // This ordering is intentional so AdminContextMiddleware runs
                    // BEFORE TwigAdminContextMiddleware, allowing Twig to safely
                    // consume AdminContext and expose `current_admin` as a global.
                ->add(AuthorizationGuardMiddleware::class)
                ->add(\Maatify\AdminKernel\Http\Middleware\ScopeGuardMiddleware::class)
                ->add(\Maatify\AdminKernel\Http\Middleware\SessionStateGuardMiddleware::class)
                ->add(\Maatify\AdminKernel\Http\Middleware\AdminContextMiddleware::class)
                ->add(SessionGuardMiddleware::class);
            });

            // Webhooks
            $app->post('/webhooks/telegram', [\Maatify\AdminKernel\Http\Controllers\TelegramWebhookController::class, 'handle']);
        });

        // Middleware applied to the group (LIFO execution: Input -> Recovery)
        $group
            ->add(\Maatify\AdminKernel\Http\Middleware\RecoveryStateMiddleware::class)
            ->add(\Maatify\InputNormalization\Middleware\InputNormalizationMiddleware::class);

        // Explicit Infrastructure Middleware (Outer Layer)
        // Execution Order (LIFO): RequestId -> Context -> Telemetry -> Input -> Recovery
        if ($options->withInfrastructure) {
            $group
                ->add(HttpRequestTelemetryMiddleware::class)
                ->add(RequestContextMiddleware::class)
                ->add(RequestIdMiddleware::class);
        }
    }
}
