<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class AbuseCookieReaderMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        $cookies = $request->getCookieParams();

        $deviceId = $cookies['abuse_device_id'] ?? null;

        if (is_string($deviceId) && $deviceId !== '') {
            $request = $request->withAttribute('abuse_device_id', $deviceId);
        }

        return $handler->handle($request);
    }
}
