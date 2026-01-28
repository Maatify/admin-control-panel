<?php

declare(strict_types=1);

namespace App\Http\Routes;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminEmailVerificationController;
use App\Http\Controllers\AdminNotificationPreferenceController;
use App\Http\Controllers\Api\AdminQueryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationQueryController;
use App\Http\Middleware\ApiGuestGuardMiddleware;
use App\Http\Middleware\AuthorizationGuardMiddleware;
use App\Http\Middleware\SessionGuardMiddleware;
use App\Http\Middleware\WebGuestGuardMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteCollectorProxyInterface;

class AdminRoutes
{
    public static function register(RouteCollectorProxyInterface $app): void
    {
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
                $guestGroup->get('/login', [\App\Http\Controllers\Ui\UiLoginController::class, 'index']);
                $guestGroup->post('/login', [\App\Http\Controllers\Ui\UiLoginController::class, 'login']);

                $guestGroup->get('/verify-email', [\App\Http\Controllers\Ui\UiVerificationController::class, 'index']);
                $guestGroup->post('/verify-email', [\App\Http\Controllers\Ui\UiVerificationController::class, 'verify']);
                $guestGroup->post('/verify-email/resend', [\App\Http\Controllers\Ui\UiVerificationController::class, 'resend']);

                $guestGroup->get('/error', [\App\Http\Controllers\Ui\UiErrorController::class, 'index']);

                // Change Password (Forced / Initial)
                $guestGroup->get(
                    '/auth/change-password',
                    [\App\Http\Controllers\Web\ChangePasswordController::class, 'index']
                );

                $guestGroup->post(
                    '/auth/change-password',
                    [\App\Http\Controllers\Web\ChangePasswordController::class, 'change']
                );
            })->add(WebGuestGuardMiddleware::class);

            // Step-Up Verification (Session only, no Active check)
            $group->group('', function (RouteCollectorProxyInterface $stepUpGroup) {
                $stepUpGroup->get('/2fa/verify', [\App\Http\Controllers\Ui\UiStepUpController::class, 'verify']);
                $stepUpGroup->post('/2fa/verify', [\App\Http\Controllers\Ui\UiStepUpController::class, 'doVerify']);
            })
            ->add(\App\Http\Middleware\AdminContextMiddleware::class)
            ->add(SessionGuardMiddleware::class);

            // Protected UI Routes (Dashboard)
            $group->group('', function (RouteCollectorProxyInterface $protectedGroup) {
                $protectedGroup->get('/', [\App\Http\Controllers\Ui\UiDashboardController::class, 'index']);
                $protectedGroup->get('/dashboard', [\App\Http\Controllers\Ui\UiDashboardController::class, 'index']);

                // ─────────────────────────────
                // 2FA Setup (Enrollment)
                // ─────────────────────────────

                $protectedGroup->get(
                    '/2fa/setup',
                    [\App\Http\Controllers\Ui\UiTwoFactorSetupController::class, 'index']
                )
                    ->setName('2fa.setup');

                $protectedGroup->post(
                    '/2fa/setup',
                    [\App\Http\Controllers\Ui\UiTwoFactorSetupController::class, 'enable']
                )
                    ->setName('2fa.enable');


                $protectedGroup->get(
                    '/admins',
                    [\App\Http\Controllers\Ui\UiAdminsController::class, 'index']
                )
                    ->setName('admins.list')
                    ->add(AuthorizationGuardMiddleware::class);

                $protectedGroup->get(
                    '/admins/create',
                    [\App\Http\Controllers\Ui\UiAdminCreateController::class, 'index']
                )
                    ->setName('admin.create')
                    ->add(AuthorizationGuardMiddleware::class);

                // ===============================
                // Admin Profile (VIEW)
                // ===============================
                $protectedGroup->get(
                    '/admins/{id}/profile',
                    [\App\Http\Controllers\Ui\UiAdminsController::class, 'profile']
                )
                    ->setName('admins.profile.view')
                    ->add(AuthorizationGuardMiddleware::class);

                // ===============================
                // Admin Profile (EDIT FORM)
                // ===============================
                $protectedGroup->get(
                    '/admins/{id}/profile/edit',
                    [\App\Http\Controllers\Ui\UiAdminsController::class, 'editProfile']
                )
                    ->setName('admins.profile.edit')
                    ->add(AuthorizationGuardMiddleware::class);


                // ===============================
                // Admin Profile (UPDATE)
                // ===============================
                $protectedGroup->post(
                    '/admins/{id}/profile/edit',
                    [\App\Http\Controllers\Ui\UiAdminsController::class, 'updateProfile']
                )
                    ->setName('admins.profile.edit')
                    ->add(AuthorizationGuardMiddleware::class);

                // ─────────────────────────────
                // Admin Email Control
                // ─────────────────────────────
                $protectedGroup->get(
                    '/admins/{id}/emails',
                    [\App\Http\Controllers\Ui\UiAdminsController::class, 'emails']
                )
                    ->setName('admin.email.list')
                    ->add(AuthorizationGuardMiddleware::class);

                // ─────────────────────────────
                // Admin Session Control
                // ─────────────────────────────
                $protectedGroup->get(
                    '/admins/{id}/sessions',
                    [\App\Http\Controllers\Ui\UiAdminsController::class, 'sessions']
                )
                    ->setName('admins.session.list')
                    ->add(AuthorizationGuardMiddleware::class);

                $protectedGroup->get(
                    '/roles',
                    [\App\Http\Controllers\Ui\UiRolesController::class, 'index']
                )
                    ->setName('roles.query')
                    ->add(AuthorizationGuardMiddleware::class);

                $protectedGroup->get(
                    '/permissions',
                    [\App\Http\Controllers\Ui\UiPermissionsController::class, 'index']
                )
                    ->setName('permissions.query')
                    ->add(AuthorizationGuardMiddleware::class);

                $protectedGroup->get('/settings', [\App\Http\Controllers\Ui\UiSettingsController::class, 'index']);

                // UI sandbox for Twig/layout experimentation (non-canonical page)
                $protectedGroup->get('/examples', [\App\Http\Controllers\Ui\UiExamplesController::class, 'index']);

                // Phase 14.3: Sessions LIST
                $protectedGroup->get(
                    '/sessions',
                    [\App\Http\Controllers\Ui\SessionListController::class, '__invoke']
                )
                    ->setName('sessions.list')
                    ->add(AuthorizationGuardMiddleware::class);

                // ─────────────────────────────
                // Activity Logs
                // ─────────────────────────────

                $protectedGroup->get('/activity-logs', [\App\Http\Controllers\Ui\ActivityLogListController::class, 'index'])
                    ->setName('activity_logs.view');

                $protectedGroup->get('/telemetry', [\App\Http\Controllers\Ui\TelemetryListController::class, 'index'])
                    ->setName('telemetry.list');

                // Allow logout from UI
                $protectedGroup->post('/logout', [\App\Http\Controllers\Web\LogoutController::class, 'logout'])
                    ->setName('auth.logout');

                // Allow logout from UI
                $protectedGroup->get('/logout', [\App\Http\Controllers\Web\LogoutController::class, 'logout'])
                    ->setName('auth.logout');
            })
            ->add(\App\Http\Middleware\ScopeGuardMiddleware::class)
            ->add(\App\Http\Middleware\SessionStateGuardMiddleware::class)
            ->add(\App\Http\Middleware\AdminContextMiddleware::class)
            ->add(SessionGuardMiddleware::class)
            ->add(\App\Http\Middleware\RememberMeMiddleware::class);

        })->add(\App\Http\Middleware\UiRedirectNormalizationMiddleware::class);

        // API Routes (JSON only)
        $app->group('/api', function (RouteCollectorProxyInterface $api) {
            // Public API
            $api->post('/auth/login', [AuthController::class, 'login'])
                ->add(ApiGuestGuardMiddleware::class);

            // Step-Up API
            $api->post('/auth/step-up', [\App\Http\Controllers\StepUpController::class, 'verify'])
                ->add(\App\Http\Middleware\AdminContextMiddleware::class)
                ->add(SessionGuardMiddleware::class)
                ->setName('auth.stepup.verify');

            // Protected API
            $api->group('', function (RouteCollectorProxyInterface $group) {
                // Phase 14.3: Sessions Query
                $group->group('/sessions', function (RouteCollectorProxyInterface $sessions) {
                    $sessions->post('/query', [\App\Http\Controllers\Api\SessionQueryController::class, '__invoke'])
                        ->setName('sessions.list');

                    $sessions->delete('/{session_id}', [\App\Http\Controllers\Api\SessionRevokeController::class, '__invoke'])
                        ->setName('sessions.revoke');

                    $sessions->post('/revoke-bulk', [\App\Http\Controllers\Api\SessionBulkRevokeController::class, '__invoke'])
                        ->setName('sessions.revoke');
                });

                // ─────────────────────────────
                // Admins Control
                // ─────────────────────────────
                $group->group('/admins', function (RouteCollectorProxyInterface $admins) {
                    $admins->post('/query', [AdminQueryController::class, '__invoke'])
                        ->setName('admins.query');

                    $admins->post('/create', [AdminController::class, 'create'])
                        ->setName('admin.create');

                    $admins->get('/{admin_id}/preferences', [AdminNotificationPreferenceController::class, 'getPreferences'])
                        ->setName('admin.preferences.read');

                    $admins->put('/{admin_id}/preferences', [AdminNotificationPreferenceController::class, 'upsertPreference'])
                        ->setName('admin.preferences.write');

                    $admins->get('/{admin_id}/notifications', [\App\Http\Controllers\AdminNotificationHistoryController::class, 'index'])
                        ->setName('admin.notifications.history');

                    // ─────────────────────────────
                    // Admin Email Control
                    // ─────────────────────────────
                    $admins->get('/{id}/emails', [AdminController::class, 'getEmails'])
                        ->setName('admin.email.list');
                    $admins->post('/{id}/emails', [AdminController::class, 'addEmail'])
                        ->setName('admin.email.add');
                });

                $group->group('/admin-emails', function (RouteCollectorProxyInterface $adminEmails) {
                    $adminEmails->post('/{emailId}/verify', [AdminEmailVerificationController::class, 'verify'])
                        ->setName('admin.email.verify');
                    $adminEmails->post('/{emailId}/replace', [AdminEmailVerificationController::class, 'replace'])
                        ->setName('admin.email.replace');
                    $adminEmails->post('/{emailId}/fail', [AdminEmailVerificationController::class, 'fail'])
                        ->setName('admin.email.fail');
                    $adminEmails->post('/{emailId}/restart-verification', [AdminEmailVerificationController::class, 'restart'])
                        ->setName('admin.email.restart');
                });

                // ─────────────────────────────
                // Permissions Control
                // ─────────────────────────────
                $group->group('/permissions', function (RouteCollectorProxyInterface $permissions) {
                    $permissions->post('/query', [\App\Http\Controllers\Api\PermissionsController::class, '__invoke'])
                        ->setName('permissions.query');

                    $permissions->post('/{id}/metadata', [\App\Http\Controllers\Api\PermissionMetadataUpdateController::class, '__invoke'])
                        ->setName('permissions.metadata.update');
                });

                // ─────────────────────────────
                // Roles Control
                // ─────────────────────────────
                $group->group('/roles', function (RouteCollectorProxyInterface $roles) {
                    $roles->post('/query', [\App\Http\Controllers\Api\Roles\RolesControllerQuery::class, '__invoke'])
                        ->setName('roles.query');

                    $roles->post('/{id}/metadata', [\App\Http\Controllers\Api\Roles\RoleMetadataUpdateController::class, '__invoke'])
                        ->setName('roles.metadata.update');

                    $roles->post('/{id}/toggle', [\App\Http\Controllers\Api\Roles\RoleToggleController::class, '__invoke'])
                        ->setName('roles.toggle');

                    $roles->post('/{id}/rename', [\App\Http\Controllers\Api\Roles\RoleRenameController::class, '__invoke'])
                        ->setName('roles.rename');

                    $roles->post('/create', [\App\Http\Controllers\Api\Roles\RoleCreateController::class, '__invoke'])
                        ->setName('roles.create');
                });


                $group->get('/notifications', [NotificationQueryController::class, 'index'])
                    ->setName('notifications.list');

                $group->post('/admin/notifications/{id}/read', [\App\Http\Controllers\AdminNotificationReadController::class, 'markAsRead'])
                    ->setName('admin.notifications.read');

            })
            ->add(AuthorizationGuardMiddleware::class)
            ->add(\App\Http\Middleware\ScopeGuardMiddleware::class)
            ->add(\App\Http\Middleware\SessionStateGuardMiddleware::class)
            ->add(\App\Http\Middleware\AdminContextMiddleware::class)
            ->add(SessionGuardMiddleware::class);
        });

        // Webhooks
        $app->post('/webhooks/telegram', [\App\Http\Controllers\TelegramWebhookController::class, 'handle']);
    }
}
