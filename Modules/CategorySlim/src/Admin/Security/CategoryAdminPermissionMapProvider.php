<?php

declare(strict_types=1);

namespace Maatify\CategorySlim\Admin\Security;

use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;
use Maatify\SharedCommon\Contracts\Security\PermissionRequirementDefinition;

final readonly class CategoryAdminPermissionMapProvider implements PermissionMapProviderInterface
{
    /**
     * @return array<string, PermissionRequirementDefinition>
     */
    public function permissionMap(): array
    {
        return [

            // Categories — list
            'categories.list.ui'  => PermissionRequirementDefinition::single('categories.list'),
            'categories.list.api' => PermissionRequirementDefinition::single('categories.list'),

            // Categories — detail page (UI)
            'categories.detail.ui' => PermissionRequirementDefinition::single('categories.list'),

            // Categories — mutations
            'categories.create.api'      => PermissionRequirementDefinition::single('categories.create'),
            'categories.update.api'      => PermissionRequirementDefinition::single('categories.update'),
            'categories.set_active.api'  => PermissionRequirementDefinition::single('categories.set_active'),
            'categories.update_sort.api' => PermissionRequirementDefinition::single('categories.update_sort'),
            'categories.dropdown.api'    => PermissionRequirementDefinition::single('categories.list'),

            // Sub-categories
            'categories.sub_categories.list.ui'      => PermissionRequirementDefinition::single('categories.sub_categories.list'),
            'categories.sub_categories.list.api'     => PermissionRequirementDefinition::single('categories.sub_categories.list'),
            'categories.sub_categories.dropdown.api' => PermissionRequirementDefinition::single('categories.sub_categories.list'),

            // Category settings
            'categories.settings.list.api'   => PermissionRequirementDefinition::single('categories.settings.list'),
            'categories.settings.upsert.api' => PermissionRequirementDefinition::single('categories.settings.upsert'),
            'categories.settings.delete.api' => PermissionRequirementDefinition::single('categories.settings.delete'),

            // Category images
            'categories.images.list.ui'    => PermissionRequirementDefinition::single('categories.images.list'),
            'categories.images.list.api'   => PermissionRequirementDefinition::single('categories.images.list'),
            'categories.images.upsert.api' => PermissionRequirementDefinition::single('categories.images.upsert'),
            'categories.images.delete.api' => PermissionRequirementDefinition::single('categories.images.delete'),

            // Category translations
            'categories.translations.list.ui'    => PermissionRequirementDefinition::single('categories.translations.list'),
            'categories.translations.list.api'   => PermissionRequirementDefinition::single('categories.translations.list'),
            'categories.translations.upsert.api' => PermissionRequirementDefinition::single('categories.translations.upsert'),
            'categories.translations.delete.api' => PermissionRequirementDefinition::single('categories.translations.delete'),

        ];
    }
}

