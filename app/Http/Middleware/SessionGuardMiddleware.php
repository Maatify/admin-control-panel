<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Service\SessionValidationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionGuardMiddleware implements MiddlewareInterface
{
    private SessionValidationService $sessionValidationService;

    public function __construct(SessionValidationService $sessionValidationService)
    {
        $this->sessionValidationService = $sessionValidationService;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            // No token provided. Throwing an exception as per requirements for failures.
            // Using InvalidSessionException for missing token as well, or letting logic fall through if preferred.
            // Requirement says: "On failure: throw domain exception (NOT silent 401)".
            // However, InvalidSessionException is a Domain Exception.
            throw new \App\Domain\Exception\InvalidSessionException('No session token provided.');
        }

        $token = substr($authHeader, 7);

        $adminId = $this->sessionValidationService->validate($token);

        $request = $request->withAttribute('admin_id', $adminId);

        return $handler->handle($request);
    }
}
