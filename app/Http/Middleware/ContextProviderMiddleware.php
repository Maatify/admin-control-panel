<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ContextProviderMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Inject request into container for Request-Scoped Context Provider
        // This assumes the container is mutable (DI\Container is by default).
        if (method_exists($this->container, 'set')) {
            $this->container->set(ServerRequestInterface::class, $request);
        }

        return $handler->handle($request);
    }
}
