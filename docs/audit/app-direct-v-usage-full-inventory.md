# 1. Scope Confirmation

This inventory strictly covers **only the `app/` directory**. All other directories (like `Modules/`, `docs/`, `tests/`) are fully excluded.

---

# 2. Executive Totals

* total files in `app/` containing direct `v::...`: 51
* total direct `v::...` usages in `app/`: 111

Totals by category:
* EntityId-like: 27
* Boolean: 15
* Date: 5
* Generic string: 47
* Email: 3
* Array: 1
* Generic integer: 11
* Enum: 2

---

# 3. Full File-by-File Inventory

| File | Field | Raw Usage | Category | Notes |
| ---- | ----- | --------- | -------- | ----- |
| `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php` | `permission_id` | `v::intType()->positive()` | EntityId-like |  |
| `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php` | `is_allowed` | `v::boolType()` | Boolean | boolean flag |
| `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php` | `expires_at` | `v::optional(v::dateTime('Y-m-d H:i:s'))` | Date |  |
| `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionRevokeSchema.php` | `permission_id` | `v::intType()->positive()` | EntityId-like |  |
| `app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php` | `code` | `v::stringType()->notEmpty()` | Generic string | schema text property |
| `app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php` | `scope` | `v::optional(v::stringType())` | Generic string | schema text property |
| `app/Modules/AdminKernel/Validation/Schemas/Auth/AuthLoginSchema.php` | `email` | `v::email()` | Email |  |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `status` | `v::optional(v::stringType())` | Generic string | schema text property |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `channel` | `v::optional(v::stringType())` | Generic string | schema text property |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `from` | `v::optional(v::date())` | Date |  |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `to` | `v::optional(v::date())` | Date |  |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `admin_id` | `v::optional(v::intVal())` | EntityId-like |  |
| `app/Modules/AdminKernel/Validation/Schemas/TelegramWebhookSchema.php` | `message` | `v::optional(v::arrayType())` | Array | complex list |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminAssignSchema.php` | `admin_id` | `v::intType()->positive()` | EntityId-like |  |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionUnassignSchema.php` | `permission_id` | `v::intType()->positive()` | EntityId-like |  |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleToggleSchema.php` | `is_active` | `v::boolType()` | Boolean | boolean flag |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminUnassignSchema.php` | `admin_id` | `v::intType()->positive()` | EntityId-like |  |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionAssignSchema.php` | `permission_id` | `v::intType()->positive()` | EntityId-like |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminLookupEmailSchema.php` | `email` | `v::email()` | Email |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `page` | `v::optional(v::intVal()->min(1))` | Generic integer |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `per_page` | `v::optional(v::intVal()->min(1)->max(100))` | Generic integer |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `id` | `v::optional(v::intVal())` | EntityId-like |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `email` | `v::optional(v::stringType())` | Generic string | schema text property |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `admin_id` | `v::intVal()` | EntityId-like |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `page` | `v::optional(v::intVal()->min(1))` | Generic integer |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `limit` | `v::optional(v::intVal()->min(1))` | Generic integer |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `notification_type` | `v::optional(v::stringType())` | Generic string | schema text property |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `from_date` | `v::optional(v::date())` | Date |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `to_date` | `v::optional(v::date())` | Date |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceGetSchema.php` | `admin_id` | `v::intVal()` | EntityId-like |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationReadSchema.php` | `id` | `v::intVal()` | EntityId-like |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminCreateSchema.php` | `display_name` | `\Respect\Validation\Validator::stringType()` | Generic string | schema text property |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | `admin_id` | `v::intVal()` | EntityId-like |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | `notification_type` | `v::stringType()->notEmpty()` | Generic string | schema text property |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | `channel_type` | `v::stringType()->notEmpty()` | Generic string | schema text property |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminEmailVerifySchema.php` | `emailId` | `v::intVal()` | Generic integer |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminAddEmailSchema.php` | `id` | `v::intVal()` | EntityId-like |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminAddEmailSchema.php` | `email` | `v::email()` | Email |  |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminGetEmailSchema.php` | `id` | `v::intVal()` | EntityId-like |  |
| `app/Modules/AdminKernel/Validation/Schemas/Session/SessionRevokeSchema.php` | `session_id` | `v::stringType()->notEmpty()` | Generic string | schema text property |
| `app/Modules/AdminKernel/Validation/Schemas/Session/SessionBulkRevokeSchema.php` | `session_ids` | `v::arrayType()->notEmpty()->each(v::stringType())` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTranslationsUpsertSchema.php` | `title` | `v::stringType()` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTranslationsUpsertSchema.php` | `meta_title` | `v::stringType()->length(0, 255)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTranslationsUpsertSchema.php` | `meta_description` | `v::stringType()->length(0, 5000)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTranslationsUpsertSchema.php` | `content` | `v::stringType()` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php` | `key` | `v::stringType()` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php` | `requires_acceptance_default` | `v::boolType()` | Boolean | boolean flag |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php` | `is_system` | `v::boolType()` | Boolean | boolean flag |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentVersionsCreateSchema.php` | `version` | `v::stringType()` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentVersionsCreateSchema.php` | `requires_acceptance` | `v::boolType()` | Boolean | boolean flag |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesUpdateSchema.php` | `requires_acceptance_default` | `v::boolType()` | Boolean | boolean flag |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesUpdateSchema.php` | `is_system` | `v::boolType()` | Boolean | boolean flag |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileSetActiveSchema.php` | `id` | `v::intType()->min(1)` | EntityId-like |  |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileSetActiveSchema.php` | `is_active` | `v::boolType()` | Boolean | boolean flag |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `(unknown)` | `$nullableString = static fn (int $max): \Respect\Validation\Validatable => v::optional(v::anyOf(v::nullType(), v::stringType()->length(1, $max)));` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `(unknown)` | `$nullableInt = static fn (int $min = 0, ?int $max = null): \Respect\Validation\Validatable => v::optional(v::anyOf(v::nullType(), v::intType()->min($min)->max($max ?? PHP_INT_MAX)));` | EntityId-like |  |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `code` | `v::stringType()->notEmpty()->length(1, 64)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `allowed_mime_types` | `v::optional(v::anyOf(v::nullType(), v::stringType()))` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `is_active` | `v::optional(v::boolType())` | Boolean | boolean flag |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `notes` | `v::optional(v::anyOf(v::nullType(), v::stringType()))` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `requires_transparency` | `v::optional(v::boolType())` | Boolean | boolean flag |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `variants` | `v::optional(v::anyOf(v::nullType(), v::stringType()))` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileDetailsSchema.php` | `id` | `v::intType()->min(1)` | EntityId-like |  |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `(unknown)` | `$nullableString = static fn (int $max): \Respect\Validation\Validatable => v::anyOf(v::nullType(), v::stringType()->length(1, $max));` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `(unknown)` | `$nullableInt = static fn (int $min = 0, ?int $max = null): \Respect\Validation\Validatable => v::anyOf(v::nullType(), v::intType()->min($min)->max($max ?? PHP_INT_MAX));` | EntityId-like |  |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `id` | `v::intType()->min(1)` | EntityId-like |  |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `code` | `v::stringType()->notEmpty()->length(1, 64)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `allowed_mime_types` | `v::anyOf(v::nullType(), v::stringType())` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `is_active` | `v::boolType()` | Boolean | boolean flag |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `notes` | `v::anyOf(v::nullType(), v::stringType())` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `requires_transparency` | `v::boolType()` | Boolean | boolean flag |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `variants` | `v::anyOf(v::nullType(), v::stringType())` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDetailsSchema.php` | `id` | `v::intType()->min(1)` | EntityId-like |  |
| `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeUpdateSchema.php` | `id` | `v::intType()->min(1)` | EntityId-like |  |
| `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeUpdateSchema.php` | `display_name` | `v::stringType()->notEmpty()->length(1, 150)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeCreateSchema.php` | `display_name` | `v::stringType()->notEmpty()->length(1, 150)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDeleteSchema.php` | `id` | `v::intType()->min(1)` | EntityId-like |  |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyTranslationDeleteSchema.php` | `language_id` | `v::intType()->min(1)` | EntityId-like |  |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyCreateSchema.php` | `code` | `v::stringType()->notEmpty()->length(3, 3)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyCreateSchema.php` | `name` | `v::stringType()->notEmpty()->length(1, 50)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyCreateSchema.php` | `symbol` | `v::stringType()->notEmpty()->length(1, 10)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyCreateSchema.php` | `is_active` | `v::optional(v::boolType())` | Boolean | boolean flag |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyCreateSchema.php` | `display_order` | `v::optional(v::intType()->min(0))` | Generic integer |  |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSchema.php` | `id` | `v::intType()->min(1)` | EntityId-like |  |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSchema.php` | `code` | `v::stringType()->notEmpty()->length(3, 3)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSchema.php` | `name` | `v::stringType()->notEmpty()->length(1, 50)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSchema.php` | `symbol` | `v::stringType()->notEmpty()->length(1, 10)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSchema.php` | `is_active` | `v::boolType()` | Boolean | boolean flag |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSchema.php` | `display_order` | `v::intType()->min(1)` | Generic integer |  |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSortOrderSchema.php` | `id` | `v::intType()->min(1)` | EntityId-like |  |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSortOrderSchema.php` | `display_order` | `v::intType()->min(1)` | Generic integer |  |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencySetActiveSchema.php` | `id` | `v::intType()->min(1)` | EntityId-like |  |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencySetActiveSchema.php` | `is_active` | `v::boolType()` | Boolean | boolean flag |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyTranslationUpsertSchema.php` | `language_id` | `v::intType()->min(1)` | EntityId-like |  |
| `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyTranslationUpsertSchema.php` | `translated_name` | `v::stringType()->notEmpty()->length(1, 50)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainUpdateSortSchema.php` | `position` | `v::intVal()->min(0)` | Generic integer |  |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageCreateSchema.php` | `code` | `v::stringType()->length(2, 10)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageCreateSchema.php` | `direction` | `v::in(array_map(` | Enum |  |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSortOrderSchema.php` | `sort_order` | `v::intVal()->min(1)` | Generic integer |  |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSettingsSchema.php` | `direction` | `v::in(array_map(` | Enum |  |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateCodeSchema.php` | `code` | `v::stringType()->length(1, 32)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeUpdateSortSchema.php` | `position` | `v::intVal()->min(0)` | Generic integer |  |
| `app/Modules/AdminKernel/Domain/I18n/LanguageTranslationValue/Validation/LanguageTranslationValueUpsertSchema.php` | `value` | `v::stringType()->length(1), //no max as type is text` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php` | `setting_group` | `v::stringType()->length(1, 64)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php` | `setting_key` | `v::stringType()->length(1, 64)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php` | `setting_value` | `v::stringType()` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsCreateSchema.php` | `setting_group` | `v::stringType()->length(1, 64)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsCreateSchema.php` | `setting_key` | `v::stringType()->length(1, 64)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsCreateSchema.php` | `setting_value` | `v::stringType()->length(1, null)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsSetActiveSchema.php` | `setting_group` | `v::stringType()->length(1, 64)` | Generic string | schema text property |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsSetActiveSchema.php` | `setting_key` | `v::stringType()->length(1, 64)` | Generic string | schema text property |

