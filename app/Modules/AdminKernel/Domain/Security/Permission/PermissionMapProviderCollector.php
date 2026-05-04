<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security\Permission;

use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;
use Maatify\SharedCommon\Contracts\Security\ProvidesPermissionMapsInterface;

final class PermissionMapProviderCollector
{
    /**
     * @param list<object> $packages
     * @return list<PermissionMapProviderInterface>
     */
    public function collect(array $packages): array
    {
        $providers = [];

        foreach ($packages as $package) {
            if (!$package instanceof ProvidesPermissionMapsInterface) {
                continue;
            }

            foreach ($package->permissionMapProviders() as $provider) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }
}
