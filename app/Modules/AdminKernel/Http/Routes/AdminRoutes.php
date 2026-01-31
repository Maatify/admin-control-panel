<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes;

use Maatify\AdminKernel\Http\Controllers\AdminController;
use Maatify\AdminKernel\Http\Controllers\AdminEmailVerificationController;
use Maatify\AdminKernel\Http\Controllers\AdminNotificationPreferenceController;
use Maatify\AdminKernel\Http\Controllers\Api\AdminQueryController;
use Maatify\AdminKernel\Http\Controllers\AuthController;
use Maatify\AdminKernel\Http\Controllers\NotificationQueryController;
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
                    $guestGroup->post('/login', [\Maatify\AdminKernel\Http\Controllers\Ui\UiLoginController::class, 'login']);

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
                        '/admins/{id}/profile',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiAdminsController::class, 'profile']
                    )
                        ->setName('admins.profile.view')
                        ->add(AuthorizationGuardMiddleware::class);

                    // ===============================
                    // Admin Profile (EDIT FORM)
                    // ===============================
                    $protectedGroup->get(
                        '/admins/{id}/profile/edit',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiAdminsController::class, 'editProfile']
                    )
                        ->setName('admins.profile.edit.view')
                        ->add(AuthorizationGuardMiddleware::class);


                    // ===============================
                    // Admin Profile (UPDATE)
                    // ===============================
                    $protectedGroup->post(
                        '/admins/{id}/profile/edit',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiAdminsController::class, 'updateProfile']
                    )
                        ->setName('admins.profile.edit')
                        ->add(AuthorizationGuardMiddleware::class);

                    // ─────────────────────────────
                    // Admin Email Control
                    // ─────────────────────────────
                    $protectedGroup->get(
                        '/admins/{id}/emails',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiAdminsController::class, 'emails']
                    )
                        ->setName('admin.email.list.ui')
                        ->add(AuthorizationGuardMiddleware::class);

                    // ─────────────────────────────
                    // Admin Session Control
                    // ─────────────────────────────
                    $protectedGroup->get(
                        '/admins/{id}/sessions',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiAdminsController::class, 'sessions']
                    )
                        ->setName('admins.session.list')
                        ->add(AuthorizationGuardMiddleware::class);

                    $protectedGroup->get(
                        '/roles',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiRolesController::class, 'index']
                    )
                        ->setName('roles.query.ui')
                        ->add(AuthorizationGuardMiddleware::class);

                    $protectedGroup->get(
                        '/permissions',
                        [\Maatify\AdminKernel\Http\Controllers\Ui\UiPermissionsController::class, 'index']
                    )
                        ->setName('permissions.query.ui')
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

            // API Routes (JSON only)
            $app->group('/api', function (RouteCollectorProxyInterface $api) {
                // Public API
                $api->post('/auth/login', [AuthController::class, 'login'])
                    ->add(ApiGuestGuardMiddleware::class);

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
                    // Admins Control
                    // ─────────────────────────────
                    $group->group('/admins', function (RouteCollectorProxyInterface $admins) {
                        $admins->post('/query', [AdminQueryController::class, '__invoke'])
                            ->setName('admins.list.api');

                        $admins->post('/create', [AdminController::class, 'create'])
                            ->setName('admin.create.api');

                        $admins->get('/{admin_id}/preferences', [AdminNotificationPreferenceController::class, 'getPreferences'])
                            ->setName('admin.preferences.read');

                        $admins->put('/{admin_id}/preferences', [AdminNotificationPreferenceController::class, 'upsertPreference'])
                            ->setName('admin.preferences.write');

                        $admins->get('/{admin_id}/notifications', [\Maatify\AdminKernel\Http\Controllers\AdminNotificationHistoryController::class, 'index'])
                            ->setName('admin.notifications.history');

                        // ─────────────────────────────
                        // Admin Email Control
                        // ─────────────────────────────
                        $admins->get('/{id}/emails', [AdminController::class, 'getEmails'])
                            ->setName('admin.email.list.api');
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
                        $permissions->post('/query', [\Maatify\AdminKernel\Http\Controllers\Api\PermissionsController::class, '__invoke'])
                            ->setName('permissions.query.api');

                        $permissions->post('/{id}/metadata', [\Maatify\AdminKernel\Http\Controllers\Api\PermissionMetadataUpdateController::class, '__invoke'])
                            ->setName('permissions.metadata.update');
                    });

                    // ─────────────────────────────
                    // Roles Control
                    // ─────────────────────────────
                    $group->group('/roles', function (RouteCollectorProxyInterface $roles) {
                        $roles->post('/query', [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RolesControllerQuery::class, '__invoke'])
                            ->setName('roles.query.api');

                        $roles->post('/{id}/metadata', [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleMetadataUpdateController::class, '__invoke'])
                            ->setName('roles.metadata.update');

                        $roles->post('/{id}/toggle', [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleToggleController::class, '__invoke'])
                            ->setName('roles.toggle');

                        $roles->post('/{id}/rename', [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleRenameController::class, '__invoke'])
                            ->setName('roles.rename');

                        $roles->post('/create', [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleCreateController::class, '__invoke'])
                            ->setName('roles.create');
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
