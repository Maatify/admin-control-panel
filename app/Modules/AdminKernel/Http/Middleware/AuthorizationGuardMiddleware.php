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

    public function __construct(
        private AuthorizationService $authorizationService,
        private \Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionMapperV2Interface $permissionMapper
    ) {
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

        $requirement = $this->permissionMapper->resolve($permission);

        // Validate resolution BEFORE enforcement to prevent AuthorizationService crash
        $this->assertResolvedRequirements($adminId, $requirement, $permission);

        // AND logic
        if ($requirement->allOf !== []) {
            foreach ($requirement->allOf as $reqPerm) {
                $this->authorizationService->checkPermission($adminId, $reqPerm, $context);
            }
            return $handler->handle($request);
        }

        // OR logic (including single permission wrapper)
        if ($requirement->anyOf !== []) {
            $hasAny = false;
            foreach ($requirement->anyOf as $reqPerm) {
                if ($this->authorizationService->hasPermission($adminId, $reqPerm)) {
                    $hasAny = true;
                    break;
                }
            }
            if (!$hasAny) {
                throw new \Maatify\AdminKernel\Domain\Exception\PermissionDeniedException(
                    "Admin $adminId lacks required permissions."
                );
            }
            return $handler->handle($request);
        }

        // Canonical Fallback (If requirement resolves to empty anyOf/allOf)
        $this->authorizationService->checkPermission($adminId, $permission, $context);

        return $handler->handle($request);
    }

    private function assertResolvedRequirements(int $adminId, \Maatify\AdminKernel\Domain\Security\PermissionRequirement $requirement, string $originalPermission): void
    {
        $allRequirements = array_merge($requirement->allOf, $requirement->anyOf);
        if (empty($allRequirements)) {
            $allRequirements[] = $originalPermission;
        }

        foreach ($allRequirements as $req) {
            if (preg_match('/^.+\.(api|ui|web|bulk|id)$/', $req)) {
                // Unresolved transport/variant must not leak into AuthorizationService. Safe degradation.
                throw new \Maatify\AdminKernel\Domain\Exception\PermissionDeniedException(
                    "Admin $adminId lacks required permissions (unresolved permission: $req)."
                );
            }
        }
    }
}
