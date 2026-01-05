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

    // Web Routes
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/login', [\App\Http\Controllers\Web\LoginController::class, 'index']);
        $group->post('/login', [\App\Http\Controllers\Web\LoginController::class, 'login']);

        $group->get('/verify-email', [\App\Http\Controllers\Web\EmailVerificationController::class, 'index']);
        $group->post('/verify-email', [\App\Http\Controllers\Web\EmailVerificationController::class, 'verify']);
        $group->post('/verify-email/resend', [\App\Http\Controllers\Web\EmailVerificationController::class, 'resend']);
    })->add($webGuestGuard);

    // Protected Routes
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/dashboard', [\App\Http\Controllers\Web\DashboardController::class, 'index']);

        // Phase 13.1
        $group->get('/2fa/setup', [\App\Http\Controllers\Web\TwoFactorController::class, 'setup'])
            ->setName('2fa.setup');
        $group->post('/2fa/setup', [\App\Http\Controllers\Web\TwoFactorController::class, 'doSetup']);

        $group->get('/2fa/verify', [\App\Http\Controllers\Web\TwoFactorController::class, 'verify'])
            ->setName('2fa.verify');
        $group->post('/2fa/verify', [\App\Http\Controllers\Web\TwoFactorController::class, 'doVerify']);

        // Phase 13.3
        $group->get('/notifications/telegram/connect', [\App\Http\Controllers\Web\TelegramConnectController::class, 'index']);

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

        // Phase 3.4
        $group->post('/admins/{id}/emails/verify', [AdminEmailVerificationController::class, 'verify'])
            ->setName('email.verify')
            ->add(AuthorizationGuardMiddleware::class);

        // Phase 8.4
        $group->get('/notifications', [NotificationQueryController::class, 'index'])
            ->setName('notifications.list')
            ->add(AuthorizationGuardMiddleware::class);

        // Phase 11.1
        $group->get('/admins/{admin_id}/preferences', [AdminNotificationPreferenceController::class, 'getPreferences'])
            ->setName('admin.preferences.read')
            ->add(AuthorizationGuardMiddleware::class);

        $group->put('/admins/{admin_id}/preferences', [AdminNotificationPreferenceController::class, 'upsertPreference'])
            ->setName('admin.preferences.write')
            ->add(AuthorizationGuardMiddleware::class);

        // Phase 11.2
        $group->get('/admins/{admin_id}/notifications', [\App\Http\Controllers\AdminNotificationHistoryController::class, 'index'])
            ->setName('admin.notifications.history')
            ->add(AuthorizationGuardMiddleware::class);

        $group->post('/admin/notifications/{id}/read', [\App\Http\Controllers\AdminNotificationReadController::class, 'markAsRead'])
            ->setName('admin.notifications.read')
            ->add(AuthorizationGuardMiddleware::class);

        // Phase 13.4
        $group->post('/logout', [\App\Http\Controllers\Web\LogoutController::class, 'logout'])
            ->setName('auth.logout');
    })
    ->add(\App\Http\Middleware\ScopeGuardMiddleware::class)
    ->add(\App\Http\Middleware\SessionStateGuardMiddleware::class) // Phase 12 Session State Guard
    ->add(SessionGuardMiddleware::class)
    ->add(\App\Http\Middleware\RememberMeMiddleware::class); // Phase 13.5 Remember Me

    // Phase 4
    $app->post('/auth/login', [AuthController::class, 'login'])
        ->add($apiGuestGuard);

    // Phase 12
    $app->group('/auth', function (RouteCollectorProxy $group) {
        $group->post('/step-up', [\App\Http\Controllers\StepUpController::class, 'verify'])
            ->setName('auth.stepup.verify');
    })->add(SessionGuardMiddleware::class);
};
