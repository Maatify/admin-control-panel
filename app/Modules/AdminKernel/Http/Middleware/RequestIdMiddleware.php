<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\InvalidUuidStringException;

final class RequestIdMiddleware implements MiddlewareInterface
{
    private const HEADER_NAME = 'X-Request-ID';
    private const ATTRIBUTE_NAME = 'request_id';

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $requestId = $this->resolveRequestId($request);

        // Attach to request attribute
        $request = $request->withAttribute(self::ATTRIBUTE_NAME, $requestId);

        // Handle request
        $response = $handler->handle($request);

        // Propagate to response header
        return $response->withHeader(self::HEADER_NAME, $requestId);
    }

    private function resolveRequestId(ServerRequestInterface $request): string
    {
        $header = $request->getHeaderLine(self::HEADER_NAME);

        if ($header !== '') {
            try {
                $uuid = Uuid::fromString($header);

                // 🔒 ACCEPT UUID v4 ONLY
                if ($uuid->getVersion() === 4) {
                    return $uuid->toString();
                }
            } catch (InvalidUuidStringException) {
                // Invalid UUID → fallback
            }
        }

        return Uuid::uuid4()->toString();
    }
}
