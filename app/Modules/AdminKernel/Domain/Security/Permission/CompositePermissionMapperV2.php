<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security\Permission;

use Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionMapperV2Interface;
use Maatify\AdminKernel\Domain\Security\PermissionRequirement;
use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;

final readonly class CompositePermissionMapperV2 implements PermissionMapperV2Interface
{
    /**
     * @param list<PermissionMapProviderInterface> $providers
     */
    public function __construct(
        private array $providers,
        private SharedPermissionRequirementConverter $converter = new SharedPermissionRequirementConverter(),
    ) {}

    public function resolve(string $routeName): PermissionRequirement
    {
        foreach ($this->providers as $provider) {
            $map = $provider->permissionMap();

            if (!array_key_exists($routeName, $map)) {
                continue;
            }

            return $this->converter->convert($map[$routeName]);
        }

        return PermissionRequirement::single($routeName);
    }
}
