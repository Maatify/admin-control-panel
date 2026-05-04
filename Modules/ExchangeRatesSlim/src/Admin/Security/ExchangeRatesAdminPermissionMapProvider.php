<?php

declare(strict_types=1);

namespace Maatify\ExchangeRatesSlim\Admin\Security;

use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;
use Maatify\SharedCommon\Contracts\Security\PermissionRequirementDefinition;

final readonly class ExchangeRatesAdminPermissionMapProvider implements PermissionMapProviderInterface
{
    /**
     * @return array<string, PermissionRequirementDefinition>
     */
    public function permissionMap(): array
    {
        return [

            // Exchange Rates
            'exchange_rates.providers.list.ui'        => PermissionRequirementDefinition::single('exchange_rates.providers.list'),
            'exchange_rates.providers.list.api'       => PermissionRequirementDefinition::single('exchange_rates.providers.list'),
            'exchange_rates.providers.dropdown.api'   => PermissionRequirementDefinition::single('exchange_rates.providers.list'),
            'exchange_rates.providers.create.api'     => PermissionRequirementDefinition::single('exchange_rates.providers.create'),
            'exchange_rates.providers.update.api'     => PermissionRequirementDefinition::single('exchange_rates.providers.update'),
            'exchange_rates.providers.set_active.api' => PermissionRequirementDefinition::single('exchange_rates.providers.set_active'),
            'exchange_rates.providers.update_sort.api'=> PermissionRequirementDefinition::single('exchange_rates.providers.update_sort'),
            'exchange_rates.providers.delete.api'     => PermissionRequirementDefinition::single('exchange_rates.providers.delete'),

            'exchange_rates.rates.list.ui'         => PermissionRequirementDefinition::single('exchange_rates.rates.list'),
            'exchange_rates.rates.list.api'        => PermissionRequirementDefinition::single('exchange_rates.rates.list'),
            'exchange_rates.rates.create.api'      => PermissionRequirementDefinition::single('exchange_rates.rates.create'),
            'exchange_rates.rates.update.api'      => PermissionRequirementDefinition::single('exchange_rates.rates.update'),
            'exchange_rates.rates.set_active.api'  => PermissionRequirementDefinition::single('exchange_rates.rates.set_active'),
            'exchange_rates.rates.update_sort.api' => PermissionRequirementDefinition::single('exchange_rates.rates.update_sort'),
            'exchange_rates.rates.delete.api'      => PermissionRequirementDefinition::single('exchange_rates.rates.delete'),
            'exchange_rates.rates.history.api'     => PermissionRequirementDefinition::single('exchange_rates.rates.history'),
            'exchange_rates.rates.history.list.ui'     => PermissionRequirementDefinition::single('exchange_rates.rates.history'),

        ];
    }
}
