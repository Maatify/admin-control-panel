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
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $serverParams = $request->getServerParams();
        $ipAddress = $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = $serverParams['HTTP_USER_AGENT'] ?? 'Unknown';

        // Request ID is set by RequestIdMiddleware
        $requestId = $request->getAttribute('request_id');
        if (!is_string($requestId)) {
            $requestId = 'unknown';
        }

        $context = new RequestContext(
            $requestId,
            (string) $ipAddress,
            (string) $userAgent
        );

        $request = $request->withAttribute(RequestContext::class, $context);

        return $handler->handle($request);
    }
}
