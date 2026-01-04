<?php

declare(strict_types=1);

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminEmailVerificationController;
use App\Http\Controllers\AuthController;
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

    // Public routes
    $app->post('/auth/login', [AuthController::class, 'login']);

    // Protected routes
    $app->group('', function (RouteCollectorProxy $group) {
        $group->post('/admins', [AdminController::class, 'create']);
        $group->post('/admins/{id}/emails', [AdminController::class, 'addEmail']);
        $group->post('/admin-identifiers/email/lookup', [AdminController::class, 'lookupEmail']);
        $group->get('/admins/{id}/emails', [AdminController::class, 'getEmail']);
        $group->post('/admins/{id}/emails/verify', [AdminEmailVerificationController::class, 'verify']);
    })->add(SessionGuardMiddleware::class);
};
