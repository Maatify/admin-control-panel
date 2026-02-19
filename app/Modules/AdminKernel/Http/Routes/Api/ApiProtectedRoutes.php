<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Http\Routes\Api;

use Maatify\AdminKernel\Http\Controllers\AdminNotificationPreferenceController;
use Maatify\AdminKernel\Http\Controllers\Api\Admin\AdminController;
use Maatify\AdminKernel\Http\Controllers\Api\Admin\AdminEmailVerificationController;
use Maatify\AdminKernel\Http\Controllers\Api\Admin\AdminQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Keys\I18nScopeKeysCreateController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Keys\I18nScopeKeysUpdateDescriptionController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Keys\I18nScopeKeysUpdateNameController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesClearFallbackController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesCreateController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesQueryController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesSetActiveController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesSetFallbackController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesUpdateCodeController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesUpdateNameController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesUpdateSettingsController;
use Maatify\AdminKernel\Http\Controllers\Api\I18n\Languages\LanguagesUpdateSortOrderController;
use Maatify\AdminKernel\Http\Controllers\NotificationQueryController;
use Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware;
use Maatify\AdminKernel\Http\Middleware\SessionGuardMiddleware;
use Maatify\LanguageCore\Http\Controllers\Api\LanguageDropdownController;
use Slim\Interfaces\RouteCollectorProxyInterface;

