<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Middleware;

use Maatify\AdminKernel\Context\RequestContext;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;
use Maatify\AdminKernel\Domain\Exception\UnauthorizedException;
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
        $adminContext = $request->getAttribute(\Maatify\AdminKernel\Context\AdminContext::class);
        if (!$adminContext instanceof \Maatify\AdminKernel\Context\AdminContext) {
            throw new \RuntimeException('AdminContext missing');
        }
        $adminId = $adminContext->adminId;

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
