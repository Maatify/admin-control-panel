# JULES AUDIT PROMPT — AbstractSchema Validation Usage Review

## 1. Executive Summary

- **Total Schemas Analzyed:** 74 classes extending `AbstractSchema` were found.
- **Overall Consistency:** Validation style is mostly consistent but highly fragmented. There is significant duplication of raw `Respect\Validation` (i.e., `v::`) chains across many modules.
- **Top Repeated Rule Families:**
  - Required Entity IDs: `v::intType()->positive()` vs `v::intVal()->min(1)`.
  - Optional Entity IDs: `v::optional(v::intVal())` vs `v::optional(v::intVal()->min(1))`.
  - String Fields: `v::stringType()->length(X, Y)` for names and descriptions.
  - Booleans: `v::boolType()` vs `v::boolVal()`.
  - Codes/Slugs: Mix of regex like `v::stringType()->regex('/^[a-z][a-z0-9_.-]*$/')` and custom rules like `I18nCodeRule`.
- **Main Risks:** Duplication of validation logic makes it hard to change domain rules (e.g., if a "code" format needs to change). Error code mappings are sometimes inconsistent (`INVALID_VALUE` vs `REQUIRED_FIELD` for identical rules).
- **Highest-Value Opportunities:** Extracting primitive ID, String, and Boolean rules, followed by standardized identifier/slug rules.

## 2. Inventory of Schemas