---

# 4. Grouped Pattern Inventory

## `v::intType()->positive()`

* total occurrences: 6
* files:
  * `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionRevokeSchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminAssignSchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionUnassignSchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminUnassignSchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionAssignSchema.php`
* fields:
  * `permission_id`
  * `admin_id`

## `v::boolType()`

* total occurrences: 12
* files:
  * `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleToggleSchema.php`
  * `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php`
  * `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentVersionsCreateSchema.php`
  * `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesUpdateSchema.php`
  * `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileSetActiveSchema.php`
  * `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php`
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSchema.php`
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencySetActiveSchema.php`
* fields:
  * `is_allowed`
  * `is_active`
  * `requires_acceptance_default`
  * `is_system`
  * `requires_acceptance`
  * `requires_transparency`

## `v::optional(v::dateTime('Y-m-d H:i:s'))`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php`
* fields:
  * `expires_at`

## `v::stringType()->notEmpty()`

* total occurrences: 4
* files:
  * `app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Session/SessionRevokeSchema.php`
* fields:
  * `code`
  * `notification_type`
  * `channel_type`
  * `session_id`

## `v::optional(v::stringType())`

* total occurrences: 5
* files:
  * `app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php`
* fields:
  * `scope`
  * `status`
  * `channel`
  * `email`
  * `notification_type`

