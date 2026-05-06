# 1. Scope Confirmation

This inventory covers strictly the validation code within:
- `app/`
- `Modules/`

Excluded directories: `docs/`, `vendor/`, and unrelated tooling files.

# 2. Executive Totals

- **Unbounded string cases:** 12
- **Nullable string-helper cases:** 6
- **Semantic identifier-like string cases:** 9

# 3. Unbounded String Matrix

| File | Field / Location | Current Validator | Best Current Bucket | Note |
| ---- | ---------------- | ----------------- | ------------------- | ---- |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `status` | `v::optional(v::stringType())` | Needs explicit bounds | Filtering param. |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `channel` | `v::optional(v::stringType())` | Needs explicit bounds | Filtering param. |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `email` | `v::optional(v::stringType())` | Could map to existing semantic rule | Should use EmailRule::optional(). |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `notification_type` | `v::optional(v::stringType())` | Needs explicit bounds | |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | `notification_type` | `v::stringType()->notEmpty()` | Needs explicit bounds | |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | `channel_type` | `v::stringType()->notEmpty()` | Needs explicit bounds | |
| `app/Modules/AdminKernel/Validation/Schemas/Session/SessionRevokeSchema.php` | `session_id` | `v::stringType()->notEmpty()` | Needs new semantic family later | Session ID string format. |
| `app/Modules/AdminKernel/Validation/Schemas/Session/SessionBulkRevokeSchema.php` | `session_ids` | `each(v::stringType())` | Needs new semantic family later | Array of Session IDs. |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php` | `value` | `v::stringType()` | Could stay inline | Dynamic value, explicitly unbounded text. |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateCreateSchema.php` | `rate` | `v::stringType()->notEmpty()` | Could stay inline | Validated via regex later. |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSchema.php` | `rate` | `v::stringType()->notEmpty()` | Could stay inline | Validated via regex later. |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderCreateSchema.php` | `url` | `v::optional(v::stringType())` | Could map to existing semantic rule | Could use a URL semantic rule. |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSchema.php` | `url` | `v::optional(v::stringType())` | Could map to existing semantic rule | Could use a URL semantic rule. |


# 4. Nullable String / anyOf Matrix

| File | Field / Location | Current Validator | Why It Is Special | Suggested Direction |
| ---- | ---------------- | ----------------- | ----------------- | ------------------- |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `$nullableString` | closure `v::optional(v::anyOf(v::nullType(), ...))` | Reusable closure for nullable string with length. | Needs dedicated nullable string policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `allowed_mime_types` | `v::optional(v::anyOf(v::nullType(), v::stringType()))` | Direct anyOf usage. | Needs dedicated nullable string policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `notes` | `v::optional(v::anyOf(v::nullType(), v::stringType()))` | Direct anyOf usage. | Needs dedicated nullable string policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `variants` | `v::optional(v::anyOf(v::nullType(), v::stringType()))` | Direct anyOf usage. | Needs dedicated nullable string policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `$nullableString` | closure `v::anyOf(v::nullType(), ...)` | Reusable closure for nullable string with length. | Needs dedicated nullable string policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `allowed_mime_types`, `notes`, `variants` | `v::anyOf(v::nullType(), v::stringType())` | Direct anyOf usage. | Needs dedicated nullable string policy |

# 5. Semantic Identifier Matrix

| File | Field / Location | Current Validator | Closest Existing Rule | Exact Match? | Note |
| ---- | ---------------- | ----------------- | --------------------- | ------------ | ---- |
| `app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php` | `code` | `v::stringType()->notEmpty()` | None | No | MFA verification code. |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php` | `type_key` | `v::stringType()` | StringPatternRule | No | Unique machine name for document type. |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentVersionsCreateSchema.php` | `type_key` | `v::stringType()` | StringPatternRule | No | Unique machine name for document type. |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `code` | `length(1, 64)` | None | No | Machine readable code for image profile. |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `code` | `length(1, 64)` | None | No | Machine readable code for image profile. |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageCreateSchema.php` | `code` | `length(2, 10)` | I18nCodeRule | Yes | Very likely matches I18nCodeRule concept. |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php` | `group_key`, `setting_key` | `length(1, 64)` | StringPatternRule | No | App Settings keys. |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsCreateSchema.php` | `group_key`, `setting_key` | `length(1, 64)` | StringPatternRule | No | App Settings keys. |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsSetActiveSchema.php` | `group_key`, `setting_key` | `length(1, 64)` | StringPatternRule | No | App Settings keys. |

# 6. Ready-Next Candidates

- **Ready for existing semantic rules:**
  - `AdminListSchema.php` (`email`) -> Ready to use `EmailRule::optional()`.
  - `LanguageCreateSchema.php` (`code`) -> Could be verified against `I18nCodeRule`.

- **Ready if explicit bounds are approved:**
  - Notification schemas (`status`, `channel`, `notification_type`, `channel_type`) currently unbounded but likely require simple enum mapping or strict length primitive rules.

- **Not ready:**
  - `session_id`s, `type_key`s, and `group_key`s -> Need defined semantic rules first.
  - ImageProfile closures -> Need a defined nullable string primitive strategy before touching.
  - Settings `value` -> Intentionally unbounded.

# 7. Final Recommendation

- **Safest Next Batch:** The most isolated, safe change is updating `AdminListSchema` to use `EmailRule` and `LanguageCreateSchema` to use `I18nCodeRule` (or at least `StringRule`), since these map perfectly to existing concepts.
- **Needs Policy:** A firm architecture policy is required for `v::anyOf(v::nullType(), ...)` structures. We must decide whether to create a `NullableStringRule` primitive or allow inline `anyOf` to stay.
- **Stay Untouched:** `SessionRevokeSchema`'s `session_id`, `type_key` in Content Documents, and `AppSettings` keys should remain inline until new, explicit semantic rules (like `SessionIdRule` or `SystemKeyRule`) are authored.
