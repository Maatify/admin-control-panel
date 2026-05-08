# 1. Scope Confirmation

This matrix covers explicitly the validation code within:
- `app/`
- `Modules/`

It exclusively addresses unresolved groups (`intVal(...)`, unbounded `stringType(...)`, nullable string-helpers, and semantic identifier-like strings) to safely decide on the next implementation steps.

---

# 2. Executive Totals

- **`intVal(...)` cases:** 19
- **Unbounded string cases:** 10
- **Nullable string-helper cases:** 6
- **Semantic identifier-like string cases:** 9

---

# 3. `intVal(...)` Decision Matrix

| File | Field / Location | Current Validator | Best Direction | Why |
| ---- | ---------------- | ----------------- | -------------- | --- |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `admin_id` | `v::optional(v::intVal())` | Use existing EntityIdRule | Represents an entity ID. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `page` | `v::optional(v::intVal()->min(1))` | Use existing PaginationRule | Pagination parameter. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `per_page` | `v::optional(v::intVal()->min(1)->max(100))` | Use existing PaginationRule | Pagination parameter. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `id` | `v::optional(v::intVal())` | Use existing EntityIdRule | Represents an entity ID. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `admin_id` | `v::intVal()` | Use existing EntityIdRule | Represents an entity ID. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `page` | `v::optional(v::intVal()->min(1))` | Use existing PaginationRule | Pagination parameter. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `limit` | `v::optional(v::intVal()->min(1))` | Use existing PaginationRule | Pagination parameter. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceGetSchema.php` | `admin_id` | `v::intVal()` | Use existing EntityIdRule | Represents an entity ID. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationReadSchema.php` | `id` | `v::intVal()` | Use existing EntityIdRule | Represents an entity ID. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | `admin_id` | `v::intVal()` | Use existing EntityIdRule | Represents an entity ID. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminEmailVerifySchema.php` | `emailId` | `v::intVal()` | Use existing EntityIdRule | Represents an entity ID. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminAddEmailSchema.php` | `id` | `v::intVal()` | Use existing EntityIdRule | Represents an entity ID. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminGetEmailSchema.php` | `id` | `v::intVal()` | Use existing EntityIdRule | Represents an entity ID. |
| `app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainUpdateSortSchema.php` | `sort_order` | `v::intVal()->min(0)` | Needs separate sort/order policy | Specific ordering logic lacking shared primitive. |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSortOrderSchema.php` | `sort_order` | `v::intVal()->min(1)` | Needs separate sort/order policy | Specific ordering logic lacking shared primitive. |
| `app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeUpdateSortSchema.php` | `sort_order` | `v::intVal()->min(0)` | Needs separate sort/order policy | Specific ordering logic lacking shared primitive. |

---

# 4. Unbounded String Decision Matrix