## `v::email()`

* total occurrences: 3
* files:
  * `app/Modules/AdminKernel/Validation/Schemas/Auth/AuthLoginSchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminLookupEmailSchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminAddEmailSchema.php`
* fields:
  * `email`

## `v::optional(v::date())`

* total occurrences: 4
* files:
  * `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php`
* fields:
  * `from`
  * `to`
  * `from_date`
  * `to_date`

## `v::optional(v::intVal())`

* total occurrences: 2
* files:
  * `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php`
* fields:
  * `admin_id`
  * `id`

## `v::optional(v::arrayType())`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Validation/Schemas/TelegramWebhookSchema.php`
* fields:
  * `message`

## `v::optional(v::intVal()->min(1))`

* total occurrences: 3
* files:
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php`
* fields:
  * `page`
  * `limit`

## `v::optional(v::intVal()->min(1)->max(100))`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php`
* fields:
  * `per_page`

## `v::intVal()`

* total occurrences: 7
* files:
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceGetSchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationReadSchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminEmailVerifySchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminAddEmailSchema.php`
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminGetEmailSchema.php`
* fields:
  * `admin_id`
  * `id`
  * `emailId`

## `\Respect\Validation\Validator::stringType()`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminCreateSchema.php`
* fields:
  * `display_name`