| Schema Class | File Path | Module | Notes |
| --- | --- | --- | --- |
| `NotificationQuerySchema` | `./app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | AdminKernel | |
| `TelegramWebhookSchema` | `./app/Modules/AdminKernel/Validation/Schemas/TelegramWebhookSchema.php` | AdminKernel | |
| `PermissionMetadataUpdateSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Permissions/PermissionMetadataUpdateSchema.php` | AdminKernel | |
| `DirectPermissionAssignSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php` | AdminKernel | |
| `DirectPermissionRevokeSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionRevokeSchema.php` | AdminKernel | |
| `StepUpVerifySchema` | `./app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php` | AdminKernel | |
| `AuthLoginSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Auth/AuthLoginSchema.php` | AdminKernel | |
| `RoleAdminAssignSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminAssignSchema.php` | AdminKernel | |
| `RolePermissionUnassignSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionUnassignSchema.php` | AdminKernel | |
| `RoleToggleSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Roles/RoleToggleSchema.php` | AdminKernel | |
| `RoleAdminUnassignSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminUnassignSchema.php` | AdminKernel | |
| `RoleRenameSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Roles/RoleRenameSchema.php` | AdminKernel | |
| `RoleCreateSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Roles/RoleCreateSchema.php` | AdminKernel | |
| `RolePermissionAssignSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionAssignSchema.php` | AdminKernel | |
| `RoleMetadataUpdateSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Roles/RoleMetadataUpdateSchema.php` | AdminKernel | |
| `AdminLookupEmailSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Admin/AdminLookupEmailSchema.php` | AdminKernel | |
| `AdminListSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | AdminKernel | |
| `AdminNotificationHistorySchema` | `./app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | AdminKernel | |
| `AdminPreferenceGetSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceGetSchema.php` | AdminKernel | |
| `AdminNotificationReadSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationReadSchema.php` | AdminKernel | |
| `AdminCreateSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Admin/AdminCreateSchema.php` | AdminKernel | |
| `AdminPreferenceUpsertSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | AdminKernel | |
| `AdminEmailVerifySchema` | `./app/Modules/AdminKernel/Validation/Schemas/Admin/AdminEmailVerifySchema.php` | AdminKernel | |
| `AdminAddEmailSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Admin/AdminAddEmailSchema.php` | AdminKernel | |
| `AdminGetEmailSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Admin/AdminGetEmailSchema.php` | AdminKernel | |
| `SessionRevokeSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Session/SessionRevokeSchema.php` | AdminKernel | |
| `SessionBulkRevokeSchema` | `./app/Modules/AdminKernel/Validation/Schemas/Session/SessionBulkRevokeSchema.php` | AdminKernel | |
| `ContentDocumentTranslationsUpsertSchema` | `./app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTranslationsUpsertSchema.php` | Domain/ContentDocuments | |
| `ContentDocumentTypesCreateSchema` | `./app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php` | Domain/ContentDocuments | |
| `ContentDocumentVersionsCreateSchema` | `./app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentVersionsCreateSchema.php` | Domain/ContentDocuments | |
| `ContentDocumentTypesUpdateSchema` | `./app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesUpdateSchema.php` | Domain/ContentDocuments | |
| `ImageProfileSetActiveSchema` | `./app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileSetActiveSchema.php` | Domain/ImageProfile | |
| `ImageProfileCreateSchema` | `./app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | Domain/ImageProfile | |
| `ImageProfileDetailsSchema` | `./app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileDetailsSchema.php` | Domain/ImageProfile | |
| `ImageProfileUpdateSchema` | `./app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | Domain/ImageProfile | |
| `WebsiteUiThemeDetailsSchema` | `./app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDetailsSchema.php` | Domain/WebsiteUiTheme | |
| `WebsiteUiThemeUpdateSchema` | `./app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeUpdateSchema.php` | Domain/WebsiteUiTheme | |
| `WebsiteUiThemeEntityTypeSchema` | `./app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeEntityTypeSchema.php` | Domain/WebsiteUiTheme | |
| `WebsiteUiThemeCreateSchema` | `./app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeCreateSchema.php` | Domain/WebsiteUiTheme | |
| `WebsiteUiThemeDeleteSchema` | `./app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDeleteSchema.php` | Domain/WebsiteUiTheme | |
| `CurrencyTranslationDeleteSchema` | `./app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyTranslationDeleteSchema.php` | Domain/Currency | |
| `CurrencyCreateSchema` | `./app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyCreateSchema.php` | Domain/Currency | |
| `CurrencyUpdateSchema` | `./app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSchema.php` | Domain/Currency | |
| `CurrencyUpdateSortOrderSchema` | `./app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyUpdateSortOrderSchema.php` | Domain/Currency | |
| `CurrencySetActiveSchema` | `./app/Modules/AdminKernel/Domain/Currency/Validation/CurrencySetActiveSchema.php` | Domain/Currency | |
| `CurrencyTranslationUpsertSchema` | `./app/Modules/AdminKernel/Domain/Currency/Validation/CurrencyTranslationUpsertSchema.php` | Domain/Currency | |
| `I18nDomainUpdateSortSchema` | `./app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainUpdateSortSchema.php` | Domain/I18n | |
| `I18nDomainSetActiveSchema` | `./app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainSetActiveSchema.php` | Domain/I18n | |
| `I18nDomainUpdateMetadataSchema` | `./app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainUpdateMetadataSchema.php` | Domain/I18n | |
| `I18nDomainCreateSchema` | `./app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainCreateSchema.php` | Domain/I18n | |
| `I18nDomainChangeCodeSchema` | `./app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainChangeCodeSchema.php` | Domain/I18n | |
| `LanguageCreateSchema` | `./app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageCreateSchema.php` | Domain/I18n | |
| `LanguageUpdateSortOrderSchema` | `./app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSortOrderSchema.php` | Domain/I18n | |
| `LanguageUpdateNameSchema` | `./app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateNameSchema.php` | Domain/I18n | |
| `LanguageSetFallbackSchema` | `./app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageSetFallbackSchema.php` | Domain/I18n | |
| `LanguageSetActiveSchema` | `./app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageSetActiveSchema.php` | Domain/I18n | |
| `LanguageUpdateSettingsSchema` | `./app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSettingsSchema.php` | Domain/I18n | |
| `LanguageClearFallbackSchema` | `./app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageClearFallbackSchema.php` | Domain/I18n | |
| `LanguageUpdateCodeSchema` | `./app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateCodeSchema.php` | Domain/I18n | |
| `I18nScopeUpdateMetadataSchema` | `./app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeUpdateMetadataSchema.php` | Domain/I18n | |
| `I18nScopeChangeCodeSchema` | `./app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeChangeCodeSchema.php` | Domain/I18n | |
| `I18nScopeUpdateSortSchema` | `./app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeUpdateSortSchema.php` | Domain/I18n | |
| `I18nScopeSetActiveSchema` | `./app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeSetActiveSchema.php` | Domain/I18n | |
| `I18nScopeCreateSchema` | `./app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeCreateSchema.php` | Domain/I18n | |
| `LanguageTranslationValueUpsertSchema` | `./app/Modules/AdminKernel/Domain/I18n/LanguageTranslationValue/Validation/LanguageTranslationValueUpsertSchema.php` | Domain/I18n | |
| `LanguageTranslationValueDeleteSchema` | `./app/Modules/AdminKernel/Domain/I18n/LanguageTranslationValue/Validation/LanguageTranslationValueDeleteSchema.php` | Domain/I18n | |
| `TranslationKeyUpdateNameSchema` | `./app/Modules/AdminKernel/Domain/I18n/Keys/Validation/TranslationKeyUpdateNameSchema.php` | Domain/I18n | |
| `TranslationKeyCreateSchema` | `./app/Modules/AdminKernel/Domain/I18n/Keys/Validation/TranslationKeyCreateSchema.php` | Domain/I18n | |
| `TranslationKeyUpdateDescriptionSchema` | `./app/Modules/AdminKernel/Domain/I18n/Keys/Validation/TranslationKeyUpdateDescriptionSchema.php` | Domain/I18n | |
| `AppSettingsUpdateSchema` | `./app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php` | Domain/AppSettings | |
| `AppSettingsCreateSchema` | `./app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsCreateSchema.php` | Domain/AppSettings | |
| `AppSettingsSetActiveSchema` | `./app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsSetActiveSchema.php` | Domain/AppSettings | |
| `AuthLoginSchemaExample` | `./Modules/Validation/Schemas/AuthLoginSchemaExample.php` | Validation | |
| `SharedStringRequiredSchema` | `./Modules/Validation/Schemas/SharedStringRequiredSchema.php` | Validation | |

