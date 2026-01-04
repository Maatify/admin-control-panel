<?php

declare(strict_types=1);

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
    $app->group('', function (RouteCollectorProxy $group) {
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
    })->add(SessionGuardMiddleware::class);

    // Phase 4
    $app->post('/auth/login', [AuthController::class, 'login']);
};
