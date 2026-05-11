<?php

declare(strict_types=1);

namespace Maatify\SettingsSlim\Admin\Security;

use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;
use Maatify\SharedCommon\Contracts\Security\ProvidesPermissionMapsInterface;

class SettingAdminPermissionPackage implements ProvidesPermissionMapsInterface
{
    /**
     * @return list<PermissionMapProviderInterface>
     */
    public function permissionMapProviders(): array
    {
        return [
            new SettingAdminPermissionMapProvider(),
        ];
    }
}