## `v::arrayType()->notEmpty()->each(v::stringType())`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Validation/Schemas/Session/SessionBulkRevokeSchema.php`
* fields:
  * `session_ids`

## `v::stringType()`

* total occurrences: 5
* files:
  * `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTranslationsUpsertSchema.php`
  * `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php`
  * `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentVersionsCreateSchema.php`
  * `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php`
* fields:
  * `title`
  * `content`
  * `key`
  * `version`
  * `setting_value`

## `v::stringType()->length(0, 255)`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTranslationsUpsertSchema.php`
* fields:
  * `meta_title`

## `v::stringType()->length(0, 5000)`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTranslationsUpsertSchema.php`
* fields:
  * `meta_description`

## `v::intType()->min(1)`

* total occurrences: 13
* files:
  * `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileSetActiveSchema.php`
  * `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileDetailsSchema.php`
  * `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php`
  * `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDetailsSchema.php`
  * `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeUpdateSchema.php`
  * `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDeleteSchema.php`
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyTranslationDeleteSchema.php`
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSchema.php`
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSortOrderSchema.php`
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencySetActiveSchema.php`
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyTranslationUpsertSchema.php`
* fields:
  * `id`
  * `language_id`
  * `display_order`

## `$nullableString = static fn (int $max): \Respect\Validation\Validatable => v::optional(v::anyOf(v::nullType(), v::stringType()->length(1, $max)));`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php`
* fields:
  * `unknown`

## `$nullableInt = static fn (int $min = 0, ?int $max = null): \Respect\Validation\Validatable => v::optional(v::anyOf(v::nullType(), v::intType()->min($min)->max($max ?? PHP_INT_MAX)));`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php`
* fields:
  * `unknown`

## `v::stringType()->notEmpty()->length(1, 64)`

* total occurrences: 2
* files:
  * `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php`
  * `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php`
* fields:
  * `code`

## `v::optional(v::anyOf(v::nullType(), v::stringType()))`

* total occurrences: 3
* files:
  * `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php`
* fields:
  * `allowed_mime_types`
  * `notes`
  * `variants`

