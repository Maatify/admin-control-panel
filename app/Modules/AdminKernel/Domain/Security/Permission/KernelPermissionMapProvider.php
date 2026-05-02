<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security\Permission;

use Maatify\SharedCommon\Contracts\Security\PermissionMapProviderInterface;
use Maatify\SharedCommon\Contracts\Security\PermissionRequirementDefinition;

/**
 * Kernel baseline permission map provider.
 *
 * This class exposes the AdminKernel built-in route-to-permission map using
 * SharedCommon permission definitions so the map can participate in the new
 * composite permission mapper without coupling external modules to AdminKernel.
 */
final class KernelPermissionMapProvider implements PermissionMapProviderInterface
{
    /**
     * @return array<string, PermissionRequirementDefinition>
     */
    public function permissionMap(): array
    {
        return [
            // Admins
            'admins.list.ui'  => PermissionRequirementDefinition::single('admins.list'),
            'admins.list.api' => PermissionRequirementDefinition::single('admins.list'),

            // NOTE:
            // auth.stepup.verify is intentionally NOT mapped.
            // This route is handled as a security step-up flow and bypasses permission mapping.

            // Admin Profile
            'admins.profile.edit' => PermissionRequirementDefinition::single('admins.profile.edit'),

            // Admin Emails
            'admin.email.list.ui'  => PermissionRequirementDefinition::single('admin.email.list'),
            'admin.email.list.api' => PermissionRequirementDefinition::single('admin.email.list'),

            // Admin Create
            'admin.create.ui'  => PermissionRequirementDefinition::single('admin.create'),
            'admin.create.api' => PermissionRequirementDefinition::single('admin.create'),

            // Sessions
            'sessions.list.ui'  => PermissionRequirementDefinition::single('sessions.list'),
            'sessions.list.api' => PermissionRequirementDefinition::single('sessions.list'),

            'sessions.revoke.id'   => PermissionRequirementDefinition::single('sessions.revoke'),
            'sessions.revoke.bulk' => PermissionRequirementDefinition::single('sessions.revoke'),

            // Permissions
            'permissions.query.ui'  => PermissionRequirementDefinition::single('permissions.query'),
            'permissions.query.api' => PermissionRequirementDefinition::single('permissions.query'),

            // Permission details (UI normalization)
            'permission.details.ui' => PermissionRequirementDefinition::single('permission.details'),

            // Roles
            'roles.query.ui'  => PermissionRequirementDefinition::single('roles.query'),
            'roles.query.api' => PermissionRequirementDefinition::single('roles.query'),

            // Roles view normalization
            'roles.view.ui' => PermissionRequirementDefinition::single('roles.view'),

            // Languages
            'languages.list.ui'  => PermissionRequirementDefinition::single('languages.list'),
            'languages.list.api' => PermissionRequirementDefinition::single('languages.list'),

            'languages.clear.fallback.api' => PermissionRequirementDefinition::single('languages.set.fallback'),
            'languages.set.fallback.api'   => PermissionRequirementDefinition::single('languages.set.fallback'),

            'languages.create.api'          => PermissionRequirementDefinition::single('languages.create'),
            'languages.set.active.api'      => PermissionRequirementDefinition::single('languages.set.active'),
            'languages.update.code.api'     => PermissionRequirementDefinition::single('languages.update.code'),
            'languages.update.name.api'     => PermissionRequirementDefinition::single('languages.update.name'),
            'languages.update.settings.api' => PermissionRequirementDefinition::single('languages.update.settings'),
            'languages.update.sort.api'     => PermissionRequirementDefinition::single('languages.update.sort'),

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


            // Image Profiles
            'image_profiles.list.ui'      => PermissionRequirementDefinition::single('image_profiles.list'),
            'image_profiles.list.api'     => PermissionRequirementDefinition::single('image_profiles.list'),
            'image_profiles.details.api'  => PermissionRequirementDefinition::single('image_profiles.details'),
            'image_profiles.create.api'   => PermissionRequirementDefinition::single('image_profiles.create'),
            'image_profiles.update.api'   => PermissionRequirementDefinition::single('image_profiles.update'),
            'image_profiles.set_active.api' => PermissionRequirementDefinition::single('image_profiles.set_active'),
            'image_profiles.dropdown.api' => PermissionRequirementDefinition::single('image_profiles.dropdown'),

            // Website UI Themes
            'website_ui_themes.list.ui'                 => PermissionRequirementDefinition::single('website_ui_themes.list'),
            'website_ui_themes.list.api'                => PermissionRequirementDefinition::single('website_ui_themes.list'),
            'website_ui_themes.details.api'             => PermissionRequirementDefinition::single('website_ui_themes.details'),
            'website_ui_themes.create.api'              => PermissionRequirementDefinition::single('website_ui_themes.create'),
            'website_ui_themes.update.api'              => PermissionRequirementDefinition::single('website_ui_themes.update'),
            'website_ui_themes.delete.api'              => PermissionRequirementDefinition::single('website_ui_themes.delete'),
            'website_ui_themes.dropdown.api'            => PermissionRequirementDefinition::single('website_ui_themes.dropdown'),
            'website_ui_themes.dropdown_by_entity_type.api' => PermissionRequirementDefinition::single('website_ui_themes.dropdown_by_entity_type'),

            // I18n Keys
            // 'i18n.keys.list.ui'  => PermissionRequirementDefinition::single('i18n.keys.list'),
            // 'i18n.keys.list.api' => PermissionRequirementDefinition::single('i18n.keys.list'),

            // I18n Translations
            'languages.translations.list.ui'    => PermissionRequirementDefinition::single('i18n.translations.list'),
            'languages.translations.list.api'   => PermissionRequirementDefinition::single('languages.translations.list'),
            'languages.translations.upsert.api' => PermissionRequirementDefinition::single('languages.translations.upsert'),
            'languages.translations.delete.api' => PermissionRequirementDefinition::single('languages.translations.delete'),

            'i18n.scopes.domains.keys.query.api' => PermissionRequirementDefinition::single('i18n.scopes.domains.keys'),
            'i18n.scopes.domains.keys.ui'        => PermissionRequirementDefinition::single('i18n.scopes.domains.keys'),

            'i18n.scopes.coverage.language.api' => PermissionRequirementDefinition::single('i18n.scopes.details'),

            'i18n.scopes.coverage.domain.ui'  => PermissionRequirementDefinition::single('i18n.scopes.coverage.domain'),
            'i18n.scopes.coverage.domain.api' => PermissionRequirementDefinition::single('i18n.scopes.coverage.domain'),

            'i18n.scopes.domains.translations.query.api' => PermissionRequirementDefinition::single('i18n.scopes.domains.translations'),
            'i18n.scopes.domains.translations.ui'        => PermissionRequirementDefinition::single('i18n.scopes.domains.translations'),

            // I18n Scopes Control
            'i18n.scopes.dropdown.api'        => PermissionRequirementDefinition::single('i18n.scopes.dropdown'),
            'i18n.scopes.list.ui'             => PermissionRequirementDefinition::single('i18n.scopes.list'),
            'i18n.scopes.list.api'            => PermissionRequirementDefinition::single('i18n.scopes.list'),
            'i18n.scopes.create.api'          => PermissionRequirementDefinition::single('i18n.scopes.create'),
            'i18n.scopes.change_code.api'     => PermissionRequirementDefinition::single('i18n.scopes.change_code'),
            'i18n.scopes.set_active.api'      => PermissionRequirementDefinition::single('i18n.scopes.set_active'),
            'i18n.scopes.update_sort.api'     => PermissionRequirementDefinition::single('i18n.scopes.update_sort'),
            'i18n.scopes.update_metadata.api' => PermissionRequirementDefinition::single('i18n.scopes.update_metadata'),

            'i18n.scopes.details.ui'          => PermissionRequirementDefinition::single('i18n.scopes.details'),
            'i18n.scopes.domains.query.api'   => PermissionRequirementDefinition::single('i18n.scopes.details'),
            'i18n.scopes.domains.assign.api'  => PermissionRequirementDefinition::single('i18n.scopes.domains.assign'),
            'i18n.scopes.domains.unassign.api'=> PermissionRequirementDefinition::single('i18n.scopes.domains.unassign'),

            // I18n Domains Control
            'i18n.domains.list.ui'             => PermissionRequirementDefinition::single('i18n.domains.list'),
            'i18n.domains.list.api'            => PermissionRequirementDefinition::single('i18n.domains.list'),
            'i18n.domains.create.api'          => PermissionRequirementDefinition::single('i18n.domains.create'),
            'i18n.domains.change_code.api'     => PermissionRequirementDefinition::single('i18n.domains.change_code'),
            'i18n.domains.set_active.api'      => PermissionRequirementDefinition::single('i18n.domains.set_active'),
            'i18n.domains.update_sort.api'     => PermissionRequirementDefinition::single('i18n.domains.update_sort'),
            'i18n.domains.update_metadata.api' => PermissionRequirementDefinition::single('i18n.domains.update_metadata'),

            // I18n Translations Keys Control
            'i18n.scopes.keys.ui'              => PermissionRequirementDefinition::single('i18n.scopes.keys'),
            'i18n.scopes.keys.query.api'       => PermissionRequirementDefinition::single('i18n.scopes.keys'),
            'i18n.scopes.keys.update_name.api' => PermissionRequirementDefinition::single('i18n.scopes.keys.update_name'),
            'i18n.scopes.keys.create.api'      => PermissionRequirementDefinition::single('i18n.scopes.keys.create'),
            'i18n.scopes.keys.update_metadata.api' => PermissionRequirementDefinition::single('i18n.scopes.keys.update_metadata'),

            // App Settings Control
            'app_settings.list.api' => PermissionRequirementDefinition::single('app_settings.list'),

            // App Settings UI
            'app_settings.list.ui' => PermissionRequirementDefinition::single('app_settings.list'),

            'app_settings.create.api'   => PermissionRequirementDefinition::single('app_settings.create'),
            'app_settings.metadata.api' => PermissionRequirementDefinition::single('app_settings.create'),

            'app_settings.update.api' => PermissionRequirementDefinition::single('app_settings.update'),

            'app_settings.set_active.api' => PermissionRequirementDefinition::single('app_settings.set_active'),

            // Content Documents Control
            'content_documents.types.query.ui'  => PermissionRequirementDefinition::single('content_documents.types.query'),
            'content_documents.types.query.api' => PermissionRequirementDefinition::single('content_documents.types.query'),
            'content_documents.types.create.api'=> PermissionRequirementDefinition::single('content_documents.types.create'),
            'content_documents.types.update.api'=> PermissionRequirementDefinition::single('content_documents.types.update'),

            'content_documents.versions.query.ui'        => PermissionRequirementDefinition::single('content_documents.versions.query'),
            'content_documents.versions.query.api'       => PermissionRequirementDefinition::single('content_documents.versions.query'),
            'content_documents.versions.create.api'      => PermissionRequirementDefinition::single('content_documents.versions.create'),
            'content_documents.versions.activate.api'    => PermissionRequirementDefinition::single('content_documents.versions.activate'),
            'content_documents.versions.deactivate.api'  => PermissionRequirementDefinition::single('content_documents.versions.deactivate'),
            'content_documents.versions.archive.api'     => PermissionRequirementDefinition::single('content_documents.versions.archive'),
            'content_documents.versions.publish.api'     => PermissionRequirementDefinition::single('content_documents.versions.publish'),

            'content_documents.translations.query.ui'  => PermissionRequirementDefinition::single('content_documents.translations.query'),
            'content_documents.translations.query.api' => PermissionRequirementDefinition::single('content_documents.translations.query'),

            // 'content_documents.translations.details' => PermissionRequirementDefinition::single('content_documents.translations.details'),

            'content_documents.translations.upsert.api' => PermissionRequirementDefinition::single('content_documents.translations.upsert'),

            'content_documents.acceptance.query.api' => PermissionRequirementDefinition::single('content_documents.acceptance.query'),
            'content_documents.acceptance.query.ui'  => PermissionRequirementDefinition::single('content_documents.acceptance.query'),

            /**
             * Shared selector:
             * - allowed from translations UI (upsert permission implies ability to select context)
             * - allowed from languages context
             */
            'i18n.languages.dropdown.api' => PermissionRequirementDefinition::compound(
                anyOf: [
                    'i18n.translations.upsert',
                    'i18n.languages.dropdown',
                ],
            ),

            'content_documents.types.dropdown.api' => PermissionRequirementDefinition::compound(
                anyOf: [
                    'content_documents.types.dropdown',
                    'content_documents.types.query',
                ],
            ),

            // I18n Translations Keys Dropdown for create
            'i18n.scopes.domains.dropdown.api' => PermissionRequirementDefinition::compound(
                anyOf: [
                    'i18n.scopes.keys.create',
                    'i18n.scopes.domains.dropdown',
                ],
            ),
        ];
    }
}
