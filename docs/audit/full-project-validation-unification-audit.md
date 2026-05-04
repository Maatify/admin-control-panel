# 1. Scope Confirmation

This audit explicitly covers:
* `app/`
* `Modules/`

It explicitly excludes `docs/`, `vendor/`, generated files, and unrelated tooling.

---

# 2. Executive Summary

- Total files containing validation logic: 86
- Total files already aligned with the new validation direction: 22
- Total files still using old/direct validation style: 64
- Main remaining unification gaps: `v::stringType()`, `v::boolType()`, `v::intType()`, `v::intVal()` used inline instead of `StringRule`, `BooleanRule`, and `EntityIdRule` or `PaginationRule`.
- Top migration opportunities: Convert repeated inline primitive typings into their respective Primitive rule usages, especially in schemas like `AdminNotificationHistorySchema`, `SessionRevokeSchema`, etc.

---

# 3. Validation Usage Categories

## A. Already aligned with dedicated validation module direction
These files strictly use `Maatify\Validation\Rules`.
- `app/Modules/AdminKernel/Validation/Schemas/Auth/AuthLoginSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleRenameSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleCreateSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminLookupEmailSchema.php`
- `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeEntityTypeSchema.php`
- `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeCreateSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainSetActiveSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainUpdateMetadataSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainCreateSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainChangeCodeSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateNameSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageSetFallbackSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageSetActiveSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageClearFallbackSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeUpdateMetadataSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeChangeCodeSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeSetActiveSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeCreateSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/LanguageTranslationValue/Validation/LanguageTranslationValueDeleteSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Keys/Validation/TranslationKeyUpdateNameSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Keys/Validation/TranslationKeyCreateSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Keys/Validation/TranslationKeyUpdateDescriptionSchema.php`

## B. Uses reusable validation, but still old-style / legacy-style (Mixed)
These files use some primitive/semantic rules but still rely heavily on direct `v::...` calls.
- `app/Modules/AdminKernel/Validation/Schemas/Permissions/PermissionMetadataUpdateSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleMetadataUpdateSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminCreateSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminAddEmailSchema.php`
- `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTranslationsUpsertSchema.php`
- `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeUpdateSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainUpdateSortSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageCreateSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSortOrderSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSettingsSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateCodeSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeUpdateSortSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/LanguageTranslationValue/Validation/LanguageTranslationValueUpsertSchema.php`
- `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsCreateSchema.php`
- `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsSetActiveSchema.php`
- `Modules/Validation/Schemas/AuthLoginSchemaExample.php`
- `Modules/Validation/Schemas/SharedListQuerySchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderCreateSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSchema.php`
- `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyCreateSchema.php`
- `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyUpdateSchema.php`
- `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyTranslationUpsertSchema.php`

## C. Direct inline validation that should be migrated
These files solely rely on direct inline validation (`v::...` or `Validator::...`) for types that already have a primitive rule (String, Int, Boolean, Email).
- `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionRevokeSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminAssignSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionUnassignSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleToggleSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminUnassignSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionAssignSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceGetSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationReadSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminEmailVerifySchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminGetEmailSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Session/SessionRevokeSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Session/SessionBulkRevokeSchema.php`
- `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php`
- `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentVersionsCreateSchema.php`
- `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesUpdateSchema.php`
- `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileSetActiveSchema.php`
- `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php`
- `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileDetailsSchema.php`
- `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php`
- `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDetailsSchema.php`
- `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDeleteSchema.php`
- `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php`
- `app/Modules/AdminKernel/Http/Controllers/Api/Admin/AdminController.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateCreateSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateSetActiveSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderSetActiveSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSortOrderSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSortOrderSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderSoftDeleteSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateSoftDeleteSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSchema.php`
- `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyTranslationDeleteSchema.php`
- `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyUpdateSortOrderSchema.php`
- `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencySetActiveSchema.php`

## D. Direct inline validation that should likely remain inline
These files use `v::...` for specific array shapes, enums, or specific date formats.
- `app/Modules/AdminKernel/Validation/Schemas/TelegramWebhookSchema.php`
- `Modules/Validation/Schemas/SharedStringRequiredSchema.php`

---

# 4. Full Inventory of “Needs Migration” Cases

