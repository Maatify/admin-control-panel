# FIRST Project Permissions Route Audit

## 1. Real Protected Route Inventory

| Source | Method | Path | Route Name | File | Controller | Permission Checked? | Resolved Type | Permissions |
|---|---|---|---|---|---|---|---|---|
| UI | GET | `/activity-logs` | `activity_logs.view` | `ActivityLogsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\ActivityLogListController::class, index` | no | N/A | `N/A` |
| UI | GET | `/admins` | `admins.list.ui` | `AdminsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, index` | yes | single | `admins.list` |
| UI | GET | `/admins/create` | `admin.create.ui` | `AdminsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminCreateController::class, index` | yes | single | `admin.create` |
| UI | GET | `/admins/{id:[0-9]+}/profile` | `admins.profile.view` | `AdminsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, profile` | yes | single | `admins.profile.view` |
| UI | GET | `/admins/{id:[0-9]+}/profile/edit` | `admins.profile.edit.view` | `AdminsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, editProfile` | yes | single | `admins.profile.edit.view` |
| UI | POST | `/{id:[0-9]+}/profile/edit` | `admins.profile.edit` | `AdminsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, updateProfile` | yes | single | `admins.profile.edit` |
| UI | GET | `/{id:[0-9]+}/emails` | `admin.email.list.ui` | `AdminsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, emails` | yes | single | `admin.email.list` |
| UI | GET | `/{id:[0-9]+}/sessions` | `admins.session.list` | `AdminsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, sessions` | yes | single | `admins.session.list` |
| UI | GET | `/{id:[0-9]+}/permissions` | `admins.permissions` | `AdminsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\Admin\UiAdminsController::class, permissions` | yes | single | `admins.permissions` |
| UI | GET | `/app-settings` | `app_settings.list.ui` | `AppSettingsUiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Ui\AppSettings\AppSettingsListUiController::class, __invoke ` | no | N/A | `N/A` |
| UI | GET | `/content-document-types` | `content_documents.types.query.ui` | `ContentDocumentsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments\UiContentDocumentTypesController::class, index` | yes | single | `content_documents.types.query` |
| UI | GET | `/content-document-types/{type_id:[0-9]+}/documents` | `content_documents.versions.query.ui` | `ContentDocumentsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments\UiContentDocumentVersionsController::class, index` | yes | single | `content_documents.versions.query` |
| UI | GET | `/content-document-types/{type_id:[0-9]+}/documents/{document_id:[0-9]+}/translations` | `content_documents.translations.query.ui` | `ContentDocumentsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments\UiContentDocumentTranslationsController::class, index` | yes | single | `content_documents.translations.query` |
| UI | GET | `/content-document-types/{type_id:[0-9]+}/documents/{document_id:[0-9]+}/translations/{language_id:[0-9]+}` | `content_documents.translations.details` | `ContentDocumentsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\ContentDocuments\UiContentDocumentTranslationsUpdateController::class, index` | yes | single | `content_documents.translations.details` |
| UI | UNKNOWN | `UNKNOWN` | `content_documents.acceptance.query.ui` | `ContentDocumentsUiRoutes.php` | `UNKNOWN` | yes | single | `content_documents.acceptance.query` |
| UI | GET | `UNKNOWN` | `currencies.list.ui` | `CurrenciesUiRoutes.php` | `CurrenciesListUiController::class, __invoke` | yes | single | `currencies.list` |
| UI | GET | `/{currency_id:[0-9]+}/translations` | `currencies.translations.list.ui` | `CurrenciesUiRoutes.php` | `CurrencyTranslationsListUiController::class, __invoke` | yes | single | `currencies.translations.list` |
| UI | GET | `UNKNOWN` | `i18n.scopes.list.ui` | `I18nUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\I18n\ScopesListUiController::class, __invoke` | yes | single | `i18n.scopes.list` |
| UI | GET | `/{scope_id:[0-9]+}` | `i18n.scopes.details.ui` | `I18nUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\I18n\ScopeDetailsController::class, index` | yes | single | `i18n.scopes.details` |
| UI | GET | `/{scope_id:[0-9]+}/keys` | `i18n.scopes.keys.ui` | `I18nUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\I18n\ScopeKeysController::class, index` | yes | single | `i18n.scopes.keys` |
| UI | GET | `/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/keys` | `i18n.scopes.domains.keys.ui` | `I18nUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\I18n\ScopeDomainKeysSummaryController::class, index` | yes | single | `i18n.scopes.domains.keys` |
| UI | GET | `/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/translations` | `i18n.scopes.domains.translations.ui` | `I18nUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\I18n\ScopeDomainTranslationsUiController::class, index` | yes | single | `i18n.scopes.domains.translations` |
| UI | GET | `/{scope_id:[0-9]+}/coverage/languages/{language_id:[0-9]+}` | `i18n.scopes.coverage.domain.ui` | `I18nUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\I18n\I18nScopeLanguageCoverageUiController::class` | yes | single | `i18n.scopes.coverage.domain` |
| UI | GET | `/domains` | `i18n.domains.list.ui` | `I18nUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\I18n\DomainsListUiController::class, __invoke` | yes | single | `i18n.domains.list` |
| UI | GET | `UNKNOWN` | `image_profiles.list.ui` | `ImageProfilesUiRoutes.php` | `ImageProfilesListUiController::class, __invoke` | yes | single | `image_profiles.list` |
| UI | GET | `UNKNOWN` | `languages.list.ui` | `LanguagesUiRoutes.php` | `LanguagesListController::class, __invoke` | yes | single | `languages.list` |
| UI | GET | `/{language_id:[0-9]+}/translations` | `languages.translations.list.ui` | `LanguagesUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\I18n\LanguageTranslationsListUiController::class, __invoke` | yes | single | `i18n.translations.list` |
| UI | POST | `/logout` | `auth.logout` | `LogoutUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Web\LogoutController::class, logout` | no | N/A | `N/A` |
| UI | GET | `/logout` | `auth.logout.web` | `LogoutUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Web\LogoutController::class, logout` | no | N/A | `N/A` |
| UI | GET | `/permissions/{permission_id:[0-9]+}` | `permission.details.ui` | `PermissionsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\Permissions\UiAPermissionDetailsController::class, index` | yes | single | `permission.details` |
| UI | GET | `/permissions` | `permissions.query.ui` | `PermissionsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\Permissions\UiPermissionsController::class, index` | yes | single | `permissions.query` |
| UI | GET | `/roles` | `roles.query.ui` | `RolesUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\Roles\UiRolesController::class, index` | yes | single | `roles.query` |
| UI | GET | `/roles/{id:[0-9]+}` | `roles.view.ui` | `RolesUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\Roles\UiRoleDetailsController::class, __invoke` | yes | single | `roles.view` |
| UI | GET | `/sessions` | `sessions.list.ui` | `SessionsUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\SessionListController::class, __invoke` | yes | single | `sessions.list` |
| UI | GET | `/telemetry` | `telemetry.list` | `TelemetryUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\TelemetryListController::class, index` | no | N/A | `N/A` |
| UI | GET | `/2fa/setup` | `2fa.setup` | `TwoFactorUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\Auth\UiTwoFactorSetupController::class, index` | no | N/A | `N/A` |
| UI | POST | `/2fa/setup` | `2fa.enable` | `TwoFactorUiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Ui\Auth\UiTwoFactorSetupController::class, enable` | no | N/A | `N/A` |
| API | POST | `/{emailId:[0-9]+}/verify` | `admin.email.verify` | `AdminEmailApiRoutes.php` | `AdminEmailVerificationController::class, verify` | yes | single | `admin.email.verify` |
| API | POST | `/{emailId:[0-9]+}/replace` | `admin.email.replace` | `AdminEmailApiRoutes.php` | `AdminEmailVerificationController::class, replace` | yes | single | `admin.email.replace` |
| API | POST | `/{emailId:[0-9]+}/fail` | `admin.email.fail` | `AdminEmailApiRoutes.php` | `AdminEmailVerificationController::class, fail` | yes | single | `admin.email.fail` |
| API | POST | `/{emailId}/restart-verification` | `admin.email.restart` | `AdminEmailApiRoutes.php` | `AdminEmailVerificationController::class, restart` | yes | single | `admin.email.restart` |
| API | POST | `/query` | `admins.list.api` | `AdminsApiRoutes.php` | `AdminQueryController::class, __invoke` | yes | single | `admins.list` |
| API | POST | `/create` | `admin.create.api` | `AdminsApiRoutes.php` | `AdminController::class, create` | yes | single | `admin.create` |
| API | GET | `/{admin_id:[0-9]+}/preferences` | `admin.preferences.read` | `AdminsApiRoutes.php` | `AdminNotificationPreferenceController::class, getPreferences` | yes | single | `admin.preferences.read` |
| API | PUT | `/{admin_id:[0-9]+}/preferences` | `admin.preferences.write` | `AdminsApiRoutes.php` | `AdminNotificationPreferenceController::class, upsertPreference` | yes | single | `admin.preferences.write` |
| API | GET | `/{admin_id:[0-9]+}/notifications` | `admin.notifications.history` | `AdminsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\AdminNotificationHistoryController::class, index` | yes | single | `admin.notifications.history` |
| API | POST | `/{admin_id:[0-9]+}/roles/query` | `admin.roles.query` | `AdminsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Admin\AdminRolesQueryController::class, __invoke` | yes | single | `admin.roles.query` |
| API | POST | `/{admin_id:[0-9]+}/permissions/effective` | `admin.permissions.effective` | `AdminsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Admin\EffectivePermissionsQueryController::class, __invoke` | yes | single | `admin.permissions.effective` |
| API | POST | `/{admin_id:[0-9]+}/permissions/direct/query` | `admin.permissions.direct.query` | `AdminsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Admin\DirectPermissionsQueryController::class, __invoke` | yes | single | `admin.permissions.direct.query` |
| API | POST | `/{admin_id:[0-9]+}/permissions/direct/assign` | `admin.permissions.direct.assign` | `AdminsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Admin\AssignDirectPermissionController::class, __invoke` | yes | single | `admin.permissions.direct.assign` |
| API | POST | `/{admin_id:[0-9]+}/permissions/direct/revoke` | `admin.permissions.direct.revoke` | `AdminsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Admin\RevokeDirectPermissionController::class, __invoke` | yes | single | `admin.permissions.direct.revoke` |
| API | POST | `/{admin_id:[0-9]+}/permissions/direct/assignable/query` | `admin.permissions.direct.assignable.query` | `AdminsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Admin\DirectPermissionsAssignableQueryController::class, __invoke` | yes | single | `admin.permissions.direct.assignable.query` |
| API | GET | `/{id:[0-9]+}/emails` | `admin.email.list.api` | `AdminsApiRoutes.php` | `AdminController::class, getEmails` | yes | single | `admin.email.list` |
| API | POST | `/{id:[0-9]+}/emails` | `admin.email.add` | `AdminsApiRoutes.php` | `AdminController::class, addEmail` | yes | single | `admin.email.add` |
| API | POST | `/query` | `app_settings.list.api` | `AppSettingsApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\AppSettings\AppSettingsQueryController::class, __invoke ` | yes | single | `app_settings.list` |
| API | POST | `/create` | `app_settings.create.api` | `AppSettingsApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\AppSettings\AppSettingsCreateController::class, __invoke ` | yes | single | `app_settings.create` |
| API | POST | `/metadata` | `app_settings.metadata.api` | `AppSettingsApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\AppSettings\AppSettingsMetadataController::class, __invoke ` | yes | single | `app_settings.create` |
| API | POST | `/update` | `app_settings.update.api` | `AppSettingsApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\AppSettings\AppSettingsUpdateController::class, __invoke ` | yes | single | `app_settings.update` |
| API | POST | `/set-active` | `app_settings.set_active.api` | `AppSettingsApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\AppSettings\AppSettingsSetActiveController::class, __invoke ` | yes | single | `app_settings.set_active` |
| API | POST | `/dropdown` | `content_documents.types.dropdown.api` | `ContentDocumentsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentsKeysDropdownController::class` | yes | anyOf | `content_documents.types.dropdown, content_documents.types.query` |
| API | POST | `/query` | `content_documents.types.query.api` | `ContentDocumentsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentTypesQueryController::class` | yes | single | `content_documents.types.query` |
| API | POST | `/create` | `content_documents.types.create.api` | `ContentDocumentsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentTypesCreateController::class` | yes | single | `content_documents.types.create` |
| API | POST | `/{type_id:[0-9]+}/update` | `content_documents.types.update.api` | `ContentDocumentsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentTypesUpdateController::class` | yes | single | `content_documents.types.update` |
| API | POST | `/query` | `content_documents.versions.query.api` | `ContentDocumentsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentVersionsQueryController::class` | yes | single | `content_documents.versions.query` |
| API | POST | `/create` | `content_documents.versions.create.api` | `ContentDocumentsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentVersionsCreateController::class` | yes | single | `content_documents.versions.create` |
| API | POST | `/activate` | `content_documents.versions.activate.api` | `ContentDocumentsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentVersionsActivateController::class` | yes | single | `content_documents.versions.activate` |
| API | POST | `/deactivate` | `content_documents.versions.deactivate.api` | `ContentDocumentsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentVersionsDeactivateController::class` | yes | single | `content_documents.versions.deactivate` |
| API | POST | `/publish` | `content_documents.versions.publish.api` | `ContentDocumentsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentVersionsPublishController::class` | yes | single | `content_documents.versions.publish` |
| API | POST | `/archive` | `content_documents.versions.archive.api` | `ContentDocumentsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentVersionsArchiveController::class` | yes | single | `content_documents.versions.archive` |
| API | POST | `/query` | `content_documents.translations.query.api` | `ContentDocumentsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentTranslationsQueryController::class` | yes | single | `content_documents.translations.query` |
| API | POST | `/{language_id:[0-9]+}` | `content_documents.translations.upsert.api` | `ContentDocumentsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\ContentDocuments\ContentDocumentTranslationsUpsertController::class` | yes | single | `content_documents.translations.upsert` |
| API | UNKNOWN | `UNKNOWN` | `content_documents.acceptance.query.api` | `ContentDocumentsApiRoutes.php` | `UNKNOWN` | yes | single | `content_documents.acceptance.query` |
| API | POST | `/dropdown` | `currencies.dropdown.api` | `CurrenciesApiRoutes.php` | `CurrenciesDropdownController::class, __invoke` | yes | single | `currencies.dropdown` |
| API | POST | `/query` | `currencies.list.api` | `CurrenciesApiRoutes.php` | `CurrenciesQueryController::class, __invoke` | yes | single | `currencies.list` |
| API | POST | `/create` | `currencies.create.api` | `CurrenciesApiRoutes.php` | `CurrenciesCreateController::class, __invoke` | yes | single | `currencies.create` |
| API | POST | `/update` | `currencies.update.api` | `CurrenciesApiRoutes.php` | `CurrenciesUpdateController::class, __invoke` | yes | single | `currencies.update` |
| API | POST | `/set-active` | `currencies.set_active.api` | `CurrenciesApiRoutes.php` | `CurrenciesSetActiveController::class, __invoke` | yes | single | `currencies.set_active` |
| API | POST | `/update-sort` | `currencies.update_sort.api` | `CurrenciesApiRoutes.php` | `CurrenciesUpdateSortOrderController::class, __invoke` | yes | single | `currencies.update_sort` |
| API | POST | `/query` | `currencies.translations.list.api` | `CurrenciesApiRoutes.php` | `CurrencyTranslationsQueryController::class, __invoke` | yes | single | `currencies.translations.list` |
| API | POST | `/upsert` | `currencies.translations.upsert.api` | `CurrenciesApiRoutes.php` | `CurrencyTranslationUpsertController::class, __invoke` | yes | single | `currencies.translations.upsert` |
| API | POST | `/delete` | `currencies.translations.delete.api` | `CurrenciesApiRoutes.php` | `CurrencyTranslationDeleteController::class, __invoke` | yes | single | `currencies.translations.delete` |
| API | POST | `/dropdown` | `i18n.scopes.dropdown.api` | `I18nApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopesDropdownController::class, __invoke ` | yes | single | `i18n.scopes.dropdown` |
| API | POST | `/query` | `i18n.scopes.list.api` | `I18nApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopesQueryController::class, __invoke ` | yes | single | `i18n.scopes.list` |
| API | POST | `/create` | `i18n.scopes.create.api` | `I18nApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopeCreateController::class, __invoke ` | yes | single | `i18n.scopes.create` |
| API | POST | `/change-code` | `i18n.scopes.change_code.api` | `I18nApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopeChangeCodeController::class, __invoke ` | yes | single | `i18n.scopes.change_code` |
| API | POST | `/set-active` | `i18n.scopes.set_active.api` | `I18nApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopeSetActiveController::class, __invoke ` | yes | single | `i18n.scopes.set_active` |
| API | POST | `/update-sort` | `i18n.scopes.update_sort.api` | `I18nApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopeUpdateSortController::class, __invoke ` | yes | single | `i18n.scopes.update_sort` |
| API | POST | `/update-metadata` | `i18n.scopes.update_metadata.api` | `I18nApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\I18n\Scope\I18nScopeUpdateMetadataController::class, __invoke ` | yes | single | `i18n.scopes.update_metadata` |
| API | POST | `/{scope_id:[0-9]+}/domains/query` | `i18n.scopes.domains.query.api` | `I18nApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\I18nScopeDomainsQueryController::class` | yes | single | `i18n.scopes.details` |
| API | POST | `/{scope_id:[0-9]+}/domains/assign` | `i18n.scopes.domains.assign.api` | `I18nApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\I18nScopeDomainAssignController::class` | yes | single | `i18n.scopes.domains.assign` |
| API | POST | `/{scope_id:[0-9]+}/domains/unassign` | `i18n.scopes.domains.unassign.api` | `I18nApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\I18nScopeDomainUnassignController::class` | yes | single | `i18n.scopes.domains.unassign` |
| API | GET | `/{scope_id:[0-9]+}/domains/dropdown` | `i18n.scopes.domains.dropdown.api` | `I18nApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\I18nScopeDomainsDropdownController::class` | yes | anyOf | `i18n.scopes.keys.create, i18n.scopes.domains.dropdown` |
| API | POST | `/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/keys/query` | `i18n.scopes.domains.keys.query.api` | `I18nApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\I18nScopeDomainKeysSummaryQueryController::class` | yes | single | `i18n.scopes.domains.keys` |
| API | POST | `/{scope_id:[0-9]+}/domains/{domain_id:[0-9]+}/translations/query` | `i18n.scopes.domains.translations.query.api` | `I18nApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\I18n\ScopeDomains\ScopeDomainTranslationsQueryController::class` | yes | single | `i18n.scopes.domains.translations` |
| API | GET | `/{scope_id:[0-9]+}/coverage` | `i18n.scopes.coverage.language.api` | `I18nApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\I18n\Coverage\I18nScopeCoverageByLanguageController::class` | yes | single | `i18n.scopes.details` |
| API | GET | `/{scope_id:[0-9]+}/coverage/languages/{language_id:[0-9]+}` | `i18n.scopes.coverage.domain.api` | `I18nApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\I18n\Coverage\I18nScopeCoverageByDomainController::class` | yes | single | `i18n.scopes.coverage.domain` |
| API | POST | `/{scope_id:[0-9]+}/keys/query` | `i18n.scopes.keys.query.api` | `I18nApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\I18n\Keys\I18nScopeKeysQueryController::class` | yes | single | `i18n.scopes.keys` |
| API | POST | `/{scope_id:[0-9]+}/keys/update-name` | `i18n.scopes.keys.update_name.api` | `I18nApiRoutes.php` | `I18nScopeKeysUpdateNameController::class, __invoke` | yes | single | `i18n.scopes.keys.update_name` |
| API | POST | `/{scope_id:[0-9]+}/keys/create` | `i18n.scopes.keys.create.api` | `I18nApiRoutes.php` | `I18nScopeKeysCreateController::class, __invoke` | yes | single | `i18n.scopes.keys.create` |
| API | POST | `/{scope_id:[0-9]+}/keys/update_metadata` | `i18n.scopes.keys.update_metadata.api` | `I18nApiRoutes.php` | `I18nScopeKeysUpdateDescriptionController::class, __invoke` | yes | single | `i18n.scopes.keys.update_metadata` |
| API | POST | `/query` | `i18n.domains.list.api` | `I18nApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nDomainsQueryController::class, __invoke ` | yes | single | `i18n.domains.list` |
| API | POST | `/create` | `i18n.domains.create.api` | `I18nApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nDomainCreateController::class, __invoke ` | yes | single | `i18n.domains.create` |
| API | POST | `/change-code` | `i18n.domains.change_code.api` | `I18nApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nDomainChangeCodeController::class, __invoke ` | yes | single | `i18n.domains.change_code` |
| API | POST | `/set-active` | `i18n.domains.set_active.api` | `I18nApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nDomainSetActiveController::class, __invoke ` | yes | single | `i18n.domains.set_active` |
| API | POST | `/update-sort` | `i18n.domains.update_sort.api` | `I18nApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nScopeDomainSortController::class, __invoke ` | yes | single | `i18n.domains.update_sort` |
| API | POST | `/update-metadata` | `i18n.domains.update_metadata.api` | `I18nApiRoutes.php` | ` \Maatify\AdminKernel\Http\Controllers\Api\I18n\Domains\I18nDomainUpdateMetadataController::class, __invoke ` | yes | single | `i18n.domains.update_metadata` |
| API | POST | `/dropdown` | `image_profiles.dropdown.api` | `ImageProfilesApiRoutes.php` | `ImageProfilesDropdownController::class, __invoke` | yes | single | `image_profiles.dropdown` |
| API | POST | `/query` | `image_profiles.list.api` | `ImageProfilesApiRoutes.php` | `ImageProfilesQueryController::class, __invoke` | yes | single | `image_profiles.list` |
| API | POST | `/details` | `image_profiles.details.api` | `ImageProfilesApiRoutes.php` | `ImageProfilesDetailsController::class, __invoke` | yes | single | `image_profiles.details` |
| API | POST | `/create` | `image_profiles.create.api` | `ImageProfilesApiRoutes.php` | `ImageProfilesCreateController::class, __invoke` | yes | single | `image_profiles.create` |
| API | POST | `/update` | `image_profiles.update.api` | `ImageProfilesApiRoutes.php` | `ImageProfilesUpdateController::class, __invoke` | yes | single | `image_profiles.update` |
| API | POST | `/set-active` | `image_profiles.set_active.api` | `ImageProfilesApiRoutes.php` | `ImageProfilesSetActiveController::class, __invoke` | yes | single | `image_profiles.set_active` |
| API | POST | `/dropdown` | `i18n.languages.dropdown.api` | `LanguagesApiRoutes.php` | `LanguageDropdownController::class` | yes | anyOf | `i18n.translations.upsert, i18n.languages.dropdown` |
| API | POST | `/query` | `languages.list.api` | `LanguagesApiRoutes.php` | `LanguagesQueryController::class, __invoke` | yes | single | `languages.list` |
| API | POST | `/create` | `languages.create.api` | `LanguagesApiRoutes.php` | `LanguagesCreateController::class, __invoke` | yes | single | `languages.create` |
| API | POST | `/update-settings` | `languages.update.settings.api` | `LanguagesApiRoutes.php` | `LanguagesUpdateSettingsController::class, __invoke` | yes | single | `languages.update.settings` |
| API | POST | `/set-active` | `languages.set.active.api` | `LanguagesApiRoutes.php` | `LanguagesSetActiveController::class, __invoke` | yes | single | `languages.set.active` |
| API | POST | `/set-fallback` | `languages.set.fallback.api` | `LanguagesApiRoutes.php` | `LanguagesSetFallbackController::class, __invoke` | yes | single | `languages.set.fallback` |
| API | POST | `/clear-fallback` | `languages.clear.fallback.api` | `LanguagesApiRoutes.php` | `LanguagesClearFallbackController::class, __invoke` | yes | single | `languages.set.fallback` |
| API | POST | `/update-sort` | `languages.update.sort.api` | `LanguagesApiRoutes.php` | `LanguagesUpdateSortOrderController::class, __invoke` | yes | single | `languages.update.sort` |
| API | POST | `/update-name` | `languages.update.name.api` | `LanguagesApiRoutes.php` | `LanguagesUpdateNameController::class, __invoke` | yes | single | `languages.update.name` |
| API | POST | `/update-code` | `languages.update.code.api` | `LanguagesApiRoutes.php` | `LanguagesUpdateCodeController::class, __invoke` | yes | single | `languages.update.code` |
| API | POST | `/query` | `languages.translations.list.api` | `LanguagesApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\I18n\LanguageTranslationsQueryController::class, __invoke` | yes | single | `languages.translations.list` |
| API | POST | `/upsert` | `languages.translations.upsert.api` | `LanguagesApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\I18n\LanguageTranslationUpsertController::class, __invoke` | yes | single | `languages.translations.upsert` |
| API | POST | `/delete` | `languages.translations.delete.api` | `LanguagesApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\I18n\LanguageTranslationDeleteController::class, __invoke` | yes | single | `languages.translations.delete` |
| API | POST | `/query` | `permissions.query.api` | `PermissionsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionsController::class, __invoke` | yes | single | `permissions.query` |
| API | POST | `/{id:[0-9]+}/metadata` | `permissions.metadata.update` | `PermissionsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionMetadataUpdateController::class, __invoke` | yes | single | `permissions.metadata.update` |
| API | POST | `/permissions/{permission_id:[0-9]+}/roles/query` | `permissions.roles.query` | `PermissionsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionRolesQueryController::class` | yes | single | `permissions.roles.query` |
| API | POST | `/permissions/{permission_id:[0-9]+}/admins/query` | `permissions.admins.query` | `PermissionsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Permissions\PermissionAdminsQueryController::class` | yes | single | `permissions.admins.query` |
| API | POST | `/query` | `roles.query.api` | `RolesApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Roles\RolesControllerQuery::class, __invoke` | yes | single | `roles.query` |
| API | POST | `/{id:[0-9]+}/metadata` | `roles.metadata.update` | `RolesApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleMetadataUpdateController::class, __invoke` | yes | single | `roles.metadata.update` |
| API | POST | `/{id:[0-9]+}/toggle` | `roles.toggle` | `RolesApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleToggleController::class, __invoke` | yes | single | `roles.toggle` |
| API | POST | `/{id:[0-9]+}/rename` | `roles.rename` | `RolesApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleRenameController::class, __invoke` | yes | single | `roles.rename` |
| API | POST | `/create` | `roles.create` | `RolesApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleCreateController::class, __invoke` | yes | single | `roles.create` |
| API | POST | `/{id:[0-9]+}/permissions/query` | `roles.permissions.query` | `RolesApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Roles\RolePermissionsQueryController::class, __invoke` | yes | single | `roles.permissions.query` |
| API | POST | `/{id:[0-9]+}/permissions/assign` | `roles.permissions.assign` | `RolesApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Roles\RolePermissionAssignController::class, __invoke` | yes | single | `roles.permissions.assign` |
| API | POST | `/{id:[0-9]+}/permissions/unassign` | `roles.permissions.unassign` | `RolesApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Roles\RolePermissionUnassignController::class, __invoke` | yes | single | `roles.permissions.unassign` |
| API | POST | `/{id:[0-9]+}/admins/query` | `roles.admins.query` | `RolesApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleAdminsQueryController::class, __invoke` | yes | single | `roles.admins.query` |
| API | POST | `/{id:[0-9]+}/admins/assign` | `roles.admins.assign` | `RolesApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleAdminAssignController::class, __invoke` | yes | single | `roles.admins.assign` |
| API | POST | `/{id}/admins/unassign` | `roles.admins.unassign` | `RolesApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Roles\RoleAdminUnassignController::class, __invoke` | yes | single | `roles.admins.unassign` |
| API | POST | `/query` | `sessions.list.api` | `SessionsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Sessions\SessionQueryController::class, __invoke` | yes | single | `sessions.list` |
| API | DELETE | `/{session_id}` | `sessions.revoke.id` | `SessionsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Sessions\SessionRevokeController::class, __invoke` | yes | single | `sessions.revoke` |
| API | POST | `/revoke-bulk` | `sessions.revoke.bulk` | `SessionsApiRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\Api\Sessions\SessionBulkRevokeController::class, __invoke` | yes | single | `sessions.revoke` |
| API | GET | `/notifications` | `notifications.list` | `ApiProtectedRoutes.php` | `NotificationQueryController::class, index` | yes | single | `notifications.list` |
| API | POST | `/admin/notifications/{id}/read` | `admin.notifications.read` | `ApiProtectedRoutes.php` | `\Maatify\AdminKernel\Http\Controllers\AdminNotificationReadController::class, markAsRead` | yes | single | `admin.notifications.read` |

