<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-02-04 18:50
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Security;

use Maatify\AdminKernel\Domain\Contracts\Permissions\PermissionMapperV2Interface;

/**
 * V2 Permission Mapper (Hierarchy-aware shape, OR/AND ready)
 *
 * - Backward compatible behavior: unknown routes map to themselves
 * - Supports:
 *   - single permission
 *   - anyOf (OR)
 *   - allOf (AND)
 *
 * NOTE:
 * PHP does not allow objects in class constants, so MAP values must be arrays/strings only.
 */
final class PermissionMapperV2 implements PermissionMapperV2Interface
{
    /**
     * Map Shapes:
     * 1) string => single permission
     * 2) ['anyOf' => list<string>] => OR permissions
     * 3) ['allOf' => list<string>] => AND permissions
     *
     * @var array<string, string|array<string, list<string>>>
     */
    private const MAP = [
        // Admins
        'admins.list.ui'  => 'admins.list',
        'admins.list.api' => 'admins.list',

        // Admin Profile
        'admins.profile.edit.view' => 'admins.profile.edit',
        'admins.profile.edit'      => 'admins.profile.edit',

        // Admin Emails
        'admin.email.list.ui'  => 'admin.email.list',
        'admin.email.list.api' => 'admin.email.list',

        // Admin Create
        'admin.create.ui'  => 'admin.create',
        'admin.create.api' => 'admin.create',

        // Sessions
        'sessions.list.ui'  => 'sessions.list',
        'sessions.list.api' => 'sessions.list',

        'sessions.revoke.id'   => 'sessions.revoke',
        'sessions.revoke.bulk' => 'sessions.revoke',

        // Languages
        'languages.list.ui'  => 'languages.list',
        'languages.list.api' => 'languages.list',

        'languages.clear.fallback.api' => 'languages.set.fallback',

        // I18n Keys
        'i18n.keys.list.ui'  => 'i18n.keys.list',
        'i18n.keys.list.api' => 'i18n.keys.list',

        // I18n Translations
        'languages.translations.list.ui'  => 'i18n.translations.list',
        'languages.translations.list.api' => 'languages.translations.list',
        'languages.translations.upsert.api' => 'languages.translations.upsert',
        'languages.translations.delete.api' => 'languages.translations.delete',

        'i18n.scopes.domains.keys.query.api' => 'i18n.scopes.domains.keys',
        'i18n.scopes.domains.keys.ui' => 'i18n.scopes.domains.keys',

        'i18n.scopes.coverage.language.api' => 'i18n.scopes.details',

        'i18n.scopes.coverage.domain.ui' => 'i18n.scopes.coverage.domain',
        'i18n.scopes.coverage.domain.api' => 'i18n.scopes.coverage.domain',

        'i18n.scopes.domains.translations.query.api' => 'i18n.scopes.domains.translations',
        'i18n.scopes.domains.translations.ui' => 'i18n.scopes.domains.translations',

        // I18n Scopes Control
        'i18n.scopes.dropdown.api' => 'i18n.scopes.dropdown',
        'i18n.scopes.list.ui' => 'i18n.scopes.list',
        'i18n.scopes.list.api' => 'i18n.scopes.list',
        'i18n.scopes.create.api' => 'i18n.scopes.create',
        'i18n.scopes.change_code.api' => 'i18n.scopes.change_code',
        'i18n.scopes.set_active.api' => 'i18n.scopes.set_active',
        'i18n.scopes.update_sort.api' => 'i18n.scopes.update_sort',
        'i18n.scopes.update_metadata.api' => 'i18n.scopes.update_metadata',

        'i18n.scopes.details.ui' => 'i18n.scopes.details',
        'i18n.scopes.domains.query.api' => 'i18n.scopes.details',
        'i18n.scopes.domains.assign.api' => 'i18n.scopes.domains.assign',
        'i18n.scopes.domains.unassign.api' => 'i18n.scopes.domains.unassign',

        // I18n Domains Control
        'i18n.domains.list.ui' => 'i18n.domains.list',
        'i18n.domains.list.api' => 'i18n.domains.list',
        'i18n.domains.create.api' => 'i18n.domains.create',
        'i18n.domains.change_code.api' => 'i18n.domains.change_code',
        'i18n.domains.set_active.api' => 'i18n.domains.set_active',
        'i18n.domains.update_sort.api' => 'i18n.domains.update_sort',
        'i18n.domains.update_metadata.api' => 'i18n.domains.update_metadata',

        // I18n Translations Keys Control
        'i18n.scopes.keys.ui' => 'i18n.scopes.keys',
        'i18n.scopes.keys.query.api' => 'i18n.scopes.keys',
        'i18n.scopes.keys.update_name.api' => 'i18n.scopes.keys.update_name',
        'i18n.scopes.keys.create.api' => 'i18n.scopes.keys.create',
        'i18n.scopes.keys.update_metadata.api' => 'i18n.scopes.keys.update_metadata',

        // App Settings Control
        'app_settings.list.api' => 'app_settings.list',
        // App Settings UI
        'app_settings.list.ui' => 'app_settings.list',

        'app_settings.create.api' => 'app_settings.create',
        'app_settings.metadata.api' => 'app_settings.create',

        'app_settings.update.api' => 'app_settings.update',

        'app_settings.set_active.api' => 'app_settings.set_active',

        /**
         * Shared selector:
         * - allowed from translations UI (upsert permission implies ability to select context)
         * - allowed from languages context
         */
        'i18n.languages.dropdown.api' => [
            'anyOf' => [
                'i18n.translations.upsert',
                'i18n.languages.dropdown',
            ],
            'allOf' => [],
        ],

        // I18n Translations Keys Dropdown for create
        'i18n.scopes.domains.dropdown.api' => [
            'anyOf' => [
                'i18n.scopes.keys.create',
                'i18n.scopes.domains.dropdown',
            ],
            'allOf' => [],
        ],
    ];

    public function resolve(string $routeName): PermissionRequirement
    {
        $mapped = self::MAP[$routeName] ?? $routeName;

        if (is_string($mapped)) {
            return PermissionRequirement::single($mapped);
        }

        /** @var list<string> $anyOf */
        $anyOf = $mapped['anyOf'];

        /** @var list<string> $allOf */
        $allOf = $mapped['allOf'];

        if ($anyOf === [] && $allOf === []) {
            return PermissionRequirement::single($routeName);
        }

        return new PermissionRequirement($anyOf, $allOf);
    }

}
