<?php

declare(strict_types=1);

use App\Http\Controllers\AdminController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    $app->get('/health', function (Request $request, Response $response) {
        $payload = json_encode(['status' => 'ok']);
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    });

    $app->post('/admins', [AdminController::class, 'create']);
    $app->post('/admins/{id}/emails', [AdminController::class, 'addEmail']);
    $app->post('/admin-identifiers/email/lookup', [AdminController::class, 'lookupEmail']);
    $app->get('/admins/{id}/emails', [AdminController::class, 'getEmail']);
};
