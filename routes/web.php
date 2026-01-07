<?php

declare(strict_types=1);

use App\Domain\Service\SessionValidationService;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminEmailVerificationController;
use App\Http\Controllers\AdminNotificationPreferenceController;
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
        })->add($webGuestGuard);

        // Step-Up Verification (Session only, no Active check)
        $group->group('', function (RouteCollectorProxy $stepUpGroup) {
            $stepUpGroup->get('/2fa/verify', [\App\Http\Controllers\Ui\UiStepUpController::class, 'verify']);
            $stepUpGroup->post('/2fa/verify', [\App\Http\Controllers\Ui\UiStepUpController::class, 'doVerify']);
        })->add(SessionGuardMiddleware::class);

        // Protected UI Routes (Dashboard)
        $group->group('', function (RouteCollectorProxy $protectedGroup) {
            $protectedGroup->get('/', [\App\Http\Controllers\Ui\UiDashboardController::class, 'index']);
            $protectedGroup->get('/dashboard', [\App\Http\Controllers\Ui\UiDashboardController::class, 'index']);
            $protectedGroup->get('/admins', [\App\Http\Controllers\Ui\UiAdminsController::class, 'list'])
                ->setName('admins.list')
                ->add(AuthorizationGuardMiddleware::class);
            $protectedGroup->get('/admins/create', [\App\Http\Controllers\Ui\UiAdminsController::class, 'create'])
                ->setName('admins.create')
                ->add(AuthorizationGuardMiddleware::class);
            $protectedGroup->get('/admins/{id}/edit', [\App\Http\Controllers\Ui\UiAdminsController::class, 'edit'])
                ->setName('admins.edit')
                ->add(AuthorizationGuardMiddleware::class);

            $protectedGroup->get('/roles', [\App\Http\Controllers\Ui\UiRolesController::class, 'index']);
            $protectedGroup->get('/permissions', [\App\Http\Controllers\Ui\UiPermissionsController::class, 'index']);
            $protectedGroup->get('/settings', [\App\Http\Controllers\Ui\UiSettingsController::class, 'index']);

            // Phase 14.3: Sessions LIST
            $protectedGroup->get('/sessions', [\App\Http\Controllers\Ui\SessionListController::class, '__invoke'])
                ->setName('sessions.list')
                ->add(AuthorizationGuardMiddleware::class);

            // Allow logout from UI
            $protectedGroup->post('/logout', [\App\Http\Controllers\Web\LogoutController::class, 'logout'])
                ->setName('auth.logout');
        })
        ->add(\App\Http\Middleware\ScopeGuardMiddleware::class)
        ->add(\App\Http\Middleware\SessionStateGuardMiddleware::class)
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

            $group->get('/admins/list', [\App\Http\Controllers\Api\AdminListController::class, '__invoke'])
                ->setName('sessions.view_all')
                ->add(AuthorizationGuardMiddleware::class);

            // Phase 1: Admin Management
            $group->post('/admins/query', [\App\Http\Controllers\Api\AdminsQueryController::class, '__invoke'])
                ->setName('admins.list')
                ->add(AuthorizationGuardMiddleware::class);
            $group->post('/admins/create', [\App\Http\Controllers\Api\AdminCreateController::class, '__invoke'])
                ->setName('admins.create')
                ->add(AuthorizationGuardMiddleware::class);
            $group->post('/admins/{id}/update', [\App\Http\Controllers\Api\AdminUpdateController::class, '__invoke'])
                ->setName('admins.edit')
                ->add(AuthorizationGuardMiddleware::class);
            $group->post('/admins/{id}/disable', [\App\Http\Controllers\Api\AdminDisableController::class, '__invoke'])
                ->setName('admins.disable')
                ->add(AuthorizationGuardMiddleware::class);

            // Notifications / Admins / Etc.
            $group->post('/admins', [AdminController::class, 'create'])
                ->setName('admin.create')
                ->add(AuthorizationGuardMiddleware::class);

            $group->post('/admins/{id}/emails', [AdminController::class, 'addEmail'])
                ->setName('email.add')
                ->add(AuthorizationGuardMiddleware::class);

            $group->post('/admin-identifiers/email/lookup', [AdminController::class, 'lookupEmail'])
                ->setName('email.lookup')
                ->add(AuthorizationGuardMiddleware::class);

            $group->get('/admins/{id}/emails', [AdminController::class, 'getEmail'])
                ->setName('email.read')
                ->add(AuthorizationGuardMiddleware::class);

            $group->post('/admins/{id}/emails/verify', [AdminEmailVerificationController::class, 'verify'])
                ->setName('email.verify')
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

            // Note: 2FA Setup/Verify API endpoints could be added here if needed, but currently Web 2FA controller handles UI logic.
            // Keeping them exposed as API requires separate controllers or careful handling.
            // For now, moving existing Web controllers to API path if they were intended for API, but mostly they were mixed.
            // The prompt says "GET /api/admins".
            // I've moved the admin routes here.
        })
        ->add(\App\Http\Middleware\ScopeGuardMiddleware::class)
        ->add(\App\Http\Middleware\SessionStateGuardMiddleware::class)
        ->add(SessionGuardMiddleware::class);
    });

    // Webhooks
    $app->post('/webhooks/telegram', [\App\Http\Controllers\TelegramWebhookController::class, 'handle']);

    $app->add(\App\Http\Middleware\RecoveryStateMiddleware::class);
};