| File | Current Validation Style | Suggested Target |
| ---- | ------------------------ | ---------------- |
| `app/Modules/AdminKernel/Validation/Schemas/Permissions/PermissionMetadataUpdateSchema.php` | Mixed (Rules + Inline) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleMetadataUpdateSchema.php` | Mixed (Rules + Inline) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | Mixed (Rules + Inline) | Primitive\EntityIdRule / PaginationRule, Primitive\StringRule, Leave inline (date/time) |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminCreateSchema.php` | Mixed (Rules + Inline) | Primitive\StringRule |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | Mixed (Rules + Inline) | Primitive\EntityIdRule / PaginationRule, Primitive\StringRule |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminAddEmailSchema.php` | Mixed (Rules + Inline) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTranslationsUpsertSchema.php` | Mixed (Rules + Inline) | Primitive\StringRule |
| `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeUpdateSchema.php` | Mixed (Rules + Inline) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainUpdateSortSchema.php` | Mixed (Rules + Inline) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageCreateSchema.php` | Mixed (Rules + Inline) | Primitive\StringRule, Leave inline (enum) |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSortOrderSchema.php` | Mixed (Rules + Inline) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSettingsSchema.php` | Mixed (Rules + Inline) | Leave inline (enum) |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateCodeSchema.php` | Mixed (Rules + Inline) | Primitive\StringRule |
| `app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeUpdateSortSchema.php` | Mixed (Rules + Inline) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Domain/I18n/LanguageTranslationValue/Validation/LanguageTranslationValueUpsertSchema.php` | Mixed (Rules + Inline) | Primitive\StringRule |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsCreateSchema.php` | Mixed (Rules + Inline) | Primitive\StringRule |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsSetActiveSchema.php` | Mixed (Rules + Inline) | Primitive\StringRule |
| `Modules/Validation/Schemas/AuthLoginSchemaExample.php` | Mixed (Rules + Inline) | Primitive\EmailRule |
| `Modules/Validation/Schemas/SharedListQuerySchema.php` | Mixed (Rules + Inline) | Leave inline (array shape), Leave inline (date/time) |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderCreateSchema.php` | Mixed (Rules + Inline) | Primitive\StringRule |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSchema.php` | Mixed (Rules + Inline) | Primitive\EntityIdRule / PaginationRule, Primitive\StringRule |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyCreateSchema.php` | Mixed (Rules + Inline) | Primitive\BooleanRule, Primitive\StringRule |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyUpdateSchema.php` | Mixed (Rules + Inline) | Primitive\EntityIdRule / PaginationRule, Primitive\BooleanRule, Primitive\StringRule |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyTranslationUpsertSchema.php` | Mixed (Rules + Inline) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule, Primitive\StringRule, Leave inline (date/time) |
| `app/Modules/AdminKernel/Validation/Schemas/TelegramWebhookSchema.php` | Direct Inline (`v::`) | Leave inline (array shape) |
| `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule, Primitive\BooleanRule |
| `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionRevokeSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php` | Direct Inline (`v::`) | Primitive\StringRule |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminAssignSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionUnassignSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleToggleSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule, Primitive\BooleanRule |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminUnassignSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionAssignSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule, Primitive\StringRule |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceGetSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationReadSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminEmailVerifySchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminGetEmailSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Validation/Schemas/Session/SessionRevokeSchema.php` | Direct Inline (`v::`) | Primitive\StringRule |
| `app/Modules/AdminKernel/Validation/Schemas/Session/SessionBulkRevokeSchema.php` | Direct Inline (`v::`) | Primitive\StringRule, Leave inline (array shape) |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php` | Direct Inline (`v::`) | Primitive\BooleanRule, Primitive\StringRule |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentVersionsCreateSchema.php` | Direct Inline (`v::`) | Primitive\BooleanRule, Primitive\StringRule |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesUpdateSchema.php` | Direct Inline (`v::`) | Primitive\BooleanRule |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileSetActiveSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule, Primitive\BooleanRule |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule, Primitive\BooleanRule, Primitive\StringRule |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileDetailsSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule, Primitive\BooleanRule, Primitive\StringRule |
| `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDetailsSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDeleteSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php` | Direct Inline (`v::`) | Primitive\StringRule |
| `app/Modules/AdminKernel/Http/Controllers/Api/Admin/AdminController.php` | Direct Inline (`v::`) | Primitive\EmailRule |
| `Modules/Validation/Schemas/SharedStringRequiredSchema.php` | Direct Inline (`v::`) | Leave inline |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateCreateSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule, Primitive\StringRule |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateSetActiveSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule, Primitive\BooleanRule |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderSetActiveSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule, Primitive\BooleanRule |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSortOrderSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSortOrderSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderSoftDeleteSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateSoftDeleteSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule, Primitive\StringRule |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyTranslationDeleteSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyUpdateSortOrderSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencySetActiveSchema.php` | Direct Inline (`v::`) | Primitive\EntityIdRule / PaginationRule, Primitive\BooleanRule |

---

# 5. Group by Migration Type

## A. Replace with existing Primitive rules
- `app/Modules/AdminKernel/Validation/Schemas/Permissions/PermissionMetadataUpdateSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleMetadataUpdateSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminCreateSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminAddEmailSchema.php`
- `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTranslationsUpsertSchema.php`
- `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeUpdateSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainUpdateSortSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageCreateSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSortOrderSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateCodeSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeUpdateSortSchema.php`
- `app/Modules/AdminKernel/Domain/I18n/LanguageTranslationValue/Validation/LanguageTranslationValueUpsertSchema.php`
- `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsCreateSchema.php`
- `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsSetActiveSchema.php`
- `Modules/Validation/Schemas/AuthLoginSchemaExample.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderCreateSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSchema.php`
- `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyCreateSchema.php`
- `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyUpdateSchema.php`
- `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyTranslationUpsertSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionRevokeSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminAssignSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionUnassignSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleToggleSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminUnassignSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionAssignSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceGetSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationReadSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminEmailVerifySchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminGetEmailSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Session/SessionRevokeSchema.php`
- `app/Modules/AdminKernel/Validation/Schemas/Session/SessionBulkRevokeSchema.php`
- `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php`
- `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentVersionsCreateSchema.php`
- `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesUpdateSchema.php`
- `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileSetActiveSchema.php`
- `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php`
- `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileDetailsSchema.php`
- `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php`
- `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDetailsSchema.php`
- `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDeleteSchema.php`
- `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php`
- `app/Modules/AdminKernel/Http/Controllers/Api/Admin/AdminController.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateCreateSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateSetActiveSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderSetActiveSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSortOrderSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSortOrderSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderSoftDeleteSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateSoftDeleteSchema.php`
- `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSchema.php`
- `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyTranslationDeleteSchema.php`
- `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyUpdateSortOrderSchema.php`
- `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencySetActiveSchema.php`

## B. Replace with existing Semantic rules
Currently, most semantic rules are either already extracted or inline strings just need the `StringRule`. If an inline string acts as a 'Code' or 'Identifier', it can be migrated to `Semantic\I18nCodeRule` or `WebsiteUiThemeIdentifierRule`.

## C. Convert repeated raw regexes to shared pattern usage
Any regex such as `/^[a-z][a-z0-9_.-]*$/` used in `ContentDocumentTypesCreateSchema` could be evaluated for a semantic rule.

## D. Local validation helpers/closures that should be replaced

## E. Cases that still should remain inline
- Array shape validation (`v::arrayType()->key(...)`)
- Enums (`v::in(...)`)
- Dates (`v::date()`, `v::dateTime()`)
- Highly specific business logic exceptions

---

# 6. Legacy/Outdated API Usage

- Full `\Respect\Validation\Validator::stringType()` is still used in `AdminCreateSchema.php`.
- `Respect\Validation\Validator as v` is widespread but primarily should be replaced by rules when validating standard primitives.

---

# 7. Proposed Migration Plan

### Batch 1 — Remaining safe Primitive rule replacements
- **Target:** Replace inline `v::stringType()`, `v::boolType()`, `v::intType()` with `StringRule`, `BooleanRule`, and `EntityIdRule` across all `*Schema.php` files.
- **Size:** Large (touches many schemas).
- **Safety:** Very safe. It's a 1-to-1 replacement for scalar validations.
- **Timeline:** Now.

### Batch 2 — Replace local validator helpers with shared rules
- **Target:** Remove closure helpers like `$nullableString` and `$nullableInt` in `ImageProfileCreateSchema.php`.
- **Size:** Small.
- **Safety:** Safe. `StringRule::optional()` covers this logic perfectly.
- **Timeline:** Now.

### Batch 3 — Replace legacy rule/API replacements
- **Target:** Replace usages of `\Respect\Validation\Validator::stringType()` (e.g., in `AdminCreateSchema.php`).
- **Size:** Small.
- **Safety:** Very safe.
- **Timeline:** Now.

### Batch 4 — Review remaining inline-only cases and stop
- **Target:** Ensure `v::in()`, `v::arrayType()`, and `v::date()` are properly scoped and documented to remain inline.
- **Size:** Small.
- **Timeline:** Later.

---

# 8. Leave-Inline Policy

Do **not** extract rules for:
1. **Enums (`v::in(...)`)**: Each enum is local to the domain and extracting a reusable rule per enum creates unnecessary overhead.
2. **Array Shapes (`v::arrayType()`)**: Nesting is highly specific to a single endpoint's payload.
3. **Dates (`v::date(...)`)**: While `DateRangeRule` exists, singular dates are fine inline.
4. **Specific Business Constraints**: Like `v::regex()` for a one-off field that won't be reused across the application.

---

# 9. Final Recommendation

- **Migrate Next:** Execute Batch 1 and Batch 2 immediately. Convert the scattered primitive `v::...` calls into `Primitive\StringRule`, `Primitive\BooleanRule`, etc., and remove local closures.
- **Normalize Project-Wide:** Eliminate the full namespace static call `\Respect\Validation\Validator::...` entirely.
- **Remain Inline:** Enums, complex array structures, and specific date/time validation.
- **Stop Point:** Once primitives and semantics are unified, stop refactoring. Do not attempt to over-engineer enums or array payload shapes into dedicated reusable objects unless they appear 3+ times.
