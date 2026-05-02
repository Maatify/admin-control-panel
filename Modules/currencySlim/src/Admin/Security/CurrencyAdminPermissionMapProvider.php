<?php

declare(strict_types=1);

namespace Maatify\currencySlim\Admin\Security;

use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;
use Maatify\SharedCommon\Contracts\Security\PermissionRequirementDefinition;

final readonly class CurrencyAdminPermissionMapProvider implements PermissionMapProviderInterface
{
    /**
     * @return array<string, PermissionRequirementDefinition>
     */
    public function permissionMap(): array
    {
        return [


            // Currencies
            'currencies.list.ui'         => PermissionRequirementDefinition::single('currencies.list'),
            'currencies.list.api'        => PermissionRequirementDefinition::single('currencies.list'),
            'currencies.create.api'      => PermissionRequirementDefinition::single('currencies.create'),
            'currencies.update.api'      => PermissionRequirementDefinition::single('currencies.update'),
            'currencies.set_active.api'  => PermissionRequirementDefinition::single('currencies.set_active'),
            'currencies.update_sort.api' => PermissionRequirementDefinition::single('currencies.update_sort'),
            'currencies.dropdown.api'    => PermissionRequirementDefinition::single('currencies.dropdown'),

            'currencies.translations.list.ui'    => PermissionRequirementDefinition::single('currencies.translations.list'),
            'currencies.translations.list.api'   => PermissionRequirementDefinition::single('currencies.translations.list'),
            'currencies.translations.upsert.api' => PermissionRequirementDefinition::single('currencies.translations.upsert'),
            'currencies.translations.delete.api' => PermissionRequirementDefinition::single('currencies.translations.delete'),

        ];
    }
}
