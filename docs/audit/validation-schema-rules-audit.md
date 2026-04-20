# JULES AUDIT PROMPT — AbstractSchema Validation Usage Review (Tightened)

This document provides an evidence-based, governance-ready validation audit of all `AbstractSchema` implementations.

## 1. Precise Counts for Repeated Patterns

### Required Positive Integer ID Patterns
- **Variant:** `v::intVal()->min(1)` — used **20** times
  - **Fields:** `fallback_language_id, id, key_id, language_id`
  - **Schemas (Sample):** `I18nDomainChangeCodeSchema, I18nDomainSetActiveSchema, I18nDomainUpdateMetadataSchema`
- **Variant:** `v::intType()->min(1)` — used **9** times
  - **Fields:** `id, language_id`
  - **Schemas (Sample):** `CurrencySetActiveSchema, CurrencyTranslationDeleteSchema, CurrencyTranslationUpsertSchema`
- **Variant:** `v::intType()->positive()` — used **6** times
  - **Fields:** `admin_id, permission_id`
  - **Schemas (Sample):** `DirectPermissionAssignSchema, DirectPermissionRevokeSchema, RoleAdminAssignSchema`
- **Variant:** `v::intVal()` — used **6** times
  - **Fields:** `admin_id, id`
  - **Schemas (Sample):** `AdminAddEmailSchema, AdminGetEmailSchema, AdminNotificationHistorySchema`
- **Variant:** `v::intType()->min(1), ValidationErrorCodeEnum::REQUIRED_FIELD` — used **2** times
  - **Fields:** `id`
  - **Schemas (Sample):** `ImageProfileDetailsSchema, ImageProfileSetActiveSchema`
- **Variant:** `v::stringType()->notEmpty()` — used **1** times
  - **Fields:** `session_id`
  - **Schemas (Sample):** `SessionRevokeSchema`

### Optional Positive Integer ID Patterns
- **Variant:** `v::optional(v::intVal())` — used **2** times
  - **Fields:** `admin_id, id`
  - **Schemas (Sample):** `AdminListSchema, NotificationQuerySchema`
- **Variant:** `v::optional(v::intVal()->min(1))` — used **1** times
  - **Fields:** `fallback_language_id`
  - **Schemas (Sample):** `LanguageCreateSchema`

### Boolean Flag Patterns
- **Variant:** `v::boolType()` — used **11** times
  - **Fields:** `is_active, is_allowed, is_system, requires_acceptance, requires_acceptance_default, requires_transparency`
- **Variant:** `v::optional(v::boolVal())` — used **5** times
  - **Fields:** `is_active, is_read`
- **Variant:** `v::boolVal()` — used **5** times
  - **Fields:** `is_active, is_enabled`
- **Variant:** `v::optional(v::boolType())` — used **3** times
  - **Fields:** `is_active, requires_transparency`
- **Variant:** `v::boolType(), ValidationErrorCodeEnum::REQUIRED_FIELD` — used **1** times
  - **Fields:** `is_active`

### Text/String Patterns (Sample of Top 5)
- **Variant:** `v::stringType()->notEmpty()->length(1, 50)` — used **6** times
- **Variant:** `v::optional(v::stringType()->length(0, 255))` — used **5** times
- **Variant:** `v::optional( v::stringType()->length(1, 128) )` — used **3** times
- **Variant:** `v::optional( v::stringType()->length(1, 255) )` — used **3** times
- **Variant:** `v::stringType()->length(1, 100)` — used **3** times

### Code/Identifier Patterns
- **Variant:** `I18nCodeRule::rule(1, 50)` — used **4** times
  - **Fields:** `code, new_code`
- **Variant:** `v::stringType()->length(1, 64)` — used **3** times
  - **Fields:** `setting_key`
- **Variant:** `CredentialInputRule::rule()` — used **2** times
  - **Fields:** `password`
- **Variant:** `v::stringType()->notEmpty()->length(3, 3)` — used **2** times
  - **Fields:** `code`
- **Variant:** `I18nCodeRule::rule(1, 128)` — used **2** times
  - **Fields:** `key_name`

## 2. Semantic-Family Grouping

### ID Family
- **Fields:** `id`, `admin_id`, `permission_id`, `role_id`, `key_id`, `fallback_language_id`
- **Validator Diversity:** Very high. Mix of `v::intType()->positive()`, `v::intVal()->min(1)`, and optional variants.
- **Acceptability:** Unacceptable. IDs have identical semantic constraints system-wide.
- **Semantic Rule Justified:** Yes. A unified primitive `PositiveEntityIdRule` is strictly required.

### Name/Title Family
- **Fields:** `name`, `display_name`, `title`
- **Validator Diversity:** High. Various min/max lengths. Sometimes `notEmpty()` is chained.
- **Acceptability:** Acceptable for length variations, but base string handling should be unified.
- **Semantic Rule Justified:** No deep semantic rule needed, but a parameterized primitive `RequiredStringRule(min, max)` is justified.

### Description/Meta Family
- **Fields:** `description`, `meta_title`, `meta_description`
- **Validator Diversity:** Usually `v::optional(v::stringType()->length(...))`.
- **Semantic Rule Justified:** Parameterized primitive `OptionalStringRule(min, max)`.

### Code/Identifier Family
- **Fields:** `code`, `new_code`, `domain_code`, `scope_code`, `key`
- **Validator Diversity:** Mix of inline regex (`/^[a-z][a-z0-9_.-]*$/`) and custom rules (`I18nCodeRule::rule()`).
- **Acceptability:** Unacceptable. Inline regex makes governance impossible.
- **Semantic Rule Justified:** Yes. `CodeRule` and `SlugRule` must centralize regex.

