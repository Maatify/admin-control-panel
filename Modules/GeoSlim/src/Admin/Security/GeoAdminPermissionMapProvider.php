<?php

declare(strict_types=1);

namespace Maatify\GeoSlim\Admin\Security;

use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;
use Maatify\SharedCommon\Contracts\Security\PermissionRequirementDefinition;

final readonly class GeoAdminPermissionMapProvider implements PermissionMapProviderInterface
{
    /**
     * @return array<string, PermissionRequirementDefinition>
     */
    public function permissionMap(): array
    {
        return [

            // Countries
            'geo.countries.list.ui'          => PermissionRequirementDefinition::single('geo.countries.list'),
            'geo.countries.list.api'         => PermissionRequirementDefinition::single('geo.countries.list'),
            'geo.countries.dropdown.api'     => PermissionRequirementDefinition::single('geo.countries.list'),
            'geo.countries.create.api'       => PermissionRequirementDefinition::single('geo.countries.create'),
            'geo.countries.update.api'       => PermissionRequirementDefinition::single('geo.countries.update'),
            'geo.countries.set_active.api'   => PermissionRequirementDefinition::single('geo.countries.set_active'),
            'geo.countries.update_sort.api'  => PermissionRequirementDefinition::single('geo.countries.update_sort'),

            'geo.countries.translations.list.ui'    => PermissionRequirementDefinition::single('geo.countries.translations.list'),
            'geo.countries.translations.list.api'   => PermissionRequirementDefinition::single('geo.countries.translations.list'),
            'geo.countries.translations.upsert.api' => PermissionRequirementDefinition::single('geo.countries.translations.upsert'),
            'geo.countries.translations.delete.api' => PermissionRequirementDefinition::single('geo.countries.translations.delete'),

            // Cities
            'geo.cities.list.ui'          => PermissionRequirementDefinition::single('geo.cities.list'),
            'geo.cities.list.api'         => PermissionRequirementDefinition::single('geo.cities.list'),
            'geo.cities.dropdown.api'     => PermissionRequirementDefinition::single('geo.cities.list'),
            'geo.cities.create.api'       => PermissionRequirementDefinition::single('geo.cities.create'),
            'geo.cities.update.api'       => PermissionRequirementDefinition::single('geo.cities.update'),
            'geo.cities.set_active.api'   => PermissionRequirementDefinition::single('geo.cities.set_active'),
            'geo.cities.update_sort.api'  => PermissionRequirementDefinition::single('geo.cities.update_sort'),

            'geo.cities.translations.list.ui'    => PermissionRequirementDefinition::single('geo.cities.translations.list'),
            'geo.cities.translations.list.api'   => PermissionRequirementDefinition::single('geo.cities.translations.list'),
            'geo.cities.translations.upsert.api' => PermissionRequirementDefinition::single('geo.cities.translations.upsert'),
            'geo.cities.translations.delete.api' => PermissionRequirementDefinition::single('geo.cities.translations.delete'),

        ];
    }
}

