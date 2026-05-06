# 1. Scope Confirmation

This matrix explicitly covers only:
* `app/`
* `Modules/`

It strictly excludes `docs/`, `vendor/`, generated files, and unrelated tooling.

---

# 2. Executive Totals

- Total files reviewed with direct validation usage: 101
- Total **Do Now** cases: 5
- Total **Needs Policy** cases: 98
- Total **Leave Inline** cases: 9

---

# 3. Do Now Matrix

These cases are truly exact matches and ready for safe immediate implementation.

| File | Field / Location | Current Validation | Replacement | Why Exact / Safe |
| ---- | ---------------- | ------------------ | ----------- | ---------------- |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateCreateSchema.php` | `base_currency_code` | `v::stringType()->notEmpty()->length(3, 3)` | `StringRule::required(3, 3)` | Exact generic string bounds match |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateCreateSchema.php` | `target_currency_code` | `v::stringType()->notEmpty()->length(3, 3)` | `StringRule::required(3, 3)` | Exact generic string bounds match |

---

# 4. Needs Policy Matrix

These cases require explicit policy decisions on typing strictness or boundaries before migrating.

| File | Field / Location | Current Validation | Closest Target | Why Not Safe Yet |
| ---- | ---------------- | ------------------ | -------------- | ---------------- |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `status` | `v::optional(v::stringType()), ValidationErrorCodeEnum::REQUIRED_FIELD` | `StringRule::optional(...)` | Unbounded string validation needs explicit length bounds defined via policy |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `channel` | `v::optional(v::stringType()), ValidationErrorCodeEnum::REQUIRED_FIELD` | `StringRule::optional(...)` | Unbounded string validation needs explicit length bounds defined via policy |
| `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` | `admin_id` | `v::optional(v::intVal()), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Permissions/PermissionMetadataUpdateSchema.php` | `id` | `//                v::intType()->positive(),
//                ValidationErrorCodeEnum::REQUIRED_FIELD
//` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php` | `permission_id` | `v::intType()->positive(),
                ValidationErrorCodeEnum::INVALID_VALUE` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php` | `is_allowed` | `v::boolType(),
                ValidationErrorCodeEnum::INVALID_VALUE` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionRevokeSchema.php` | `permission_id` | `v::intType()->positive(),
                ValidationErrorCodeEnum::INVALID_VALUE` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php` | `code` | `v::stringType()->notEmpty(), ValidationErrorCodeEnum::REQUIRED_FIELD` | `StringRule::required(...)` | Unbounded string validation needs explicit max length bounds defined via policy |
