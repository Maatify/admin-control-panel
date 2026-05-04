# 1. Scope Confirmation

This readiness pass explicitly covers:
* `app/`
* `Modules/`

It explicitly excludes `docs/`, `vendor/`, generated files, and unrelated tooling.

---

# 2. Executive Summary

- Total files still containing direct/legacy validation logic: 62
- Total cases **ready now**: 1
- Total cases **needing judgment**: 81
- Total cases **leaving inline**: 9
- Top immediate execution opportunities: Replace remaining exact matches of `v::stringType()->length(...)` with `StringRule`, and `v::email()` with `EmailRule`.

---

# 3. Ready-Now Inventory

| File | Field / Location | Current Validation | Suggested Replacement | Why Ready Now |
| ---- | ---------------- | ------------------ | --------------------- | ------------- |
| `Modules/Validation/Schemas/AuthLoginSchemaExample.php` | `email` | `v::email()` | `Primitive\EmailRule::required()` | Exact match to required email behavior |

---

# 4. Needs-Judgment Inventory

| File | Field / Location | Current Validation | Closest Possible Target | Why It Needs Judgment |
| ---- | ---------------- | ------------------ | ----------------------- | --------------------- |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `status` | `v::optional(v::stringType())` | `Primitive\StringRule::optional()` | Unbounded string validation might need db bounds applied |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `channel` | `v::optional(v::stringType())` | `Primitive\StringRule::optional()` | Unbounded string validation might need db bounds applied |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `admin_id` | `v::optional(v::intVal())` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php` | `permission_id` | `v::intType()->positive()` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php` | `is_allowed` | `v::boolType()` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionRevokeSchema.php` | `permission_id` | `v::intType()->positive()` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php` | `code` | `v::stringType()->notEmpty()` | `Primitive\StringRule::required()` | Unbounded string validation needs explicit max length boundaries defined |
| `app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php` | `scope` | `v::optional(v::stringType())` | `Primitive\StringRule::optional()` | Unbounded string validation might need db bounds applied |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminAssignSchema.php` | `admin_id` | `v::intType()->positive()` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionUnassignSchema.php` | `permission_id` | `v::intType()->positive()` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleToggleSchema.php` | `is_active` | `v::boolType()` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminUnassignSchema.php` | `admin_id` | `v::intType()->positive()` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionAssignSchema.php` | `permission_id` | `v::intType()->positive()` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `page` | `v::optional(v::intVal()->min(1))` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `per_page` | `v::optional(v::intVal()->min(1)->max(100))` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `id` | `v::optional(v::intVal())` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `email` | `v::optional(v::stringType())` | `Primitive\StringRule::optional()` | Unbounded string validation might need db bounds applied |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `admin_id` | `v::intVal()` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `page` | `v::optional(v::intVal()->min(1))` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `limit` | `v::optional(v::intVal()->min(1))` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `notification_type` | `v::optional(v::stringType())` | `Primitive\StringRule::optional()` | Unbounded string validation might need db bounds applied |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceGetSchema.php` | `admin_id` | `v::intVal()` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationReadSchema.php` | `id` | `v::intVal()` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | `admin_id` | `v::intVal()` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | `notification_type` | `v::stringType()->notEmpty()` | `Primitive\StringRule::required()` | Unbounded string validation needs explicit max length boundaries defined |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | `channel_type` | `v::stringType()->notEmpty()` | `Primitive\StringRule::required()` | Unbounded string validation needs explicit max length boundaries defined |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminEmailVerifySchema.php` | `emailId` | `v::intVal()` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminAddEmailSchema.php` | `id` | `v::intVal()` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminGetEmailSchema.php` | `id` | `v::intVal()` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Validation/Schemas/Session/SessionRevokeSchema.php` | `session_id` | `v::stringType()->notEmpty()` | `Primitive\StringRule::required()` | Unbounded string validation needs explicit max length boundaries defined |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTranslationsUpsertSchema.php` | `content` | `v::stringType()` | `Primitive\StringRule::optional()` | Unbounded string validation might need db bounds applied |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php` | `key` | `v::stringType()` | `Primitive\StringRule::optional()` | Unbounded string validation might need db bounds applied |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php` | `requires_acceptance_default` | `v::boolType()` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php` | `is_system` | `v::boolType()` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentVersionsCreateSchema.php` | `version` | `v::stringType()` | `Primitive\StringRule::optional()` | Unbounded string validation might need db bounds applied |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentVersionsCreateSchema.php` | `requires_acceptance` | `v::boolType()` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesUpdateSchema.php` | `requires_acceptance_default` | `v::boolType()` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesUpdateSchema.php` | `is_system` | `v::boolType()` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileSetActiveSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileSetActiveSchema.php` | `is_active` | `v::boolType()` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `is_active` | `v::optional(v::boolType())` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `requires_transparency` | `v::optional(v::boolType())` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileDetailsSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `is_active` | `v::boolType()` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `requires_transparency` | `v::boolType()` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDetailsSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeUpdateSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDeleteSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainUpdateSortSchema.php` | `position` | `v::intVal()->min(0)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSortOrderSchema.php` | `sort_order` | `v::intVal()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeUpdateSortSchema.php` | `position` | `v::intVal()->min(0)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php` | `setting_value` | `v::stringType()` | `Primitive\StringRule::optional()` | Unbounded string validation might need db bounds applied |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateCreateSchema.php` | `provider_id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateCreateSchema.php` | `rate` | `v::stringType()->notEmpty()` | `Primitive\StringRule::required()` | Unbounded string validation needs explicit max length boundaries defined |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateCreateSchema.php` | `recorded_at` | `v::optional(v::stringType()->notEmpty())` | `Primitive\StringRule::required()` | Unbounded string validation needs explicit max length boundaries defined |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateSetActiveSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateSetActiveSchema.php` | `is_active` | `v::boolType()` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderSetActiveSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderSetActiveSchema.php` | `is_active` | `v::boolType()` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSortOrderSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSortOrderSchema.php` | `display_order` | `v::intType()->min(0)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderCreateSchema.php` | `description` | `v::optional(v::stringType())` | `Primitive\StringRule::optional()` | Unbounded string validation might need db bounds applied |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSortOrderSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSortOrderSchema.php` | `display_order` | `v::intType()->min(0)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderSoftDeleteSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateSoftDeleteSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSchema.php` | `rate` | `v::stringType()->notEmpty()` | `Primitive\StringRule::required()` | Unbounded string validation needs explicit max length boundaries defined |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSchema.php` | `recorded_at` | `v::optional(v::stringType()->notEmpty())` | `Primitive\StringRule::required()` | Unbounded string validation needs explicit max length boundaries defined |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSchema.php` | `description` | `v::optional(v::stringType())` | `Primitive\StringRule::optional()` | Unbounded string validation might need db bounds applied |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyTranslationDeleteSchema.php` | `language_id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyCreateSchema.php` | `is_active` | `v::optional(v::boolType())` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyUpdateSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyUpdateSchema.php` | `is_active` | `v::boolType()` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyUpdateSortOrderSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyUpdateSortOrderSchema.php` | `display_order` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencySetActiveSchema.php` | `id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencySetActiveSchema.php` | `is_active` | `v::boolType()` | `Primitive\BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyTranslationUpsertSchema.php` | `language_id` | `v::intType()->min(1)` | `Primitive\EntityIdRule / PaginationRule` | Strict `intType()` is not 100% equivalent to type-coercing `intVal()` rules and needs policy decisions |

---

# 5. Leave-Inline Inventory

The following categories and usages should remain inline for now as abstracting them introduces unnecessary complexity or obscures local rules:

- **Enums (`v::in(...)`)**: Tightly coupled to local business concepts.
- **Array Shapes (`v::arrayType()->key(...)`, `v::arrayType()->each(...)`)**: Define complex JSON payload structures that are highly specific.
- **Dates (`v::date()`, `v::dateTime()`)**: Highly local parsing logic.
- **Specific Business Constraints**: Like custom closures, conditional validation, or highly specific regex fields not shared across the project.

- `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` - Field `from`: Highly local date formats
- `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` - Field `to`: Highly local date formats
- `app/Modules/AdminKernel/Validation/Schemas/TelegramWebhookSchema.php` - Field `message`: Array JSON payload structural definitions
- `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php` - Field `expires_at`: Highly local date formats
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` - Field `from_date`: Highly local date formats
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` - Field `to_date`: Highly local date formats
- `app/Modules/AdminKernel/Validation/Schemas/Session/SessionBulkRevokeSchema.php` - Field `session_ids`: Array JSON payload structural definitions
- `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageCreateSchema.php` - Field `direction`: Local enum definitions
- `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSettingsSchema.php` - Field `direction`: Local enum definitions

---

# 6. Group by Existing Rule Coverage

## A. Ready for `Primitive\StringRule`

## B. Ready for `Primitive\EmailRule`
- `Modules/Validation/Schemas/AuthLoginSchemaExample.php` (Field: `email`)

## C. Ready for existing semantic rules
None strictly identified as ready right now. `I18nCodeRule`, `RoleNameRule`, etc. require careful mapping to exact regex patterns, so they remain in the judgment queue.

## D. Not ready for current Primitive rules
All `v::intType`, `v::intVal`, `v::boolType`, `v::boolVal` usages across `app/Modules/` and `Modules/` due to strict/weak typing differences with `EntityIdRule` and `BooleanRule`.
All nullable helpers (`v::anyOf(v::nullType(), ...)`) due to potential structural drift.

## E. Should stay inline
Array shapes (`SessionBulkRevokeSchema`), Enums (`LanguageCreateSchema`, `LanguageUpdateSettingsSchema`), and Dates (`NotificationQuerySchema`, `AdminNotificationHistorySchema`).

---

# 7. File-Level Migration Priority

- **Group 1:** Schemas with exact bounded string matches (`ImageProfileUpdateSchema`, `LanguageCreateSchema`, `RateCreateSchema`, `CurrencyCreateSchema`, `CurrencyUpdateSchema`).
- **Group 2:** Schemas with safe email replacements (`AdminController`).
- **Group 3:** STOP here. All remaining files require judgment on bounds or should remain inline.

---

# 8. Final Recommendation

- **Immediate Execution:** Execute exactly bounded `StringRule` conversions and direct `EmailRule` replacements.
- **Policy Decisions Needed:** Make explicit policy decisions on whether to standardize on `intVal()` vs `intType()` before migrating numeric and boolean primitives.
- **Safe Stop Point:** Once EXACT string length bounds and simple emails are addressed, stop refactoring. Do not force enums or array schemas into abstractions.
