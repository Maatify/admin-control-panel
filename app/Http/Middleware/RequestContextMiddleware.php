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
        // 1. Get Request ID
        $requestId = $request->getAttribute('request_id');
        if (!is_string($requestId) || $requestId === '') {
            // Hard Fail as per spec
            throw new \RuntimeException('Critical: Request ID missing in RequestContextMiddleware. Ensure RequestIdMiddleware runs first.');
        }

        // 2. Get IP and User Agent
        $serverParams = $request->getServerParams();
        $ipAddress = $serverParams['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $serverParams['HTTP_USER_AGENT'] ?? 'Unknown';

        // 3. Create Context
        $context = new RequestContext(
            requestId: $requestId,
            ipAddress: (string)$ipAddress,
            userAgent: (string)$userAgent
        );

        // 4. Attach to Request
        $request = $request->withAttribute(RequestContext::class, $context);

        // 5. Proceed
        return $handler->handle($request);
    }
}
