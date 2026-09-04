<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class HealthRoutes
{
    /**
     * @param RouteCollectorProxyInterface<ContainerInterface> $app
     */
    public static function register(RouteCollectorProxyInterface $app): void
    {
        $app->get('/health', function (Request $request, Response $response) {
            $payload = json_encode(['status' => 'ok']);
            $response->getBody()->write((string)$payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        });
    }
}