### Boolean State Family
- **Fields:** `is_active`, `is_enabled`, `is_system`, `is_allowed`
- **Validator Diversity:** Split between `v::boolType()` and `v::boolVal()`.
- **Semantic Rule Justified:** Yes. A primitive `BooleanRule` to enforce coercion policy.

## 3. Coercion-Sensitive Differences Analysis

The audit flagged inconsistencies like `v::intType()->positive()` vs `v::intVal()->min(1)` and `v::boolType()` vs `v::boolVal()`.

- **`intType()` vs `intVal()`:** `intType()` strictly requires an integer primitive from the JSON payload. `intVal()` coerces numeric strings (e.g. `"1"`). Given that API List/Query endpoints often accept GET query parameters (which arrive as strings), and Actions use JSON (which arrive as typed primitives), this difference might be partially intentional due to transport (GET vs POST).
  - **Status:** **Needs policy decision before extraction**. We must decide if the project expects strict typing universally (via middleware cast) or relies on `intVal()` to coerce.
- **`boolType()` vs `boolVal()`:** Similar to IDs. `boolType()` demands a JSON boolean (`true`/`false`), while `boolVal()` coerces string representation of booleans.
  - **Status:** **Needs policy decision before extraction**.

## 4. Error-Code Normalization Matrix

| Semantic Field Type | Common Validator Pattern | Current Error Codes Used | Consistency Status | Recommendation |
| --- | --- | --- | --- | --- |
| Required ID | `v::intType()->positive()` / `v::intVal()->min(1)` | `REQUIRED_FIELD`, `INVALID_VALUE` | Inconsistent | Standardize on `INVALID_VALUE` (or `REQUIRED_FIELD` if purely missing). A unified ID rule must enforce one error code internally if possible, or schemas must align. |
| Optional ID | `v::optional(v::intVal())` | `REQUIRED_FIELD` | Misaligned | Using `REQUIRED_FIELD` for an optional field is semantically wrong. Standardize on `INVALID_VALUE`. |
| Boolean Flag | `v::boolType()` / `v::boolVal()` | `INVALID_VALUE`, `REQUIRED_FIELD` | Inconsistent | Standardize on `INVALID_VALUE`. |
| Code/Identifier | Regex / `I18nCodeRule` | `INVALID_VALUE`, `REQUIRED_FIELD`, `INVALID_FORMAT` | Inconsistent | Standardize on `INVALID_FORMAT`. |

## 5. Primitive vs Semantic Reusable Rules Boundaries

### Primitive Rules
Reusable because they describe only **type/shape** without business context. They handle transport coercion centrally.
- `PositiveIntRule`: Handles integer validation and standardizes `intVal()` vs `intType()` policy.
- `OptionalPositiveIntRule`: Same, but optional.
- `RequiredStringRule`: Wraps `stringType()->notEmpty()->length(min, max)`.
- `BooleanRule`: Standardizes `boolType()` vs `boolVal()` policy.

### Semantic Rules
Reusable because they encode **business meaning**, strict formats, or domain bounds. They **should wrap** Primitive rules internally where possible.
- `I18nCodeRule`: Existing rule. Defines the specific regex for I18n identities.
- `SlugRule`: For URL-friendly identifiers. Wraps `RequiredStringRule` + regex.
- `EntityIdRule`: Semantic wrapper for `PositiveIntRule` explicitly signaling a database relationship.

## 6. "Approved Inline Usage" vs "Must Extract" Guidance

### Must be Extracted
- Any validation for `id`, `*_id`, `is_*`.
- Any string length validation that is repeated exactly 3+ times.
- Any regex used to validate a business code, slug, or technical name.

### May Remain Inline
- Highly specific date constraints (e.g., `v::dateTime()->min('now')` used in one schema).
- Localized array shape validations (e.g. Telegram Webhook payload validation).
- Business rules that have not yet demonstrated repetition across modules (Rule of 3).

## 7. Strengthened Freeze Rules

1. **ID Enforcement:** Any field ending in `_id` or named `id` MUST NOT use ad-hoc inline integer validation (`v::intType()...`). They MUST use `PositiveEntityIdRule` (or its optional variant).
2. **Identifier Regex:** Any code-like field (`code`, `*_code`, `key`, `slug`) MUST NOT define its own regex inline. If a regex is needed, an approved semantic identifier rule MUST be created or used.
3. **Boolean Coercion:** Boolean fields MUST follow the single explicit project policy (once decided via `BooleanRule`) based on approved input semantics. Inline `v::boolType()` or `v::boolVal()` is banned.
4. **Default to Primitives:** New schemas MAY ONLY introduce raw `Respect\Validation` chains when no approved primitive or semantic rule covers the shape.
5. **Justification:** Any new reusable rule class MUST be justified by either repeated use (Rule of 3) or stable semantic meaning (e.g., a core domain identifier).

## 8. Recommended First Extraction Batch

| Rule | Reason | Evidence Strength | Confidence |
| --- | --- | --- | --- |
| `PositiveEntityIdRule` | `id` and `*_id` validation is duplicated heavily, with inconsistent error codes and typing. | Very Strong (>20 occurrences) | **High** |
| `BooleanRule` | Fragmentation between `boolVal` and `boolType` exists across identical UI toggles. | Strong (>5 occurrences) | **High** |
| `CodeRule` | Inline regex `/^[a-z][a-z0-9_.-]*$/` is duplicated. Centralizing prevents divergence. | Medium (3 occurrences) | **Medium** |
| `RequiredStringRule` | `v::stringType()->length()` is ubiquitous, but extracting requires parameterization and risks scope bloat. | Strong (>10 occurrences) | **Low** |