| `app/Modules/AdminKernel/Validation/Schemas/Auth/StepUpVerifySchema.php` | `scope` | `v::optional(v::stringType()), ValidationErrorCodeEnum::REQUIRED_FIELD` | `StringRule::optional(...)` | Unbounded string validation needs explicit length bounds defined via policy |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminAssignSchema.php` | `admin_id` | `v::intType()->positive(),
                ValidationErrorCodeEnum::INVALID_VALUE` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionUnassignSchema.php` | `permission_id` | `v::intType()->positive(),
                ValidationErrorCodeEnum::INVALID_VALUE` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleToggleSchema.php` | `id` | `//                v::intType()->positive(),
            //                ValidationErrorCodeEnum::REQUIRED_FIELD
            //` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleToggleSchema.php` | `is_active` | `v::boolType(),
                ValidationErrorCodeEnum::INVALID_VALUE` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleAdminUnassignSchema.php` | `admin_id` | `v::intType()->positive(),
                ValidationErrorCodeEnum::INVALID_VALUE` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RolePermissionAssignSchema.php` | `permission_id` | `v::intType()->positive(),
                ValidationErrorCodeEnum::INVALID_VALUE` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Roles/RoleMetadataUpdateSchema.php` | `id` | `//                v::intType()->positive(),
//                ValidationErrorCodeEnum::REQUIRED_FIELD
//` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `page` | `v::optional(v::intVal()->min(1)), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `per_page` | `v::optional(v::intVal()->min(1)->max(100)), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `id` | `v::optional(v::intVal()), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminListSchema.php` | `email` | `v::optional(v::stringType()), ValidationErrorCodeEnum::INVALID_EMAIL` | `StringRule::optional(...)` | Unbounded string validation needs explicit length bounds defined via policy |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `admin_id` | `v::intVal(), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `page` | `v::optional(v::intVal()->min(1)), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `limit` | `v::optional(v::intVal()->min(1)), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | `notification_type` | `v::optional(v::stringType()), ValidationErrorCodeEnum::REQUIRED_FIELD` | `StringRule::optional(...)` | Unbounded string validation needs explicit length bounds defined via policy |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceGetSchema.php` | `admin_id` | `v::intVal(), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationReadSchema.php` | `id` | `v::intVal(), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | `admin_id` | `v::intVal(), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | `notification_type` | `v::stringType()->notEmpty(), ValidationErrorCodeEnum::REQUIRED_FIELD` | `StringRule::required(...)` | Unbounded string validation needs explicit max length bounds defined via policy |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminPreferenceUpsertSchema.php` | `channel_type` | `v::stringType()->notEmpty(), ValidationErrorCodeEnum::REQUIRED_FIELD` | `StringRule::required(...)` | Unbounded string validation needs explicit max length bounds defined via policy |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminEmailVerifySchema.php` | `emailId` | `v::intVal(), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminAddEmailSchema.php` | `id` | `v::intVal(), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminGetEmailSchema.php` | `id` | `v::intVal(), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Validation/Schemas/Session/SessionRevokeSchema.php` | `session_id` | `v::stringType()->notEmpty(), ValidationErrorCodeEnum::REQUIRED_FIELD` | `StringRule::required(...)` | Unbounded string validation needs explicit max length bounds defined via policy |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php` | `requires_acceptance_default` | `v::boolType(),
                ValidationErrorCodeEnum::INVALID_VALUE` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesCreateSchema.php` | `is_system` | `v::boolType(),
                ValidationErrorCodeEnum::INVALID_VALUE` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentVersionsCreateSchema.php` | `requires_acceptance` | `v::boolType(),
                ValidationErrorCodeEnum::INVALID_VALUE` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesUpdateSchema.php` | `requires_acceptance_default` | `v::boolType(),
                ValidationErrorCodeEnum::INVALID_VALUE` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `app/Modules/AdminKernel/Domain/ContentDocuments/Validation/ContentDocumentTypesUpdateSchema.php` | `is_system` | `v::boolType(),
                ValidationErrorCodeEnum::INVALID_VALUE` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileSetActiveSchema.php` | `id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileSetActiveSchema.php` | `is_active` | `v::boolType(),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `code` | `v::stringType()->notEmpty()->length(1, 64),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `Semantic Rules` | Semantic identifier-like fields need direct exact mappings rather than treating as generic text |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `allowed_mime_types` | `v::optional(v::anyOf(v::nullType(), v::stringType())), ValidationErrorCodeEnum::INVALID_FORMAT` | `StringRule::optional(...)` | Unbounded string validation needs explicit length bounds defined via policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `is_active` | `v::optional(v::boolType()), ValidationErrorCodeEnum::INVALID_FORMAT` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `notes` | `v::optional(v::anyOf(v::nullType(), v::stringType())), ValidationErrorCodeEnum::INVALID_FORMAT` | `StringRule::optional(...)` | Unbounded string validation needs explicit length bounds defined via policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `requires_transparency` | `v::optional(v::boolType()), ValidationErrorCodeEnum::INVALID_FORMAT` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | `variants` | `v::optional(v::anyOf(v::nullType(), v::stringType())), ValidationErrorCodeEnum::INVALID_FORMAT` | `StringRule::optional(...)` | Unbounded string validation needs explicit length bounds defined via policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileDetailsSchema.php` | `id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `id` | `v::intType()->min(1), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `code` | `v::stringType()->notEmpty()->length(1, 64), ValidationErrorCodeEnum::REQUIRED_FIELD` | `Semantic Rules` | Semantic identifier-like fields need direct exact mappings rather than treating as generic text |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `allowed_mime_types` | `v::anyOf(v::nullType(), v::stringType()), ValidationErrorCodeEnum::REQUIRED_FIELD` | `StringRule::optional(...)` | Unbounded string validation needs explicit length bounds defined via policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `is_active` | `v::boolType(), ValidationErrorCodeEnum::REQUIRED_FIELD` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `notes` | `v::anyOf(v::nullType(), v::stringType()), ValidationErrorCodeEnum::REQUIRED_FIELD` | `StringRule::optional(...)` | Unbounded string validation needs explicit length bounds defined via policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `requires_transparency` | `v::boolType(), ValidationErrorCodeEnum::REQUIRED_FIELD` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | `variants` | `v::anyOf(v::nullType(), v::stringType()), ValidationErrorCodeEnum::REQUIRED_FIELD` | `StringRule::optional(...)` | Unbounded string validation needs explicit length bounds defined via policy |
