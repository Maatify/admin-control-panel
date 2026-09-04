<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class TrailingSlashMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        // Ignore root "/"
        if ($path !== '/' && str_ends_with($path, '/')) {
            $normalized = rtrim($path, '/');

            return (new Response())
                ->withHeader(
                    'Location',
                    (string)$uri->withPath($normalized)
                )
                ->withStatus(301);
        }

        return $handler->handle($request);
    }
}