## 3. Real Validation Patterns Found

### A. Positive integer ID (Required)
**Observed pattern:** `v::intType()->positive()` or `v::intVal()->min(1)`
**Used in:**
- `DirectPermissionAssignSchema` (`permission_id`)
- `DirectPermissionRevokeSchema` (`permission_id`)
- `RoleAdminAssignSchema` (`admin_id`)
- `RolePermissionUnassignSchema` (`permission_id`)
- `RoleAdminUnassignSchema` (`admin_id`)
- `RolePermissionAssignSchema` (`permission_id`)
- `AdminNotificationHistorySchema` (`admin_id`)
- `AdminPreferenceGetSchema` (`admin_id`)

**Semantics:** Used for required entity identifiers (`id`, `permission_id`, `admin_id`, `key_id`, etc.).
**Recommendation:** Candidate reusable rule: `PositiveIntIdRule::rule()`

### B. Positive integer ID (Optional)
**Observed pattern:** `v::optional(v::intVal())` or `v::optional(v::intVal()->min(1))`
**Used in:**
- `NotificationQuerySchema` (`admin_id`)
- `AdminListSchema` (`id`)
- `LanguageCreateSchema` (`fallback_language_id`)

**Semantics:** Used for optional entity IDs (like filtering or optional relations).
**Recommendation:** Candidate reusable rule: `OptionalPositiveIntIdRule::rule()`

