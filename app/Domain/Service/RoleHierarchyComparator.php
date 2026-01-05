<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminRoleRepositoryInterface;
use App\Domain\Contracts\RoleRepositoryInterface;
use App\Domain\Enum\RoleLevel;

class RoleHierarchyComparator
{
    public function __construct(
        private AdminRoleRepositoryInterface $adminRoleRepository,
        private RoleRepositoryInterface $roleRepository,
        private RoleLevelResolver $resolver
    ) {
    }

    public function canAssign(int $actorId, int $targetRoleId): bool
    {
        // 1. Get Actor's Roles
        $actorRoleIds = $this->adminRoleRepository->getRoleIds($actorId);

        if (empty($actorRoleIds)) {
            return false;
        }

        // 2. Resolve Actor's Highest Level
        $maxActorLevel = 0;
        foreach ($actorRoleIds as $roleId) {
            $roleName = $this->roleRepository->getName($roleId);
            $level = $this->resolver->resolve($roleName)->value;
            if ($level > $maxActorLevel) {
                $maxActorLevel = $level;
            }
        }

        // 3. Resolve Target Role Level
        $targetRoleName = $this->roleRepository->getName($targetRoleId);
        $targetLevel = $this->resolver->resolve($targetRoleName)->value;

        // 4. Compare: Actor must possess a role >= target role
        return $maxActorLevel >= $targetLevel;
    }
}
