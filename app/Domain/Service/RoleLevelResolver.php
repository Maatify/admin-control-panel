<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Enum\RoleLevel;

class RoleLevelResolver
{
    /**
     * @param string|null $roleName
     * @return RoleLevel
     */
    public function resolve(?string $roleName): RoleLevel
    {
        if ($roleName === null) {
            return RoleLevel::UNKNOWN;
        }

        // Normalize string comparison (identity check, not sort comparison)
        return match (strtoupper(str_replace(' ', '_', $roleName))) {
            'SUPER_ADMIN' => RoleLevel::SUPER_ADMIN,
            'ADMIN' => RoleLevel::ADMIN,
            'MANAGER', 'MODERATOR' => RoleLevel::MANAGER,
            'SUPPORT' => RoleLevel::SUPPORT,
            'USER', 'CUSTOMER' => RoleLevel::USER,
            default => RoleLevel::UNKNOWN,
        };
    }
}
