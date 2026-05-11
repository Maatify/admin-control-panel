<?php

declare(strict_types=1);

namespace Maatify\SettingsSlim\Admin\Security;

use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;
use Maatify\SharedCommon\Contracts\Security\PermissionRequirementDefinition;

final readonly class SettingAdminPermissionMapProvider implements PermissionMapProviderInterface
{
    /**
     * @return array<string, PermissionRequirementDefinition>
     */
    public function permissionMap(): array
    {
        return [
            // UI Routes
            'settings.list.ui'      => PermissionRequirementDefinition::single('settings.list'),

            // API Routes
            'settings.list.api'     => PermissionRequirementDefinition::single('settings.list'),
            'settings.dropdown.api' => PermissionRequirementDefinition::single('settings.list'),
            'settings.get.api'      => PermissionRequirementDefinition::single('settings.view'),
            'settings.update.api'   => PermissionRequirementDefinition::single('settings.edit'),
        ];
    }
}
