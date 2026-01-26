<?php

declare(strict_types=1);

use App\Domain\Service\SessionValidationService;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminEmailVerificationController;
use App\Http\Controllers\AdminNotificationPreferenceController;
use App\Http\Controllers\Api\AdminQueryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationQueryController;
use App\Http\Middleware\AuthorizationGuardMiddleware;
use App\Http\Middleware\GuestGuardMiddleware;
use App\Http\Middleware\SessionGuardMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    // Instantiate Guest Guards
    $container = $app->getContainer();
    if ($container === null) {
        throw new \RuntimeException('Container not found');
    }

    $sessionValidationService = $container->get(SessionValidationService::class);
    $webGuestGuard = new GuestGuardMiddleware($sessionValidationService, false);
    $apiGuestGuard = new GuestGuardMiddleware($sessionValidationService, true);

    $app->get('/health', function (Request $request, Response $response) {
        $payload = json_encode(['status' => 'ok']);
        $response->getBody()->write((string)$payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    });

    // User-facing UI Routes (Clean URLs)
    $app->group('', function (RouteCollectorProxy $group) use ($webGuestGuard) {
        // Guest Routes
        $group->group('', function (RouteCollectorProxy $guestGroup) {
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
        })->add($webGuestGuard);

        // Step-Up Verification (Session only, no Active check)
        $group->group('', function (RouteCollectorProxy $stepUpGroup) {
            $stepUpGroup->get('/2fa/verify', [\App\Http\Controllers\Ui\UiStepUpController::class, 'verify']);
            $stepUpGroup->post('/2fa/verify', [\App\Http\Controllers\Ui\UiStepUpController::class, 'doVerify']);
        })
        ->add(\App\Http\Middleware\AdminContextMiddleware::class)
        ->add(SessionGuardMiddleware::class);

        // Protected UI Routes (Dashboard)
        $group->group('', function (RouteCollectorProxy $protectedGroup) {
            $protectedGroup->get('/', [\App\Http\Controllers\Ui\UiDashboardController::class, 'index']);
            $protectedGroup->get('/dashboard', [\App\Http\Controllers\Ui\UiDashboardController::class, 'index']);

            // ─────────────────────────────
            // 2FA Setup (Enrollment)
            // ─────────────────────────────

            $protectedGroup->get(
                '/2fa/setup',
                [\App\Http\Controllers\Ui\UiTwoFactorSetupController::class, 'index']
            )->setName('2fa.setup');

            $protectedGroup->post(
                '/2fa/setup',
                [\App\Http\Controllers\Ui\UiTwoFactorSetupController::class, 'enable']
            )->setName('2fa.enable');


            $protectedGroup->get('/admins', [\App\Http\Controllers\Ui\UiAdminsController::class, 'index'])
                ->setName('admins.list')
                ->add(AuthorizationGuardMiddleware::class);

            $protectedGroup->get('/admins/create', [\App\Http\Controllers\Ui\UiAdminCreateController::class, 'index'])
                ->setName('admin.create')
                ->add(AuthorizationGuardMiddleware::class);

            // ===============================
            // Admin Profile (VIEW)
            // ===============================
            $protectedGroup->get('/admins/{id}/profile', [\App\Http\Controllers\Ui\UiAdminsController::class, 'profile'])
                ->setName('admins.profile.view')
                ->add(AuthorizationGuardMiddleware::class);

            // ===============================
            // Admin Profile (EDIT FORM)
            // ===============================
            $protectedGroup->get(
                '/admins/{id}/profile/edit',
                [\App\Http\Controllers\Ui\UiAdminsController::class, 'editProfile']
            )->setName('admins.profile.edit')
                ->add(AuthorizationGuardMiddleware::class);


            // ===============================
            // Admin Profile (UPDATE)
            // ===============================
            $protectedGroup->post(
                '/admins/{id}/profile/edit',
                [\App\Http\Controllers\Ui\UiAdminsController::class, 'updateProfile']
            )->setName('admins.profile.edit')
                ->add(AuthorizationGuardMiddleware::class);

            // ─────────────────────────────
            // Admin Email Control
            // ─────────────────────────────
            $protectedGroup->get(
                '/admins/{id}/emails',
                [\App\Http\Controllers\Ui\UiAdminsController::class, 'emails']
            )->setName('admin.email.list')
                ->add(AuthorizationGuardMiddleware::class);

            // ─────────────────────────────
            // Admin Session Control
            // ─────────────────────────────
            $protectedGroup->get(
                '/admins/{id}/sessions',
                [\App\Http\Controllers\Ui\UiAdminsController::class, 'sessions']
            )->setName('admins.session.list')
                ->add(AuthorizationGuardMiddleware::class);

            $protectedGroup->get('/roles', [\App\Http\Controllers\Ui\UiRolesController::class, 'index']);
            $protectedGroup->get('/permissions', [\App\Http\Controllers\Ui\UiPermissionsController::class, 'index']);
            $protectedGroup->get('/settings', [\App\Http\Controllers\Ui\UiSettingsController::class, 'index']);

            // UI sandbox for Twig/layout experimentation (non-canonical page)
            $protectedGroup->get('/examples', [\App\Http\Controllers\Ui\UiExamplesController::class, 'index']);

            // Phase 14.3: Sessions LIST
            $protectedGroup->get('/sessions', [\App\Http\Controllers\Ui\SessionListController::class, '__invoke'])
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
    $app->group('/api', function (RouteCollectorProxy $api) use ($apiGuestGuard) {
        // Public API
        $api->post('/auth/login', [AuthController::class, 'login'])
            ->add($apiGuestGuard);

        // Step-Up API
        $api->post('/auth/step-up', [\App\Http\Controllers\StepUpController::class, 'verify'])
            ->add(\App\Http\Middleware\AdminContextMiddleware::class)
            ->add(SessionGuardMiddleware::class)
            ->setName('auth.stepup.verify');

        // Protected API
        $api->group('', function (RouteCollectorProxy $group) {
            // Phase 14.3: Sessions Query
            $group->post('/sessions/query', [\App\Http\Controllers\Api\SessionQueryController::class, '__invoke'])
                ->setName('sessions.list')
                ->add(AuthorizationGuardMiddleware::class)
            ;

            $group->delete('/sessions/{session_id}', [\App\Http\Controllers\Api\SessionRevokeController::class, '__invoke'])
                ->setName('sessions.revoke')
                ->add(AuthorizationGuardMiddleware::class);

            $group->post('/sessions/revoke-bulk', [\App\Http\Controllers\Api\SessionBulkRevokeController::class, '__invoke'])
                ->setName('sessions.revoke')
                ->add(AuthorizationGuardMiddleware::class);

            $group->post('/admins/query', [AdminQueryController::class, '__invoke'])
                ->setName('admins.query')
                ->add(AuthorizationGuardMiddleware::class);

            // ─────────────────────────────
            // Permissions Control
            // ─────────────────────────────
            $group->post('/permissions/query', [\App\Http\Controllers\Api\PermissionsController::class, '__invoke'])
                ->setName('permissions.query')
                ->add(AuthorizationGuardMiddleware::class);

            $group->post('/permissions/{id}/metadata', [\App\Http\Controllers\Api\PermissionMetadataUpdateController::class, '__invoke'])
                ->setName('permissions.metadata.update')
                ->add(AuthorizationGuardMiddleware::class);

            // ─────────────────────────────
            // Roles Control
            // ─────────────────────────────
            $group->post('/roles/query', [\App\Http\Controllers\Api\Roles\RolesControllerQuery::class, '__invoke'])
                ->setName('roles.query')
                ->add(AuthorizationGuardMiddleware::class);

            $group->post('/roles/{id}/metadata', [\App\Http\Controllers\Api\Roles\RoleMetadataUpdateController::class, '__invoke'])
                ->setName('roles.metadata.update')
                ->add(AuthorizationGuardMiddleware::class);

            $group->post('/roles/{id}/toggle', [\App\Http\Controllers\Api\Roles\RoleToggleController::class, '__invoke'])
                ->setName('roles.toggle')
                ->add(AuthorizationGuardMiddleware::class);

            $group->post('/roles/{id}/rename', [\App\Http\Controllers\Api\Roles\RoleRenameController::class, '__invoke'])
                ->setName('roles.rename')
                ->add(AuthorizationGuardMiddleware::class);

            // Notifications / Admins / Etc.
            $group->post('/admins/create', [AdminController::class, 'create'])
                ->setName('admin.create')
                ->add(AuthorizationGuardMiddleware::class);

            // ─────────────────────────────
            // Admin Email Control
            // ─────────────────────────────
            $group->get('/admins/{id}/emails', [AdminController::class, 'getEmails'])
                ->setName('admin.email.list')
                ->add(AuthorizationGuardMiddleware::class);
            $group->post('/admins/{id}/emails', [AdminController::class, 'addEmail'])
                ->setName('admin.email.add')
                ->add(AuthorizationGuardMiddleware::class);

            $group->post('/admin-emails/{emailId}/verify', [AdminEmailVerificationController::class, 'verify'])
                ->setName('admin.email.verify')
                ->add(AuthorizationGuardMiddleware::class);
            $group->post('/admin-emails/{emailId}/replace', [AdminEmailVerificationController::class, 'replace'])
                ->setName('admin.email.replace')
                ->add(AuthorizationGuardMiddleware::class);
            $group->post('/admin-emails/{emailId}/fail', [AdminEmailVerificationController::class, 'fail'])
                ->setName('admin.email.fail')
                ->add(AuthorizationGuardMiddleware::class);
            $group->post('/admin-emails/{emailId}/restart-verification', [AdminEmailVerificationController::class, 'restart'])
                ->setName('admin.email.restart')
                ->add(AuthorizationGuardMiddleware::class);

            $group->get('/notifications', [NotificationQueryController::class, 'index'])
                ->setName('notifications.list')
                ->add(AuthorizationGuardMiddleware::class);

            $group->get('/admins/{admin_id}/preferences', [AdminNotificationPreferenceController::class, 'getPreferences'])
                ->setName('admin.preferences.read')
                ->add(AuthorizationGuardMiddleware::class);

            $group->put('/admins/{admin_id}/preferences', [AdminNotificationPreferenceController::class, 'upsertPreference'])
                ->setName('admin.preferences.write')
                ->add(AuthorizationGuardMiddleware::class);

            $group->get('/admins/{admin_id}/notifications', [\App\Http\Controllers\AdminNotificationHistoryController::class, 'index'])
                ->setName('admin.notifications.history')
                ->add(AuthorizationGuardMiddleware::class);

            $group->post('/admin/notifications/{id}/read', [\App\Http\Controllers\AdminNotificationReadController::class, 'markAsRead'])
                ->setName('admin.notifications.read')
                ->add(AuthorizationGuardMiddleware::class);

        })
        ->add(\App\Http\Middleware\ScopeGuardMiddleware::class)
        ->add(\App\Http\Middleware\SessionStateGuardMiddleware::class)
        ->add(\App\Http\Middleware\AdminContextMiddleware::class)
        ->add(SessionGuardMiddleware::class);
    });

    // Webhooks
    $app->post('/webhooks/telegram', [\App\Http\Controllers\TelegramWebhookController::class, 'handle']);

    // IMPORTANT:
    // InputNormalizationMiddleware MUST run before validation and guards.
    // It is added last to ensure it executes first in Slim's middleware stack.

    $app->add(\App\Http\Middleware\RecoveryStateMiddleware::class);
    $app->add(\App\Modules\InputNormalization\Middleware\InputNormalizationMiddleware::class);
    $app->add(\App\Http\Middleware\RequestContextMiddleware::class);
    $app->add(\App\Http\Middleware\RequestIdMiddleware::class);
    $app->add(\App\Http\Middleware\HttpRequestTelemetryMiddleware::class);
};
