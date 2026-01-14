<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Context\RequestContext;
use App\Domain\Service\AuthorizationService;
use App\Domain\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

readonly class AuthorizationGuardMiddleware implements MiddlewareInterface
{

    public function __construct(private AuthorizationService $authorizationService)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $adminId = $request->getAttribute('admin_id');

        if (empty($adminId) || !is_int($adminId)) {
             // Should have been enforced by SessionGuardMiddleware
             throw new UnauthorizedException("Authenticated session required.");
        }

        $route = RouteContext::fromRequest($request)->getRoute();

        if ($route === null) {
            throw new UnauthorizedException("Route not found.");
        }

        $permission = $route->getName();
        assert(is_string($permission) && $permission !== '', 'Permission attribute must be a non-empty string');

        $context = $request->getAttribute(RequestContext::class);
        if (!$context instanceof RequestContext) {
            throw new \RuntimeException("Request context missing");
        }

        $this->authorizationService->checkPermission($adminId, $permission, $context);

        return $handler->handle($request);
    }
}
