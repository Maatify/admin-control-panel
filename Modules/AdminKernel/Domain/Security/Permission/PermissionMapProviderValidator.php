<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security\Permission;

use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;

final class PermissionMapProviderValidator
{
    /**
     * @param list<PermissionMapProviderInterface> $providers
     */
    public function assertNoDuplicateRoutes(array $providers): void
    {
        $seen = [];

        foreach ($providers as $provider) {
            foreach ($provider->permissionMap() as $routeName => $_definition) {
                if (isset($seen[$routeName])) {
                    throw new \LogicException(sprintf(
                        'Duplicate permission mapping for route "%s" provided by "%s" and "%s".',
                        $routeName,
                        $seen[$routeName],
                        $provider::class,
                    ));
                }

                $seen[$routeName] = $provider::class;
            }
        }
    }
}
