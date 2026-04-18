# FIRST Project Permissions Route Audit

## 1. Protected route tree coverage

- **UI protected entrypoint:** `UiProtectedRoutes.php`
- **API protected entrypoint:** `ApiProtectedRoutes.php`

### Every registered subtree inspected:
#### UI Subtrees:
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

#### API Subtrees:
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

### Unresolved Subtrees:
None. All dynamically added features under the core namespaces were resolved and extracted completely via Slim's RouteCollector mapping reflection.

## 2. Permission resolution source of truth

- **Middleware Path:** `Maatify\AdminKernel\Http\Middleware\AuthorizationGuardMiddleware`
- **Mapper Path:** `Maatify\AdminKernel\Domain\Security\PermissionMapperV2`
- **Fallback Behavior:** If a route is unmapped, `PermissionMapperV2` falls back to requiring the route name itself as a canonical permission directly through `AuthorizationService::checkPermission`.
- **Bypass Cases:** Routes explicitly defined within route groups that omit adding `AuthorizationGuardMiddleware`. Specifically, UI route files like `TwoFactorUiRoutes.php`, `DashboardUiRoutes.php`, `ActivityLogsUiRoutes.php`, `AppSettingsUiRoutes.php`, `LogoutUiRoutes.php`, `TelemetryUiRoutes.php`, and `SettingsUiRoutes.php` globally bypass permission enforcement. The system assumes these to be globally accessible if authenticated.

## 3. Canonical route-derived permissions

These permissions are definitively required by permission-checked protected routes. They are either explicitly mapped or implicitly mapped through fallback.
- `content_documents.types.query`
- `content_documents.versions.query`
- `content_documents.translations.query`
- `content_documents.translations.details`
- `admins.list`
- `admin.create`
- `admins.profile.view`
- `admins.profile.edit.view`
- `admins.profile.edit`
- `admin.email.list`
- `admins.session.list`
- `admins.permissions`
- `i18n.scopes.list`
- `i18n.scopes.details`
- `i18n.scopes.keys`
- `i18n.scopes.domains.keys`
- `i18n.scopes.domains.translations`
- `i18n.scopes.coverage.domain`
- `i18n.domains.list`
- `permission.details`
- `permissions.query`
- `roles.query`
- `roles.view`
- `languages.list`
- `i18n.translations.list`
- `sessions.list`
- `currencies.list`
- `currencies.translations.list`
- `image_profiles.list`
- `admin.email.verify`
- `admin.email.replace`
- `admin.email.fail`
- `admin.email.restart`
- `admin.preferences.read`
- `admin.preferences.write`
- `admin.notifications.history`
- `admin.roles.query`
- `admin.permissions.effective`
- `admin.permissions.direct.query`
- `admin.permissions.direct.assign`
- `admin.permissions.direct.revoke`
- `admin.permissions.direct.assignable.query`
- `admin.email.add`
- `sessions.revoke`
- `content_documents.types.dropdown`
- `content_documents.types.create`
- `content_documents.types.update`
- `content_documents.versions.create`
- `content_documents.versions.activate`
- `content_documents.versions.deactivate`
- `content_documents.versions.publish`
- `content_documents.versions.archive`
- `content_documents.translations.upsert`
- `i18n.scopes.dropdown`
- `i18n.scopes.create`
- `i18n.scopes.change_code`
- `i18n.scopes.set_active`
- `i18n.scopes.update_sort`
- `i18n.scopes.update_metadata`
- `i18n.scopes.domains.assign`
- `i18n.scopes.domains.unassign`
- `i18n.scopes.keys.create`
- `i18n.scopes.domains.dropdown`
- `i18n.scopes.keys.update_name`
- `i18n.scopes.keys.update_metadata`
- `i18n.domains.create`
- `i18n.domains.change_code`
- `i18n.domains.set_active`
- `i18n.domains.update_sort`
- `i18n.domains.update_metadata`
- `image_profiles.dropdown`
- `image_profiles.details`
- `image_profiles.create`
- `image_profiles.update`
- `image_profiles.set_active`
- `app_settings.list`
- `app_settings.create`
- `app_settings.update`
- `app_settings.set_active`
- `i18n.translations.upsert`
- `i18n.languages.dropdown`
- `languages.create`
- `languages.update.settings`
- `languages.set.active`
- `languages.set.fallback`
- `languages.update.sort`
- `languages.update.name`
- `languages.update.code`
- `languages.translations.list`
- `languages.translations.upsert`
- `languages.translations.delete`
- `permissions.metadata.update`
- `permissions.roles.query`
- `permissions.admins.query`
- `roles.metadata.update`
- `roles.toggle`
- `roles.rename`
- `roles.create`
- `roles.permissions.query`
- `roles.permissions.assign`
- `roles.permissions.unassign`
- `roles.admins.query`
- `roles.admins.assign`
- `roles.admins.unassign`
- `currencies.dropdown`
- `currencies.create`
- `currencies.update`
- `currencies.set_active`
- `currencies.update_sort`
- `currencies.translations.upsert`
- `currencies.translations.delete`
- `notifications.list`
- `admin.notifications.read`

