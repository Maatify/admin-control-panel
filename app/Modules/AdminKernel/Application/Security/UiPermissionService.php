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
}
