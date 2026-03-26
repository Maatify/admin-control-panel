<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Application\Security;

use Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionMapperV2Interface;
use Maatify\AdminKernel\Domain\Service\AuthorizationService;

readonly class UiPermissionService
{
    public function __construct(
        private PermissionMapperV2Interface $permissionMapper,
        private AuthorizationService $authorizationService
    ) {}

    public function hasPermission(int $adminId, string $permission): bool
    {
        $requirement = $this->permissionMapper->resolve($permission);

        if (!$this->isResolvedValid($requirement, $permission)) {
            return false;
        }

        // AND logic
        if ($requirement->allOf !== []) {
            foreach ($requirement->allOf as $reqPerm) {
                if (!$this->authorizationService->hasPermission($adminId, $reqPerm)) {
                    return false;
                }
            }
            return true;
        }

        // OR logic: must have AT LEAST ONE permission
        if ($requirement->anyOf !== []) {
            foreach ($requirement->anyOf as $reqPerm) {
                if ($this->authorizationService->hasPermission($adminId, $reqPerm)) {
                    return true;
                }
            }
            return false;
        }

        return $this->authorizationService->hasPermission($adminId, $permission);
    }

    private function isResolvedValid(\Maatify\AdminKernel\Domain\Security\PermissionRequirement $requirement, string $originalPermission): bool
    {
        $allRequirements = array_merge($requirement->allOf, $requirement->anyOf);
        if (empty($allRequirements)) {
            $allRequirements[] = $originalPermission;
        }

        foreach ($allRequirements as $req) {
            if (preg_match('/^.+\.(api|ui|web|bulk|id)$/', $req)) {
                return false;
            }
        }
        return true;
    }
}