## 2. Permission Resolution Source of Truth

Permissions are enforced by the `AuthorizationGuardMiddleware` which wraps `ApiProtectedRoutes` (globally) and parts of `UiProtectedRoutes` (individually per route/group). It uses `PermissionMapperV2` to resolve route names into canonical permissions. If a route is unmapped, it falls back to requiring the route name itself as a canonical permission. UI Routes that do not explicitly add `AuthorizationGuardMiddleware` (e.g. Dashboard) or are globally bypassed bypass authorization checks.

## 3. Comparison against `permissions_seed.sql`

### A. Used by protected routes and present in seed
- `admin.create`
- `admin.email.add`
- `admin.email.fail`
- `admin.email.list`
- `admin.email.replace`
- `admin.email.restart`
- `admin.email.verify`
- `admin.notifications.history`
- `admin.notifications.read`
- `admin.permissions.direct.assign`
- `admin.permissions.direct.assignable.query`
- `admin.permissions.direct.query`
- `admin.permissions.direct.revoke`
- `admin.permissions.effective`
- `admin.preferences.read`
- `admin.preferences.write`
- `admin.roles.query`
- `admins.list`
- `admins.permissions`
- `admins.profile.edit`
- `admins.profile.edit.view`
- `admins.profile.view`
- `admins.session.list`
- `app_settings.create`
- `app_settings.list`
- `app_settings.set_active`
- `app_settings.update`
- `content_documents.acceptance.query`
- `content_documents.translations.details`
- `content_documents.translations.query`
- `content_documents.translations.upsert`
- `content_documents.types.create`
- `content_documents.types.dropdown`
- `content_documents.types.query`
- `content_documents.types.update`
- `content_documents.versions.activate`
- `content_documents.versions.archive`
- `content_documents.versions.create`
- `content_documents.versions.deactivate`
- `content_documents.versions.publish`
- `content_documents.versions.query`
- `currencies.create`
- `currencies.dropdown`
- `currencies.list`
- `currencies.set_active`
- `currencies.translations.delete`
- `currencies.translations.list`
- `currencies.translations.upsert`
- `currencies.update`
- `currencies.update_sort`
- `i18n.domains.change_code`
- `i18n.domains.create`
- `i18n.domains.list`
- `i18n.domains.set_active`
- `i18n.domains.update`
- `i18n.domains.update_metadata`
- `i18n.domains.update_sort`
- `i18n.languages.dropdown`
- `i18n.scopes.change_code`
- `i18n.scopes.coverage.domain`
- `i18n.scopes.create`
- `i18n.scopes.details`
- `i18n.scopes.domains.assign`
- `i18n.scopes.domains.dropdown`
- `i18n.scopes.domains.keys`
- `i18n.scopes.domains.translations`
- `i18n.scopes.domains.unassign`
- `i18n.scopes.dropdown`
- `i18n.scopes.keys`
- `i18n.scopes.keys.create`
- `i18n.scopes.keys.update_metadata`
- `i18n.scopes.keys.update_name`
- `i18n.scopes.list`
- `i18n.scopes.set_active`
- `i18n.scopes.update`
- `i18n.scopes.update_metadata`
- `i18n.scopes.update_sort`
- `i18n.translations.list`
- `i18n.translations.upsert`
- `languages.create`
- `languages.list`
- `languages.set.active`
- `languages.set.fallback`
- `languages.translations.delete`
- `languages.translations.list`
- `languages.translations.upsert`
- `languages.update.code`
- `languages.update.name`
- `languages.update.settings`
- `languages.update.sort`
- `notifications.list`
- `permission.details`
- `permissions.admins.query`
- `permissions.metadata.update`
- `permissions.query`
- `permissions.roles.query`
- `roles.admins.assign`
- `roles.admins.query`
- `roles.admins.unassign`
- `roles.admins.view`
- `roles.create`
- `roles.metadata.update`
- `roles.permissions.assign`
- `roles.permissions.query`
- `roles.permissions.unassign`
- `roles.permissions.view`
- `roles.query`
- `roles.rename`
- `roles.toggle`
- `roles.view`
- `sessions.list`
- `sessions.revoke`
- `sessions.view_all`