### C. Text/String Rules
**Observed pattern:** `v::stringType()->length(1, 128)` or `v::stringType()->length(0, 255)`
**Used in:**
- `RoleRenameSchema` (`name`): `v::stringType()
                    ->notEmpty()
                    ->length(3, 190)
                    ->regex('/^[a-z][a-z0-9_.-]*$/')`
- `RoleCreateSchema` (`name`): `v::stringType()
                    ->notEmpty()
                    ->length(3, 190)
                    ->regex('/^[a-z][a-z0-9_.-]*$/')`
- `AdminCreateSchema` (`display_name`): `\Respect\Validation\Validator::stringType()
                    ->notEmpty()
                    ->length(2, 100),
                ValidationErrorCodeEnum::INVALID_DISPLAY_NAME`
- `ContentDocumentTranslationsUpsertSchema` (`title`): `v::stringType()
                    ->notEmpty()
                    ->length(1, 255)`
- `ContentDocumentTranslationsUpsertSchema` (`meta_title`): `v::stringType()->length(0, 255)`
- `ContentDocumentTranslationsUpsertSchema` (`meta_description`): `v::stringType()->length(0, 5000)`
- `ContentDocumentTypesCreateSchema` (`key`): `v::stringType()
                    ->notEmpty()
                    ->length(3, 64)
                    ->regex('/^[a-z0-9\-]+$/')`
- `ContentDocumentVersionsCreateSchema` (`version`): `v::stringType()
                    ->notEmpty()
                    ->length(1, 32)
                    ->regex('/^[a-zA-Z0-9.\-_]+$/')`

**Semantics:** Standard text limits for names, descriptions, and meta values.
**Recommendation:** Candidate reusable rules: `RequiredStringRule::rule(int $min, int $max)` and `OptionalStringRule::rule(int $min, int $max)`

### D. Boolean Flags
**Observed pattern:** `v::boolType()` or `v::boolVal()`
**Used in:**
- `DirectPermissionAssignSchema` (`is_allowed`)
- `RoleToggleSchema` (`is_active`)
- `AdminPreferenceUpsertSchema` (`is_enabled`)
- `ContentDocumentTypesCreateSchema` (`requires_acceptance_default`)
- `ContentDocumentTypesCreateSchema` (`is_system`)

**Semantics:** Active/Inactive toggles and other binary states.
**Recommendation:** Candidate reusable rule: `BooleanRule::rule()`

### E. Code / Slug / Identifier
**Observed pattern:** Regex `/^[a-z][a-z0-9_.-]*$/` or existing `I18nCodeRule::rule()`
**Used in:**
- `StepUpVerifySchema` (`code`): `v::stringType()->notEmpty()`
- `I18nDomainChangeCodeSchema` (`new_code`): `I18nCodeRule::rule(1, 50)`
- `TranslationKeyCreateSchema` (`domain_code`): `I18nCodeRule::rule(1, 64)`

**Semantics:** Machine-readable codes, domain codes, roles.
**Recommendation:** Extract into `SlugRule::rule()` and `CodeRule::rule()`.

## 4. Inconsistencies Found

- **ID Validation:** Fragmentation between `v::intType()->positive()` and `v::intVal()->min(1)`. This is clearly an accidental inconsistency for the identical semantic requirement.
- **Boolean Validation:** Fragmentation between `v::boolType()` and `v::boolVal()`.
- **Error Codes:** For identical rules (like `v::intVal()->min(1)`), some schemas return `ValidationErrorCodeEnum::REQUIRED_FIELD` while others return `ValidationErrorCodeEnum::INVALID_VALUE` or `ValidationErrorCodeEnum::INVALID_FORMAT`.
- **Optional Array vs Empty:** Some schemas use `v::optional(v::arrayType())` vs just `v::arrayType()`.

## 5. Candidate Reusable Rules

### `PositiveIntIdRule`
- **Target:** Required integer ID fields.
- **Current Repeated Raw Validator:** `v::intType()->positive()` or `v::intVal()->min(1)`
- **Should be Parameterized:** No.