| `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDetailsSchema.php` | `id` | `v::intType()->min(1), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeUpdateSchema.php` | `id` | `v::intType()->min(1), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Domain/WebsiteUiTheme/Validation/WebsiteUiThemeDeleteSchema.php` | `id` | `v::intType()->min(1), ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Domain/I18n/Domain/Validation/I18nDomainUpdateSortSchema.php` | `position` | `v::intVal()->min(0),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageCreateSchema.php` | `code` | `v::stringType()->length(2, 10),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `Semantic Rules` | Semantic identifier-like fields need direct exact mappings rather than treating as generic text |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSortOrderSchema.php` | `sort_order` | `v::intVal()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateCodeSchema.php` | `code` | `v::stringType()->length(1, 32),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `Semantic Rules` | Semantic identifier-like fields need direct exact mappings rather than treating as generic text |
| `app/Modules/AdminKernel/Domain/I18n/Scope/Validation/I18nScopeUpdateSortSchema.php` | `position` | `v::intVal()->min(0),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php` | `setting_group` | `v::stringType()->length(1, 64),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `Semantic Rules` | Semantic identifier-like fields need direct exact mappings rather than treating as generic text |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php` | `setting_key` | `v::stringType()->length(1, 64),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `Semantic Rules` | Semantic identifier-like fields need direct exact mappings rather than treating as generic text |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php` | `setting_value` | `v::stringType(),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `StringRule::optional(...)` | Unbounded string validation needs explicit length bounds defined via policy |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsCreateSchema.php` | `setting_group` | `v::stringType()->length(1, 64),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `Semantic Rules` | Semantic identifier-like fields need direct exact mappings rather than treating as generic text |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsCreateSchema.php` | `setting_key` | `v::stringType()->length(1, 64),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `Semantic Rules` | Semantic identifier-like fields need direct exact mappings rather than treating as generic text |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsSetActiveSchema.php` | `setting_group` | `v::stringType()->length(1, 64),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `Semantic Rules` | Semantic identifier-like fields need direct exact mappings rather than treating as generic text |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsSetActiveSchema.php` | `setting_key` | `v::stringType()->length(1, 64),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `Semantic Rules` | Semantic identifier-like fields need direct exact mappings rather than treating as generic text |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateCreateSchema.php` | `provider_id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateCreateSchema.php` | `recorded_at` | `v::optional(v::stringType()->notEmpty()),
                ValidationErrorCodeEnum::INVALID_FORMAT` | `StringRule::required(...)` | Unbounded string validation needs explicit max length bounds defined via policy |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateSetActiveSchema.php` | `id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateSetActiveSchema.php` | `is_active` | `v::boolType(),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderSetActiveSchema.php` | `id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderSetActiveSchema.php` | `is_active` | `v::boolType(),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSortOrderSchema.php` | `id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSortOrderSchema.php` | `display_order` | `v::intType()->min(0),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderCreateSchema.php` | `description` | `v::optional(v::stringType()),
                ValidationErrorCodeEnum::INVALID_FORMAT` | `StringRule::optional(...)` | Unbounded string validation needs explicit length bounds defined via policy |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSortOrderSchema.php` | `id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSortOrderSchema.php` | `display_order` | `v::intType()->min(0),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderSoftDeleteSchema.php` | `id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateSoftDeleteSchema.php` | `id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSchema.php` | `id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSchema.php` | `rate` | `v::stringType()->notEmpty(),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `StringRule::required(...)` | Unbounded string validation needs explicit max length bounds defined via policy |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateUpdateSchema.php` | `recorded_at` | `v::optional(v::stringType()->notEmpty()),
                ValidationErrorCodeEnum::INVALID_FORMAT` | `StringRule::required(...)` | Unbounded string validation needs explicit max length bounds defined via policy |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSchema.php` | `id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/ProviderUpdateSchema.php` | `description` | `v::optional(v::stringType()),
                ValidationErrorCodeEnum::INVALID_FORMAT` | `StringRule::optional(...)` | Unbounded string validation needs explicit length bounds defined via policy |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyTranslationDeleteSchema.php` | `language_id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyCreateSchema.php` | `code` | `v::stringType()->notEmpty()->length(3, 3),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `Semantic Rules` | Semantic identifier-like fields need direct exact mappings rather than treating as generic text |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyCreateSchema.php` | `is_active` | `v::optional(v::boolType()),
                ValidationErrorCodeEnum::INVALID_FORMAT` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyUpdateSchema.php` | `id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyUpdateSchema.php` | `code` | `v::stringType()->notEmpty()->length(3, 3),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `Semantic Rules` | Semantic identifier-like fields need direct exact mappings rather than treating as generic text |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyUpdateSchema.php` | `is_active` | `v::boolType(),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyUpdateSortOrderSchema.php` | `id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyUpdateSortOrderSchema.php` | `display_order` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencySetActiveSchema.php` | `id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencySetActiveSchema.php` | `is_active` | `v::boolType(),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `BooleanRule` | Strict `boolType()` is not equivalent to current type-coercing `boolVal()` BooleanRule without a policy |