## `v::optional(v::boolType())`

* total occurrences: 3
* files:
  * `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php`
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyCreateSchema.php`
* fields:
  * `is_active`
  * `requires_transparency`

## `$nullableString = static fn (int $max): \Respect\Validation\Validatable => v::anyOf(v::nullType(), v::stringType()->length(1, $max));`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php`
* fields:
  * `unknown`

## `$nullableInt = static fn (int $min = 0, ?int $max = null): \Respect\Validation\Validatable => v::anyOf(v::nullType(), v::intType()->min($min)->max($max ?? PHP_INT_MAX));`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php`
* fields:
  * `unknown`

## `v::anyOf(v::nullType(), v::stringType())`

* total occurrences: 3
* files:
  * `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php`
* fields:
  * `allowed_mime_types`
  * `notes`
  * `variants`

## `v::stringType()->notEmpty()->length(1, 150)`

* total occurrences: 2
* files:
  * `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeUpdateSchema.php`
  * `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeCreateSchema.php`
* fields:
  * `display_name`

## `v::stringType()->notEmpty()->length(3, 3)`

* total occurrences: 2
* files:
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyCreateSchema.php`
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSchema.php`
* fields:
  * `code`

## `v::stringType()->notEmpty()->length(1, 50)`

* total occurrences: 3
* files:
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyCreateSchema.php`
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSchema.php`
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyTranslationUpsertSchema.php`
* fields:
  * `name`
  * `translated_name`

## `v::stringType()->notEmpty()->length(1, 10)`

* total occurrences: 2
* files:
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyCreateSchema.php`
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSchema.php`
* fields:
  * `symbol`

## `v::optional(v::intType()->min(0))`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyCreateSchema.php`
* fields:
  * `display_order`

## `v::intVal()->min(0)`

* total occurrences: 2
* files:
  * `app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainUpdateSortSchema.php`
  * `app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeUpdateSortSchema.php`
* fields:
  * `position`

## `v::stringType()->length(2, 10)`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageCreateSchema.php`
* fields:
  * `code`

## `v::in(array_map(`

* total occurrences: 2
* files:
  * `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageCreateSchema.php`
  * `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSettingsSchema.php`
* fields:
  * `direction`

## `v::intVal()->min(1)`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSortOrderSchema.php`
* fields:
  * `sort_order`

## `v::stringType()->length(1, 32)`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateCodeSchema.php`
* fields:
  * `code`

## `v::stringType()->length(1), //no max as type is text`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Domain/I18n/LanguageTranslationValue/Validation/LanguageTranslationValueUpsertSchema.php`
* fields:
  * `value`

## `v::stringType()->length(1, 64)`

* total occurrences: 6
* files:
  * `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php`
  * `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsCreateSchema.php`
  * `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsSetActiveSchema.php`
* fields:
  * `setting_group`
  * `setting_key`

## `v::stringType()->length(1, null)`

* total occurrences: 1
* files:
  * `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsCreateSchema.php`
* fields:
  * `setting_value`


---

# 5. Existing Rule Coverage Mapping

### `v::intType()->positive()`
* Already covered clearly (EntityIdRule or Integer/Number rule)

### `v::boolType()`
* Already covered clearly (BooleanRule)

### `v::optional(v::dateTime('Y-m-d H:i:s'))`
* Should stay inline

### `v::stringType()->notEmpty()`
* Already covered clearly (StringRule)

### `v::optional(v::stringType())`
* Already covered clearly (StringRule)

### `v::email()`
* Not covered (Candidate for EmailRule)

### `v::optional(v::date())`
* Should stay inline

### `v::optional(v::intVal())`
* Already covered clearly (EntityIdRule or Integer/Number rule)

### `v::optional(v::arrayType())`
* Should stay inline

### `v::optional(v::intVal()->min(1))`
* Should stay inline

### `v::optional(v::intVal()->min(1)->max(100))`
* Should stay inline

### `v::intVal()`
* Already covered clearly (EntityIdRule or Integer/Number rule)