### `OptionalPositiveIntIdRule`
- **Target:** Optional integer ID fields.
- **Current Repeated Raw Validator:** `v::optional(v::intVal()->min(1))`
- **Should be Parameterized:** No.

### `RequiredStringRule` & `OptionalStringRule`
- **Target:** Text fields like name, description.
- **Current Repeated Raw Validator:** `v::stringType()->length(X, Y)` and `v::optional(v::stringType()->length(X, Y))`
- **Should be Parameterized:** Yes (`$min`, `$max`).

### `ActiveBooleanRule` / `BooleanRule`
- **Target:** `is_active` flags.
- **Current Repeated Raw Validator:** `v::boolType()` and `v::boolVal()`
- **Should be Parameterized:** No.

### `CodeRule`
- **Target:** Slugs, role names, setting keys.
- **Current Repeated Raw Validator:** `v::stringType()->regex('/^[a-z][a-z0-9_.-]*$/')`
- **Should be Parameterized:** Yes (`$min`, `$max`).

## 6. Proposed Rule Taxonomy

### Generic Primitive Rules
- `PositiveIntRule`
- `OptionalPositiveIntRule`
- `RequiredStringRule`
- `OptionalStringRule`
- `BooleanRule`

### Semantic Rules
- `EntityIdRule` (Alias or wrapper for PositiveIntRule)
- `OptionalEntityIdRule`
- `CodeRule`
- `SlugRule`
- `EmailRule`

## 7. Freeze Rules for Future Development

- **No Duplication:** Repeated validation chains must not be duplicated once a reusable semantic rule exists.
- **Standard IDs:** All ID-like required fields must use the standardized positive integer ID rule.
- **Standard Booleans:** All boolean fields must use the standardized Boolean rule rather than raw validation.
- **Centralize Regex:** Regex-based business identifiers must be centralized in semantic rule classes rather than duplicated inline.
- **Semantic Priority:** New schemas must prefer semantic rules over raw validator chains when the meaning is already known.

## 8. Extraction Priority Plan

### Phase 1 — Safe, High-Confidence Extraction
- `EntityIdRule` / `PositiveIntIdRule`
- `OptionalEntityIdRule`
- `BooleanRule`
- `OptionalBooleanRule`

### Phase 2 — Semantic Normalization
- `RequiredStringRule`
- `OptionalStringRule`
- Standardizing the error codes for these fields across modules.

### Phase 3 — Business-Sensitive Rules
- Standardizing the regex for `CodeRule` and `SlugRule`.
- Specific domain identifiers (Theme files, Provider IDs).

## 9. Misuse Prevention Rules

- **Do not** duplicate raw validator chains (`v::intVal()...`) already centralized in the `Rules` namespace.
- **Do not** use generic `stringType()->length(...)` for semantic codes when a dedicated `CodeRule` or `SlugRule` exists.
- **Do not** embed regex directly in schema classes; create a shared rule class.
- **Ensure** consistent error code usage (e.g. `INVALID_VALUE` vs `REQUIRED_FIELD`) for identical semantic types.

## 10. Concrete Implementation Proposal

**Proposed Folder Structure:**
Since there is already a `Modules/Validation/Rules/` namespace (e.g., `CredentialInputRule`), the new rules should be placed there:
`Modules/Validation/Rules/`

**Convention:**
- Create final classes with a static `rule()` method returning `Validatable`.
  ```php
  final class PositiveEntityIdRule
  {
      public static function rule(): Validatable
      {
          return v::intType()->positive();
      }
  }
  ```
- Schemas will call `PositiveEntityIdRule::rule()` instead of `v::intType()->positive()`.

## 11. Suggested First Extraction Set

The immediate first set to implement and replace in schemas:
1. `PositiveEntityIdRule`
2. `OptionalEntityIdRule`
3. `BooleanRule`
4. `OptionalBooleanRule`
5. `RequiredStringRule`
6. `CodeRule`