final class ApiProtectedRoutes
{
    public static function register(RouteCollectorProxyInterface $api): void
    {
        $api->group('', function (RouteCollectorProxyInterface $group) {

            // Phase 14.3: Sessions Query
            $group->group('/sessions', function (RouteCollectorProxyInterface $sessions) {
                $sessions->post('/query', [\Maatify\AdminKernel\Http\Controllers\Api\Sessions\SessionQueryController::class, '__invoke'])
                    ->setName('sessions.list.api');

                $sessions->delete('/{session_id}', [\Maatify\AdminKernel\Http\Controllers\Api\Sessions\SessionRevokeController::class, '__invoke'])
                    ->setName('sessions.revoke.id');

                $sessions->post('/revoke-bulk', [\Maatify\AdminKernel\Http\Controllers\Api\Sessions\SessionBulkRevokeController::class, '__invoke'])
                    ->setName('sessions.revoke.bulk');
            });

            // ─────────────────────────────
            // Content Documents Control
            // ─────────────────────────────
            $group->group('/content-document-types', function (RouteCollectorProxyInterface $documents) {
                // Dropdown (available enum keys)
                $documents->get(
                    '/dropdown',
                    \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentsKeysDropdownController::class
                )->setName('content_documents.types.dropdown.api');

                // Query
                $documents->post(
                    '/query',
                    \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentTypesQueryController::class
                )->setName('content_documents.types.query.api');

                // Create
                $documents->post(
                    '/create',
                    \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentTypeCreateController::class
                )->setName('content_documents.types.create.api');

                // Update
                $documents->post(
                    '/{type_id:[0-9]+}/update',
                    \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentTypeUpdateController::class
                )->setName('content_documents.types.update.api');

                $documents->group(
                    '/{type_id:[0-9]+}/documents',
                    function (RouteCollectorProxyInterface $versions) {

                        $versions->post(
                            '/query',
                            \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentVersionsQueryController::class
                        )->setName('content_documents.versions.query.api');

                        $versions->post(
                            '/create',
                            \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentVersionCreateController::class
                        )->setName('content_documents.versions.create.api');

                        $versions->group(
                            '/{document_id:[0-9]+}',
                            function (RouteCollectorProxyInterface $document) {

                                $document->post(
                                    '/activate',
                                    \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentActivateController::class
                                )->setName('content_documents.versions.activate.api');

                                $document->post(
                                    '/archive',
                                    \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentArchiveController::class
                                )->setName('content_documents.versions.archive.api');

                                $document->group(
                                    '/translations',
                                    function (RouteCollectorProxyInterface $translations) {

                                        $translations->post(
                                            '/query',
                                            \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentTranslationsQueryController::class
                                        )->setName('content_documents.translations.query.api');

                                        $translations->post(
                                            '/upsert',
                                            \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentTranslationUpsertController::class
                                        )->setName('content_documents.translations.upsert.api');
                                    }
                                );

                                $document->group(
                                    '/acceptance',
                                    function (RouteCollectorProxyInterface $acceptance) {

                                        $acceptance->post(
                                            '/query',
                                            \Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\DocumentAcceptanceQueryController::class
                                        )->setName('content_documents.acceptance.query.api');
                                    }
                                );
                            }
                        );

                    }
                );
            });

            // ─────────────────────────────
            // i18n Root Control
            // ─────────────────────────────
            $group->group('/i18n', function (RouteCollectorProxyInterface $i18n) {

                // ─────────────────────────────
                // i18n Scope Control
                // ─────────────────────────────
                $i18n->group('/scopes', function (RouteCollectorProxyInterface $i18nScopes) {
                    $i18nScopes->post(
                        '/dropdown',
                        [
                            \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopesDropdownController::class,
                            '__invoke'
                        ]
                    )
                        ->setName('i18n.scopes.dropdown.api');

                    $i18nScopes->post(
                        '/query',
                        [
                            \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopesQueryController::class,
                            '__invoke'
                        ]
                    )
                        ->setName('i18n.scopes.list.api');

                    $i18nScopes->post(
                        '/create',
                        [
                            \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopeCreateController::class,
                            '__invoke'
                        ]
                    )->setName('i18n.scopes.create.api');

                    $i18nScopes->post(
                        '/change-code',
                        [
                            \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopeChangeCodeController::class,
                            '__invoke'
                        ]
                    )->setName('i18n.scopes.change_code.api');

                    $i18nScopes->post(
                        '/set-active',
                        [
                            \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopeSetActiveController::class,
                            '__invoke'
                        ]
                    )->setName('i18n.scopes.set_active.api');

                    $i18nScopes->post(
                        '/update-sort',
                        [
                            \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopeUpdateSortController::class,
                            '__invoke'
                        ]
                    )->setName('i18n.scopes.update_sort.api');

                    $i18nScopes->post(
                        '/update-metadata',
                        [
                            \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopeUpdateMetadataController::class,
                            '__invoke'
                        ]
                    )->setName('i18n.scopes.update_metadata.api');

                    $i18nScopes->post(
                        '/{scope_id:[0-9]+}/domains/query',
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\I18nScopeDomainsQueryController::class
                    )->setName('i18n.scopes.domains.query.api');

                    $i18nScopes->post(
                        '/{scope_id:[0-9]+}/domains/assign',
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\I18nScopeDomainAssignController::class
                    )->setName('i18n.scopes.domains.assign.api');

                    $i18nScopes->post(
                        '/{scope_id:[0-9]+}/domains/unassign',
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\I18nScopeDomainUnassignController::class
                    )->setName('i18n.scopes.domains.unassign.api');

                    $i18nScopes->get(
                        '/{scope_id:[0-9]+}/domains/dropdown',
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\I18nScopeDomainsDropdownController::class
                    )->setName('i18n.scopes.domains.dropdown.api');

                    $i18nScopes->post(
                        '/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/keys/query',
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\I18nScopeDomainKeysSummaryQueryController::class
                    )->setName('i18n.scopes.domains.keys.query.api');

                    $i18nScopes->post(
                        '/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/translations/query',
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\ScopeDomainTranslationsQueryController::class
                    )->setName('i18n.scopes.domains.translations.query.api');

                    $i18nScopes->get(
                        '/{scope_id:[0-9]+}/coverage',
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Coverage\I18nScopeCoverageByLanguageController::class
                    )->setName('i18n.scopes.coverage.language.api');

                    $i18nScopes->get(
                        '/{scope_id:[0-9]+}/coverage/languages/{language_id:[0-9]+}',
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Coverage\I18nScopeCoverageByDomainController::class
                    )->setName('i18n.scopes.coverage.domain.api');

                    // ─────────────────────────────
                    // i18n Keys Control
                    // ─────────────────────────────
                    $i18nScopes->post(
                        '/{scope_id:[0-9]+}/keys/query',
                        \Maatify\AdminKernel\Http\Controllers\Api\I18n\Keys\I18nScopeKeysQueryController::class
                    )->setName('i18n.scopes.keys.query.api');

                    $i18nScopes->post('/{scope_id:[0-9]+}/keys/update-name', [I18nScopeKeysUpdateNameController::class, '__invoke'])
                        ->setName('i18n.scopes.keys.update_name.api');

                    $i18nScopes->post('/{scope_id:[0-9]+}/keys/create', [I18nScopeKeysCreateController::class, '__invoke'])
                        ->setName('i18n.scopes.keys.create.api');

                    $i18nScopes->post('/{scope_id:[0-9]+}/keys/update_metadata', [I18nScopeKeysUpdateDescriptionController::class, '__invoke'])
                        ->setName('i18n.scopes.keys.update_metadata.api');
                });

                // ─────────────────────────────
                // i18n Domains Control
                // ─────────────────────────────
                $i18n->group('/domains', function (RouteCollectorProxyInterface $i18nDomains) {
                    $i18nDomains->post(
                        '/query',
                        [
                            \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nDomainsQueryController::class,
                            '__invoke'
                        ]
                    )
                        ->setName('i18n.domains.list.api');

                    $i18nDomains->post(
                        '/create',
                        [
                            \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nDomainCreateController::class,
                            '__invoke'
                        ]
                    )->setName('i18n.domains.create.api');

                    $i18nDomains->post(
                        '/change-code',
                        [
                            \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nDomainChangeCodeController::class,
                            '__invoke'
                        ]
                    )->setName('i18n.domains.change_code.api');

                    $i18nDomains->post(
                        '/set-active',
                        [
                            \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nDomainSetActiveController::class,
                            '__invoke'
                        ]
                    )->setName('i18n.domains.set_active.api');

                    $i18nDomains->post(
                        '/update-sort',
                        [
                            \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nScopeDomainSortController::class,
                            '__invoke'
                        ]
                    )->setName('i18n.domains.update_sort.api');

                    $i18nDomains->post(
                        '/update-metadata',
                        [
                            \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nDomainUpdateMetadataController::class,
                            '__invoke'
                        ]
                    )->setName('i18n.domains.update_metadata.api');
                });
            });

            // ─────────────────────────────
            // App Settings Control
            // ─────────────────────────────
            $group->group('/app-settings', function (RouteCollectorProxyInterface $appSettings) {
                $appSettings->post(
                    '/query',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\AppSettings\AppSettingsQueryController::class,
                        '__invoke'
                    ]
                )
                    ->setName('app_settings.list.api');

                $appSettings->post(
                    '/create',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\AppSettings\AppSettingsCreateController::class,
                        '__invoke'
                    ]
                )->setName('app_settings.create.api');

                $appSettings->post(
                    '/metadata',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\AppSettings\AppSettingsMetadataController::class,
                        '__invoke'
                    ]
                )->setName('app_settings.metadata.api');

                $appSettings->post(
                    '/update',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\AppSettings\AppSettingsUpdateController::class,
                        '__invoke'
                    ]
                )->setName('app_settings.update.api');

                $appSettings->post(
                    '/set-active',
                    [
                        \Maatify\AdminKernel\Http\Controllers\Api\AppSettings\AppSettingsSetActiveController::class,
                        '__invoke'
                    ]
                )->setName('app_settings.set_active.api');

            });

            // ─────────────────────────────
            // languages Control
            // ─────────────────────────────
            $group->group('/languages', function (RouteCollectorProxyInterface $languages) {

                /**
                 * UI context selector (dropdown)
                 * Permission: i18n.languages.dropdown.api
                 * (mapped via PermissionMapperV2 anyOf)
                 */
                $languages->post('/dropdown', LanguageDropdownController::class)
                    ->setName('i18n.languages.dropdown.api');

                $languages->post('/query', [LanguagesQueryController::class, '__invoke'])
                    ->setName('languages.list.api');

                $languages->post('/create', [LanguagesCreateController::class, '__invoke'])
                    ->setName('languages.create.api');

                $languages->post('/update-settings', [LanguagesUpdateSettingsController::class, '__invoke'])
                    ->setName('languages.update.settings.api');

                $languages->post('/set-active', [LanguagesSetActiveController::class, '__invoke'])
                    ->setName('languages.set.active.api');

                $languages->post('/set-fallback', [LanguagesSetFallbackController::class, '__invoke'])
                    ->setName('languages.set.fallback.api');

                $languages->post('/clear-fallback', [LanguagesClearFallbackController::class, '__invoke'])
                    ->setName('languages.clear.fallback.api');

                $languages->post('/update-sort', [LanguagesUpdateSortOrderController::class, '__invoke'])
                    ->setName('languages.update.sort.api');

                $languages->post('/update-name', [LanguagesUpdateNameController::class, '__invoke'])
                    ->setName('languages.update.name.api');

                $languages->post('/update-code', [LanguagesUpdateCodeController::class, '__invoke'])
                    ->setName('languages.update.code.api');

                // ─────────────────────────────
                // Languages translations Control
                // ─────────────────────────────
                $languages->group('/{language_id:[0-9]+}/translations', function (RouteCollectorProxyInterface $languagesTranslations) {
                    $languagesTranslations->post('/query', [\Maatify\AdminKernel\Http\Controllers\Api\I18n\LanguageTranslationsQueryController::class, '__invoke'])
                        ->setName('languages.translations.list.api');

                    $languagesTranslations->post('/upsert', [\Maatify\AdminKernel\Http\Controllers\Api\I18n\LanguageTranslationUpsertController::class, '__invoke'])
                        ->setName('languages.translations.upsert.api');

                    $languagesTranslations->post('/delete', [\Maatify\AdminKernel\Http\Controllers\Api\I18n\LanguageTranslationDeleteController::class, '__invoke'])
                        ->setName('languages.translations.delete.api');
                });

            });

            // ─────────────────────────────
            // Admins Control
            // ─────────────────────────────
            $group->group('/admins', function (RouteCollectorProxyInterface $admins) {
                $admins->post('/query', [AdminQueryController::class, '__invoke'])
                    ->setName('admins.list.api');

                $admins->post('/create', [AdminController::class, 'create'])
                    ->setName('admin.create.api');

                $admins->get('/{admin_id:[0-9]+}/preferences', [AdminNotificationPreferenceController::class, 'getPreferences'])
                    ->setName('admin.preferences.read');

                $admins->put('/{admin_id:[0-9]+}/preferences', [AdminNotificationPreferenceController::class, 'upsertPreference'])
                    ->setName('admin.preferences.write');

                $admins->get('/{admin_id:[0-9]+}/notifications', [\Maatify\AdminKernel\Http\Controllers\AdminNotificationHistoryController::class, 'index'])
                    ->setName('admin.notifications.history');

                // ─────────────────────────────
                // Admin Roles Query
                // ─────────────────────────────
                $admins->post('/{admin_id:[0-9]+}/roles/query',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Admin\AdminRolesQueryController::class, '__invoke']
                )
                    ->setName('admin.roles.query');

                $admins->post('/{admin_id:[0-9]+}/permissions/effective',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Admin\EffectivePermissionsQueryController::class, '__invoke']
                )
                    ->setName('admin.permissions.effective');

                $admins->post(
                    '/{admin_id:[0-9]+}/permissions/direct/query',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Admin\DirectPermissionsQueryController::class, '__invoke']
                )
                    ->setName('admin.permissions.direct.query');

                $admins->post(
                    '/{admin_id:[0-9]+}/permissions/direct/assign',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Admin\AssignDirectPermissionController::class, '__invoke']
                )->setName('admin.permissions.direct.assign');

                $admins->post(
                    '/{admin_id:[0-9]+}/permissions/direct/revoke',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Admin\RevokeDirectPermissionController::class, '__invoke']
                )->setName('admin.permissions.direct.revoke');

                // ─────────────────────────────
                // Direct Permissions (Assignable) — QUERY
                // ─────────────────────────────
                $admins->post(
                    '/{admin_id:[0-9]+}/permissions/direct/assignable/query',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Admin\DirectPermissionsAssignableQueryController::class, '__invoke']
                )
                    ->setName('admin.permissions.direct.assignable.query');

                // ─────────────────────────────
                // Admin Email Control
                // ─────────────────────────────
                $admins->get('/{id:[0-9]+}/emails', [AdminController::class, 'getEmails'])
                    ->setName('admin.email.list.api');
                $admins->post('/{id:[0-9]+}/emails', [AdminController::class, 'addEmail'])
                    ->setName('admin.email.add');
            });

            $group->group('/admin-emails', function (RouteCollectorProxyInterface $adminEmails) {
                $adminEmails->post('/{emailId:[0-9]+}/verify', [AdminEmailVerificationController::class, 'verify'])
                    ->setName('admin.email.verify');
                $adminEmails->post('/{emailId:[0-9]+}/replace', [AdminEmailVerificationController::class, 'replace'])
                    ->setName('admin.email.replace');
                $adminEmails->post('/{emailId:[0-9]+}/fail', [AdminEmailVerificationController::class, 'fail'])
                    ->setName('admin.email.fail');
                $adminEmails->post('/{emailId}/restart-verification', [AdminEmailVerificationController::class, 'restart'])
                    ->setName('admin.email.restart');
            });

            // ─────────────────────────────
            // Permissions Control
            // ─────────────────────────────
            $group->group('/permissions', function (RouteCollectorProxyInterface $permissions) {
                $permissions->post('/query', [\Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionsController::class, '__invoke'])
                    ->setName('permissions.query.api');

                $permissions->post('/{id:[0-9]+}/metadata', [\Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionMetadataUpdateController::class, '__invoke'])
                    ->setName('permissions.metadata.update');
            });

            // ─────────────────────────────
            // Permission → Roles (Query)
            // ─────────────────────────────
            $group->post(
                '/permissions/{permission_id:[0-9]+}/roles/query',
                \Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionRolesQueryController::class
            )
                ->setName('permissions.roles.query')
                ->add(AuthorizationGuardMiddleware::class);

            // ─────────────────────────────
            // Permission → Admins (Direct Overrides Query)
            // ─────────────────────────────
            $group->post(
                '/permissions/{permission_id:[0-9]+}/admins/query',
                \Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionAdminsQueryController::class
            )
                ->setName('permissions.admins.query')
                ->add(AuthorizationGuardMiddleware::class);

            // ─────────────────────────────
            // Roles Control
            // ─────────────────────────────
            $group->group('/roles', function (RouteCollectorProxyInterface $roles) {
                $roles->post(
                    '/query',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RolesControllerQuery::class, '__invoke']
                )
                    ->setName('roles.query.api');

                $roles->post(
                    '/{id:[0-9]+}/metadata',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleMetadataUpdateController::class, '__invoke']
                )
                    ->setName('roles.metadata.update');

                $roles->post(
                    '/{id:[0-9]+}/toggle',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleToggleController::class, '__invoke']
                )
                    ->setName('roles.toggle');

                $roles->post(
                    '/{id:[0-9]+}/rename',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleRenameController::class, '__invoke']
                )
                    ->setName('roles.rename');

                $roles->post(
                    '/create',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleCreateController::class, '__invoke']
                )
                    ->setName('roles.create');

                // ─────────────────────────────
                // Role → Permissions (QUERY)
                // ─────────────────────────────
                $roles->post(
                    '/{id:[0-9]+}/permissions/query',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RolePermissionsQueryController::class, '__invoke']
                )
                    ->setName('roles.permissions.query');

                // ─────────────────────────────
                // Role → Permissions (ASSIGN)
                // ─────────────────────────────
                $roles->post(
                    '/{id:[0-9]+}/permissions/assign',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RolePermissionAssignController::class, '__invoke']
                )
                    ->setName('roles.permissions.assign');

                // ─────────────────────────────
                // Role → Permissions (UNASSIGN)
                // ─────────────────────────────
                $roles->post(
                    '/{id:[0-9]+}/permissions/unassign',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RolePermissionUnassignController::class, '__invoke']
                )
                    ->setName('roles.permissions.unassign');

                // AdminRoutes.php
                $roles->post(
                    '/{id:[0-9]+}/admins/query',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleAdminsQueryController::class, '__invoke']
                )->setName('roles.admins.query');

                $roles->post(
                    '/{id:[0-9]+}/admins/assign',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleAdminAssignController::class, '__invoke']
                )->setName('roles.admins.assign');

                $roles->post(
                    '/{id}/admins/unassign',
                    [\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleAdminUnassignController::class, '__invoke']
                )->setName('roles.admins.unassign');
            });

            $group->get('/notifications', [NotificationQueryController::class, 'index'])
                ->setName('notifications.list');

            $group->post('/admin/notifications/{id}/read', [\Maatify\AdminKernel\Http\Controllers\AdminNotificationReadController::class, 'markAsRead'])
                ->setName('admin.notifications.read');

        })
            // NOTE [Slim Middleware Order]:
            // Slim executes middlewares in LIFO order (last added = first executed).
            // This ordering is intentional so AdminContextMiddleware runs
            // BEFORE TwigAdminContextMiddleware, allowing Twig to safely
            // consume AdminContext and expose `current_admin` as a global.
            ->add(AuthorizationGuardMiddleware::class)
            ->add(\Maatify\AdminKernel\Http\Middleware\ScopeGuardMiddleware::class)
            ->add(\Maatify\AdminKernel\Http\Middleware\SessionStateGuardMiddleware::class)
            ->add(\Maatify\AdminKernel\Http\Middleware\AdminContextMiddleware::class)
            ->add(SessionGuardMiddleware::class);
    }
}