| File | Field / Location | Current Validator | Best Direction | Why |
| ---- | ---------------- | ----------------- | -------------- | --- |
| `app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php` | `code` | `v::stringType()->notEmpty()` | Likely semantic, not generic | MFA verification code. |
| `app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php` | `scope` | `v::optional(v::stringType())` | Needs explicit bounds before migration | Scope string param. |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `status` | `v::optional(v::stringType())` | Needs explicit bounds before migration | Generic status filter. |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `channel` | `v::optional(v::stringType())` | Needs explicit bounds before migration | Generic channel filter. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `email` | `v::optional(v::stringType())` | Could map to existing semantic rule | Should map to `EmailRule`. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `notification_type` | `v::optional(v::stringType())` | Needs explicit bounds before migration | Generic type. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | `notification_type` | `v::stringType()->notEmpty()` | Needs explicit bounds before migration | Generic type. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | `channel_type` | `v::stringType()->notEmpty()` | Needs explicit bounds before migration | Generic channel. |
| `app/Modules/AdminKernel/Validation/Schemas/Session/SessionRevokeSchema.php` | `session_id` | `v::stringType()->notEmpty()` | Likely semantic, not generic | Session ID is an exact hashed string shape. |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php` | `value` | `v::stringType()` | Could stay inline | Intentionally unbounded value. |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateCreateSchema.php` | `rate` | `v::stringType()->notEmpty()` | Could stay inline | Handled by command-level regex. |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSchema.php` | `rate` | `v::stringType()->notEmpty()` | Could stay inline | Handled by command-level regex. |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderCreateSchema.php` | `url` | `v::optional(v::stringType())` | Likely semantic, not generic | Needs URL pattern rule. |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSchema.php` | `url` | `v::optional(v::stringType())` | Likely semantic, not generic | Needs URL pattern rule. |

---

# 5. Nullable String Helper Matrix

| File | Field / Location | Current Validator | Best Direction | Why |
| ---- | ---------------- | ----------------- | -------------- | --- |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `$nullableString` | `v::optional(v::anyOf(v::nullType(), ...))` | Needs nullable-string policy | Explicit nullable string architecture missing. |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `allowed_mime_types` | `v::optional(v::anyOf(v::nullType(), v::stringType()))` | Needs nullable-string policy | Generic un-bounded anyOf. |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `notes` | `v::optional(v::anyOf(v::nullType(), v::stringType()))` | Needs nullable-string policy | Generic un-bounded anyOf. |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `variants` | `v::optional(v::anyOf(v::nullType(), v::stringType()))` | Needs nullable-string policy | Generic un-bounded anyOf. |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `$nullableString` | `v::anyOf(v::nullType(), ...)` | Needs nullable-string policy | Explicit nullable string architecture missing. |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `allowed_mime_types`, `notes`, `variants` | `v::anyOf(v::nullType(), v::stringType())` | Needs nullable-string policy | Generic un-bounded anyOf. |

---

# 6. Semantic Identifier Matrix

| File | Field / Location | Current Validator | Closest Existing Target | Exact Match? | Why |
| ---- | ---------------- | ----------------- | ----------------------- | ------------ | --- |
| `app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php` | `code` | `v::stringType()->notEmpty()` | None | No | MFA verification code. |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php` | `type_key` / `key` | `v::stringType()->regex('/^[a-z0-9\-]+$/')` | SlugRule | Unclear | Looks like a slug, needs verification. |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentVersionsCreateSchema.php` | `version` | `v::stringType()->regex('/^[a-zA-Z0-9.\-_]+$/')` | StringPatternRule | No | Looks like version semantic. |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `code` | `length(1, 64)` | StringPatternRule | No | Machine readable code, bounds 1-64. |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `code` | `length(1, 64)` | StringPatternRule | No | Machine readable code, bounds 1-64. |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php` | `group_key`, `setting_key` | `length(1, 64)` | StringPatternRule | Unclear | Machine keys. Wait for rollout policy. |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsCreateSchema.php` | `group_key`, `setting_key` | `length(1, 64)` | StringPatternRule | Unclear | Machine keys. Wait for rollout policy. |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsSetActiveSchema.php` | `group_key`, `setting_key` | `length(1, 64)` | StringPatternRule | Unclear | Machine keys. Wait for rollout policy. |

---

# 7. Ready-Next Recommendations

## A. Safe next implementation candidates
- All `intVal(...)` cases representing entity IDs mapping to `EntityIdRule`.
- All pagination parameters mapping to `PaginationRule`.
- Mapping `AdminListSchema.php` `email` to `EmailRule`.

## B. Needs explicit policy before implementation
- Bounding logic for currently unbounded notification channels/types.
- Standardizing a `NullableStringRule` primitive for `ImageProfile` schemas.
- Strategy for sorting parameters (`sort_order`).

## C. Leave untouched for now
- Semantic identifiers (Session IDs, Document `type_key`s) lacking explicit families.
- Explicitly unbounded strings (`value` in AppSettings, `rate` in ExchangeRates).