<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Context\RequestContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestContextMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestId = $request->getAttribute('request_id');

        if (!is_string($requestId) || $requestId === '') {
            throw new \RuntimeException(
                'RequestContextMiddleware called without valid request_id. ' .
                'Ensure RequestIdMiddleware runs before RequestContextMiddleware.'
            );
        }

        $serverParams = $request->getServerParams();

        $ipAddress = $serverParams['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!is_string($ipAddress) || $ipAddress === '') {
            $ipAddress = '0.0.0.0';
        }

        $userAgent = $serverParams['HTTP_USER_AGENT'] ?? 'unknown';
        if (!is_string($userAgent) || $userAgent === '') {
            $userAgent = 'unknown';
        }

        /**
         * Route metadata (optional, best-effort)
         *
         * We intentionally keep these nullable to avoid
         * coupling RequestContext to routing internals.
         */
        $routeName = null;
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        $route = $request->getAttribute('__route__')
                 ?? $request->getAttribute('route');

        if (is_object($route)) {
            if (method_exists($route, 'getName')) {
                $name = $route->getName();
                if (is_string($name) && $name !== '') {
                    $routeName = $name;
                }
            }
        }

        $context = new RequestContext(
            requestId: $requestId,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            routeName: $routeName,
            method: $method !== '' ? $method : null,
            path: $path !== '' ? $path : null
        );

        $request = $request->withAttribute(RequestContext::class, $context);

        return $handler->handle($request);
    }
}