### `\Respect\Validation\Validator::stringType()`
* Already covered clearly (StringRule)

### `v::arrayType()->notEmpty()->each(v::stringType())`
* Should stay inline

### `v::stringType()`
* Already covered clearly (StringRule)

### `v::stringType()->length(0, 255)`
* Already covered clearly (StringRule)

### `v::stringType()->length(0, 5000)`
* Already covered clearly (StringRule)

### `v::intType()->min(1)`
* Should stay inline

### `$nullableString = static fn (int $max): \Respect\Validation\Validatable => v::optional(v::anyOf(v::nullType(), v::stringType()->length(1, $max)));`
* Already covered clearly (StringRule)

### `$nullableInt = static fn (int $min = 0, ?int $max = null): \Respect\Validation\Validatable => v::optional(v::anyOf(v::nullType(), v::intType()->min($min)->max($max ?? PHP_INT_MAX)));`
* Should stay inline

### `v::stringType()->notEmpty()->length(1, 64)`
* Already covered clearly (StringRule)

### `v::optional(v::anyOf(v::nullType(), v::stringType()))`
* Already covered clearly (StringRule)

### `v::optional(v::boolType())`
* Already covered clearly (BooleanRule)

### `$nullableString = static fn (int $max): \Respect\Validation\Validatable => v::anyOf(v::nullType(), v::stringType()->length(1, $max));`
* Already covered clearly (StringRule)

### `$nullableInt = static fn (int $min = 0, ?int $max = null): \Respect\Validation\Validatable => v::anyOf(v::nullType(), v::intType()->min($min)->max($max ?? PHP_INT_MAX));`
* Should stay inline

### `v::anyOf(v::nullType(), v::stringType())`
* Already covered clearly (StringRule)

### `v::stringType()->notEmpty()->length(1, 150)`
* Already covered clearly (StringRule)

### `v::stringType()->notEmpty()->length(3, 3)`
* Already covered clearly (StringRule)

### `v::stringType()->notEmpty()->length(1, 50)`
* Already covered clearly (StringRule)

### `v::stringType()->notEmpty()->length(1, 10)`
* Already covered clearly (StringRule)

### `v::optional(v::intType()->min(0))`
* Should stay inline

### `v::intVal()->min(0)`
* Should stay inline

### `v::stringType()->length(2, 10)`
* Already covered clearly (StringRule)

### `v::in(array_map(`
* Should stay inline

### `v::intVal()->min(1)`
* Should stay inline

### `v::stringType()->length(1, 32)`
* Already covered clearly (StringRule)

### `v::stringType()->length(1), //no max as type is text`
* Already covered clearly (StringRule)

### `v::stringType()->length(1, 64)`
* Already covered clearly (StringRule)

### `v::stringType()->length(1, null)`
* Already covered clearly (StringRule)


---

# 6. Action Buckets

## A. Safe replacements with existing rules
* `v::intType()->min(1)` (EntityIdRule)
* `v::intVal()` (EntityIdRule or similar integer rule)
* `v::boolType()` (BooleanRule)
* `v::stringType()->notEmpty()->length(...)` (StringRule)
* `v::optional(v::stringType()...)` (StringRule::optional())

## B. Possible replacements but needs judgment
* `v::anyOf(v::nullType(), v::stringType())`
* `v::optional(v::intVal()->min(1))` (Pagination limit vs EntityId)

## C. Candidate new reusable rule families
* `v::email()` (Used 4 times, perfect candidate for Semantic\EmailRule)

## D. Leave inline
* `v::arrayType()->notEmpty()->each(v::stringType())` (in SessionBulkRevokeSchema.php)
* `v::in(array_map(...)` (Language direction/settings enums)
* `v::optional(v::date())` (Notification/History queries)

---

# 7. Final Short Recommendation

* Next safest pass: Globally replace `v::intType()->min(1)` with `EntityIdRule::required()` and `v::boolType()` with `BooleanRule::required()`.
* Introduce `EmailRule` to clean up the `v::email()` duplicates.
* Do not touch enums, arrays, and dates; leave them completely inline.
