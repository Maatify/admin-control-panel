<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security\Permission;

use Maatify\AdminKernel\Domain\Security\PermissionRequirement;
use Maatify\SharedCommon\Contracts\Security\PermissionRequirementDefinition;

final class SharedPermissionRequirementConverter
{
    public function convert(PermissionRequirementDefinition $definition): PermissionRequirement
    {
        return new PermissionRequirement(
            $definition->anyOf,
            $definition->allOf,
        );
    }
}
