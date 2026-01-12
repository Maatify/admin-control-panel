<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;

class RequestIdMiddleware implements MiddlewareInterface
{
    private const HEADER_NAME = 'X-Request-ID';
    private const ATTRIBUTE_NAME = 'request_id';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 1. Check for existing ID in header, or generate new UUID v4
        $requestId = $request->getHeaderLine(self::HEADER_NAME);

        if (empty($requestId)) {
            $requestId = Uuid::uuid4()->toString();
        }

        // 2. Attach to Request Attribute
        $request = $request->withAttribute(self::ATTRIBUTE_NAME, $requestId);

        // 3. Process the request
        $response = $handler->handle($request);

        // 4. Attach to Response Header
        return $response->withHeader(self::HEADER_NAME, $requestId);
    }
}