### B. Used by protected routes but missing from seed
- `image_profiles.create`
- `image_profiles.details`
- `image_profiles.dropdown`
- `image_profiles.list`
- `image_profiles.set_active`
- `image_profiles.update`

### C. Present in seed but not used by any currently protected route
- `i18n.keys.list`

### D. Routes under protected UI/API trees that are not actually permission-checked
- `activity_logs.view`
- `app_settings.list.ui`
- `auth.logout`
- `auth.logout.web`
- `telemetry.list`
- `2fa.setup`
- `2fa.enable`

### E. Permission-checked routes outside the two protected route trees
- `roles.admins.view`
- `roles.permissions.view`
- `sessions.view_all`
- `i18n.domains.update`
- `i18n.scopes.update`

### F. Legacy / suspicious / stale permissions that appear renamed or replaced
- `i18n.keys.list`
- `i18n.keys.list` (commented out in mapper, replaced by scoped mappings)

## 4. Tree Coverage Verification

### UI Registered Subtrees Inspectable:
- `ActivityLogsUiRoutes.php`
- `AdminsUiRoutes.php`
- `AppSettingsUiRoutes.php`
- `ContentDocumentsUiRoutes.php`
- `CurrenciesUiRoutes.php`
- `DashboardUiRoutes.php`
- `I18nUiRoutes.php`
- `ImageProfilesUiRoutes.php`
- `LanguagesUiRoutes.php`
- `LogoutUiRoutes.php`
- `PermissionsUiRoutes.php`
- `RolesUiRoutes.php`
- `SessionsUiRoutes.php`
- `SettingsUiRoutes.php`
- `TelemetryUiRoutes.php`
- `TwoFactorUiRoutes.php`