| `Modules/CurrencySlim/src/Admin/Domain/Validation/CurrencyTranslationUpsertSchema.php` | `language_id` | `v::intType()->min(1),
                ValidationErrorCodeEnum::REQUIRED_FIELD` | `EntityIdRule / PaginationRule` | Strict `intType()` vs type-coercing `intVal()` rules needs explicit mapping policy decision |

---

# 5. Leave Inline Matrix

The following categories should remain local and should not be abstracted at this time:

- **Enums (`v::in(...)`)**: Tightly coupled local rule arrays.
- **Array Shapes (`v::arrayType()->key(...)`, `v::arrayType()->each(...)`)**: Specific JSON structures.
- **Dates (`v::date()`, `v::dateTime()`)**: Highly local parsing logic.

- `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` - Field `from`: Specific local date parsing
- `app/Modules/AdminKernel/Validation/Schemas/NotificationQuerySchema.php` - Field `to`: Specific local date parsing
- `app/Modules/AdminKernel/Validation/Schemas/TelegramWebhookSchema.php` - Field `message`: Array shape configuration
- `app/Modules/AdminKernel/Validation/Schemas/Permissions/DirectPermissionAssignSchema.php` - Field `expires_at`: Specific local date parsing
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` - Field `from_date`: Specific local date parsing
- `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` - Field `to_date`: Specific local date parsing
- `app/Modules/AdminKernel/Validation/Schemas/Session/SessionBulkRevokeSchema.php` - Field `session_ids`: Array shape configuration
- `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageCreateSchema.php` - Field `direction`: Local enum/set definitions
- `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSettingsSchema.php` - Field `direction`: Local enum/set definitions

---

# 6. Internal Validation Module Cleanup

Review of the `Modules/Validation` directory itself:

### Do Now
- `Modules/Validation/Schemas/AuthLoginSchemaExample.php` - `email`: Replace `v::email()` with `EmailRule::required()`
- `Modules/Validation/Schemas/AuthLoginSchemaExample.php` - `email`: Replace `v::email()` with `EmailRule::required()`
- `Modules/Validation/Schemas/SharedStringRequiredSchema.php` - `field`: Replace `Validator::stringType()->notEmpty()->length($this->minLength, $this->maxLength)` with `StringRule::required($this->minLength, $this->maxLength)`

### Needs Policy

### Leave Inline
- All foundational primitive definitions (e.g. `v::email()` inside `EmailRule.php`) must remain directly using the Respect validator instances as they ARE the abstractions.

---

# 7. Execution Batches

### Batch A — `Modules/Validation` internal cleanup
- **Target Files:** `Modules/Validation/Schemas/AuthLoginSchemaExample.php`, `Modules/Validation/Schemas/SharedStringRequiredSchema.php`
- **Why Safe:** Updates examples and shared schema scaffolding to properly rely on internal Rule classes instead of direct `v::` usages.
- **Size:** Small

### Batch B — `app/` consumer replacements
- **Target Files:** `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminCreateSchema.php`
- **Why Safe:** Only replaces exactly-bounded text fields (e.g. `display_name`) with the generic `StringRule::required(2, 100)`.
- **Size:** Small

### Batch C — `Modules/*` consumer replacements
- **Target Files:** `Modules/ExchangeRatesSlim/src/Admin/Domain/Validation/RateCreateSchema.php`
- **Why Safe:** Only replaces perfectly mapped string lengths (`v::stringType()->notEmpty()->length(3, 3)`) with `StringRule::required(3, 3)`.
- **Size:** Small

---

# 8. Final Recommendation

- **Execute Immediately:** Proceed strictly with Batches A, B, and C to eliminate exact string match leaks and legacy static aliases.
- **Wait for Policy Decisions:** Do not execute numeric, boolean, nullable array structures, or semantic identifiers yet. They require explicit architectural policies mapping `intType()` and unbounded `stringType()` behaviors safely.
- **Safe Stop Point:** Stop all automated replacements once exactly-bounded text lengths are resolved. Leave dates, arrays, and enums inline intentionally.
