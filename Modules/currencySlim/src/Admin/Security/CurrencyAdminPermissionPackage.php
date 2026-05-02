<?php

declare(strict_types=1);

namespace Maatify\currencySlim\Admin\Security;

use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;
use Maatify\SharedCommon\Contracts\Security\ProvidesPermissionMapsInterface;

class CurrencyAdminPermissionPackage implements ProvidesPermissionMapsInterface
{
    /**
     * @return list<PermissionMapProviderInterface>
     */
    public function permissionMapProviders(): array
    {
        return [
            new CurrencyAdminPermissionMapProvider(),
        ];
    }
}
