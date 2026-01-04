<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Service\AuthorizationService;
use App\Domain\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthorizationGuardMiddleware implements MiddlewareInterface
{
    private AuthorizationService $authorizationService;
    private string $requiredPermission;

    public function __construct(AuthorizationService $authorizationService, string $requiredPermission)
    {
        $this->authorizationService = $authorizationService;
        $this->requiredPermission = $requiredPermission;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $adminId = $request->getAttribute('admin_id');

        if (empty($adminId) || !is_int($adminId)) {
             // Should have been enforced by SessionGuardMiddleware
             throw new UnauthorizedException("Authenticated session required.");
        }

        $this->authorizationService->checkPermission($adminId, $this->requiredPermission);

        return $handler->handle($request);
    }
}
