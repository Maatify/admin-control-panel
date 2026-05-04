<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security\Permission;

use Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionMapperV2Interface;
use Maatify\AdminKernel\Domain\Security\PermissionRequirement;
use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;

final readonly class SharedPermissionMapProviderMapper implements PermissionMapperV2Interface
{
    public function __construct(
        private PermissionMapProviderInterface $provider,
        private SharedPermissionRequirementConverter $converter = new SharedPermissionRequirementConverter(),
    ) {}

    public function resolve(string $routeName): PermissionRequirement
    {
        $map = $this->provider->permissionMap();

        if (!array_key_exists($routeName, $map)) {
            return PermissionRequirement::single($routeName);
        }

        return $this->converter->convert($map[$routeName]);
    }
}