## 4. Protected-tree routes that are not permission-checked

These routes exist within protected subtrees but lack the `AuthorizationGuardMiddleware`.

| Source | Method | Path | Route Name | File | Controller |
|---|---|---|---|---|---|
| UI | GET | `/2fa/setup` | `2fa.setup` | `TwoFactorUiRoutes.php` | `Maatify\AdminKernel\Http\Controllers\Ui\Auth\UiTwoFactorSetupController::index` |
| UI | POST | `/2fa/setup` | `2fa.enable` | `TwoFactorUiRoutes.php` | `Maatify\AdminKernel\Http\Controllers\Ui\Auth\UiTwoFactorSetupController::enable` |
| UI | GET | `/app-settings` | `app_settings.list.ui` | `AppSettingsUiRoutes.php` | `Maatify\AdminKernel\Http\Controllers\Ui\AppSettings\AppSettingsListUiController::__invoke` |
| UI | GET | `/activity-logs` | `activity_logs.view` | `ActivityLogsUiRoutes.php` | `Maatify\AdminKernel\Http\Controllers\Ui\ActivityLogListController::index` |
| UI | GET | `/telemetry` | `telemetry.list` | `TelemetryUiRoutes.php` | `Maatify\AdminKernel\Http\Controllers\Ui\TelemetryListController::index` |
| UI | POST | `/logout` | `auth.logout` | `LogoutUiRoutes.php` | `Maatify\AdminKernel\Http\Controllers\Web\LogoutController::logout` |
| UI | GET | `/logout` | `auth.logout.web` | `LogoutUiRoutes.php` | `Maatify\AdminKernel\Http\Controllers\Web\LogoutController::logout` |

## 5. Standalone permissions

These permissions are not derived directly from route-names but are actively used within controller/service logic (e.g. injected into UI Twig templates for toggling capabilities). They are proven by concrete codebase usages.
- `roles.admins.view`: Verified via `$this->uiPermissionService->hasPermission($adminId, 'roles.admins.view')` inside `UiRoleDetailsController` and `UiAdminsController`.
- `roles.permissions.view`: Verified via `$this->uiPermissionService->hasPermission($adminId, 'roles.permissions.view')` inside `UiRoleDetailsController`.
- `sessions.view_all`: Verified via `$this->authorizationService->hasPermission($adminId, 'sessions.view_all')` inside `SessionQueryController`.
- `i18n.domains.update`: Verified via `$this->uiPermissionService->hasPermission($adminId, 'i18n.domains.update')` inside `DomainsListUiController`.
- `i18n.scopes.update`: Verified via `$this->uiPermissionService->hasPermission($adminId, 'i18n.scopes.update')` inside `ScopesListUiController`.