### API Registered Subtrees Inspectable:
- `AdminEmailApiRoutes.php`
- `AdminsApiRoutes.php`
- `AppSettingsApiRoutes.php`
- `ContentDocumentsApiRoutes.php`
- `CurrenciesApiRoutes.php`
- `I18nApiRoutes.php`
- `ImageProfilesApiRoutes.php`
- `LanguagesApiRoutes.php`
- `PermissionsApiRoutes.php`
- `RolesApiRoutes.php`
- `SessionsApiRoutes.php`

**Note:** All listed subtrees were inspected recursively via the glob scan.

## 5. Rename / drift detection

There is evidence of permission drift where older `i18n.keys.list` mappings were commented out and split into `i18n.scopes.keys` and `i18n.scopes.domains.keys`. The seed still retains `i18n.keys.list`. Also, `image_profiles.*` routes were added to API routes but not explicitly seeded in `permissions_seed.sql`, causing them to resolve dynamically but fail if explicitly checked against the database list. `content_documents.translations.details` is commented out in mapper but the UI route `.ui` resolves manually to it.

## 6. Final Canonical Recommendation

The canonical permission seed should be updated to reflect reality:
1. **ADD** the missing `image_profiles.*` canonical permissions, as they are actively enforced by `ImageProfilesApiRoutes`.
2. **REMOVE** the deprecated `i18n.keys.list` permission, as its route mappings were commented out and it is no longer checked.
