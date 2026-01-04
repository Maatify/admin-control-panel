<?php

declare(strict_types=1);

use App\Domain\Service\AuthorizationService;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminEmailVerificationController;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\AuthorizationGuardMiddleware;
use App\Http\Middleware\SessionGuardMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->get('/health', function (Request $request, Response $response) {
        $payload = json_encode(['status' => 'ok']);
        $response->getBody()->write((string)$payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    });

    // Protected Routes
    $app->group('', function (RouteCollectorProxy $group) use ($app) {
        $container = $app->getContainer();
        $authService = $container->get(AuthorizationService::class);

        $group->post('/admins', [AdminController::class, 'create'])
            ->add(new AuthorizationGuardMiddleware($authService, 'admin.create'));

        $group->post('/admins/{id}/emails', [AdminController::class, 'addEmail'])
            ->add(new AuthorizationGuardMiddleware($authService, 'email.add'));

        $group->post('/admin-identifiers/email/lookup', [AdminController::class, 'lookupEmail'])
            ->add(new AuthorizationGuardMiddleware($authService, 'email.lookup'));

        $group->get('/admins/{id}/emails', [AdminController::class, 'getEmail'])
            ->add(new AuthorizationGuardMiddleware($authService, 'email.read'));

        // Phase 3.4
        $group->post('/admins/{id}/emails/verify', [AdminEmailVerificationController::class, 'verify'])
            ->add(new AuthorizationGuardMiddleware($authService, 'email.verify'));
    })->add(SessionGuardMiddleware::class);

    // Phase 4
    $app->post('/auth/login', [AuthController::class, 'login']);
};
