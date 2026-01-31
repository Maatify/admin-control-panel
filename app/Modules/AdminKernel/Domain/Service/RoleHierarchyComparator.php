<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Service;

use Maatify\AdminKernel\Domain\Contracts\AdminRoleRepositoryInterface;
use Maatify\AdminKernel\Domain\Contracts\Roles\RoleRepositoryInterface;
use Maatify\AdminKernel\Domain\Enum\RoleLevel;
use LogicException;

class RoleHierarchyComparator
{
    public function __construct(
        private AdminRoleRepositoryInterface $adminRoleRepository,
        private RoleRepositoryInterface $roleRepository,
        private RoleLevelResolver $resolver
    ) {
    }

    public function guardInvariants(int $actorId, int $targetRoleId): void
    {
        // 1. Validate Actor
        $actorRoleIds = $this->adminRoleRepository->getRoleIds($actorId);
        if (empty($actorRoleIds)) {
            // No roles = explicit denial, but hierarchy is technically deterministic (level 0).
            // However, strictly speaking, an admin with no roles should probably not be assigning.
            // But 'canAssign' returns false, so it handles it.
            // Is this "ambiguous"? 0 vs Target.
            // Let's assume having no roles is valid (Level 0).
            // But having UNKNOWN roles is ambiguous.
        }

        foreach ($actorRoleIds as $roleId) {
            $roleName = $this->roleRepository->getName($roleId);
            $level = $this->resolver->resolve($roleName);
            if ($level === RoleLevel::UNKNOWN) {
                 throw new LogicException("Actor role '{$roleName}' resolves to UNKNOWN level. Hierarchy ambiguous.");
            }
        }

        // 2. Validate Target
        $targetRoleName = $this->roleRepository->getName($targetRoleId);
        $targetLevel = $this->resolver->resolve($targetRoleName);
        if ($targetLevel === RoleLevel::UNKNOWN) {
             throw new LogicException("Target role '{$targetRoleName}' resolves to UNKNOWN level. Hierarchy ambiguous.");
        }
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
