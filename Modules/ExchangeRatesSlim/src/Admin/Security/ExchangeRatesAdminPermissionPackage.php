<?php

declare(strict_types=1);

namespace Maatify\ExchangeRatesSlim\Admin\Security;

use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;
use Maatify\SharedCommon\Contracts\Security\ProvidesPermissionMapsInterface;

final readonly class ExchangeRatesAdminPermissionPackage implements ProvidesPermissionMapsInterface
{
    /**
     * @return list<PermissionMapProviderInterface>
     */
    public function permissionMapProviders(): array
    {
        return [
            new ExchangeRatesAdminPermissionMapProvider(),
        ];
    }
}