## 6. Legacy / stale / drifted permissions

These permissions exist in the database seed but have drifted out of relevance. They are unused, bypassed, or actively deleted in mapping logic.

### 6.1. Commented-out explicit dead mappings
- `i18n.keys.list`: The API mapping was commented out in `PermissionMapperV2`, leaving this as an orphaned legacy permission in the seed.
- `content_documents.translations.details`: Present in seed and commented out in `PermissionMapperV2`. A UI route resolves to it via fallback, but the core API logic was refactored.

### 6.2. Non-Permission Checked Transports remaining in the Seed
These route names exist as permissions in the seed but the routes themselves bypass the `AuthorizationGuardMiddleware`. These shouldn't be seeded as they are unprotected transport paths.
- `2fa.setup`
- `2fa.enable`
- `activity_logs.view`
- `telemetry.list`
- `auth.logout`
- `auth.logout.web`

## 7. Seed reconciliation

### `used_and_present_in_seed`
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
- `roles.create`
- `roles.metadata.update`
- `roles.permissions.assign`
- `roles.permissions.query`
- `roles.permissions.unassign`
- `roles.query`
- `roles.rename`
- `roles.toggle`
- `roles.view`
- `sessions.list`
- `sessions.revoke`

### `used_but_missing_in_seed`
- `image_profiles.create`
- `image_profiles.details`
- `image_profiles.dropdown`
- `image_profiles.list`
- `image_profiles.set_active`
- `image_profiles.update`

### `present_in_seed_but_not_canonical_route_derived`
- `2fa.enable`
- `2fa.setup`
- `activity_logs.view`
- `auth.logout`
- `auth.logout.web`
- `content_documents.acceptance.query`
- `i18n.domains.update`
- `i18n.keys.list`
- `i18n.scopes.update`
- `roles.admins.view`
- `roles.permissions.view`
- `sessions.view_all`
- `telemetry.list`

### `non_permission_checked_route_names_present_in_seed`
- `2fa.setup`
- `2fa.enable`
- `activity_logs.view`
- `telemetry.list`
- `auth.logout`
- `auth.logout.web`

### `standalone_permissions_present_in_seed`
- `i18n.domains.update`
- `i18n.scopes.update`
- `roles.admins.view`
- `roles.permissions.view`
- `sessions.view_all`

### `suspected_legacy_permissions`
- `content_documents.acceptance.query`
- `i18n.keys.list`

### `fallback_derived_permissions`
- `admin.email.add`
- `admin.email.fail`
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
- `admins.permissions`
- `admins.profile.edit.view`
- `admins.profile.view`
- `admins.session.list`
- `content_documents.translations.details`
- `notifications.list`
- `permissions.admins.query`
- `permissions.metadata.update`
- `permissions.roles.query`
- `roles.admins.assign`
- `roles.admins.query`
- `roles.admins.unassign`
- `roles.create`
- `roles.metadata.update`
- `roles.permissions.assign`
- `roles.permissions.query`
- `roles.permissions.unassign`
- `roles.rename`
- `roles.toggle`

## 8. Final recommendation before seed rebuild

When rebuilding `permissions_seed.sql`, the definitive source of canonical permissions should be the union of `used_and_present_in_seed` and `used_but_missing_in_seed` (which integrates the newly identified `image_profiles.*` routes), combined with the valid `standalone_permissions` (`roles.admins.view`, `roles.permissions.view`, `sessions.view_all`, `i18n.domains.update`, `i18n.scopes.update`).
The `suspected_legacy_permissions` (such as `i18n.keys.list` and `content_documents.acceptance.query`) and `non_permission_checked_route_names_present_in_seed` (such as `auth.logout` and `2fa.setup`) must be explicitly excluded from the canonical API/Domain permission seed as they are either dead endpoints or represent un-enforced transport variants that violate strict database governance rules.
