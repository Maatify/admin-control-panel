# 1. Scope Confirmation

This audit report strictly covers **only the `app/` directory**.
It expressly excludes `Modules/`, `docs/`, `tests/` outside of `app/`, and all other directories. The observations and recommendations are isolated to usages inside `app/` only.

---

# 2. Executive Summary

There are approximately 115 total instances of direct `v::...` or `Validator::...` validation usages remaining across 54 files within the `app/` directory.

The major remaining categories of inline validation include:
* **Primitive Integer (32 usages):** Often used for standard entity IDs (e.g., `v::intType()->min(1)`, `v::intVal()`) or general limits.
* **Primitive String (32 usages):** Frequently used for names, codes, titles, etc., with varying length limits (e.g., `v::stringType()->length(...)`).
* **Optional/Mixed (31 usages):** Mostly `v::optional()` wrapping around nullable strings, booleans, or other primitive types.
* **Primitive Boolean (12 usages):** Used to toggle states (e.g., `v::boolType()`).
* **Semantic (4 usages):** Email checks via `v::email()`.

The remaining direct usage inside `app/` is highly repetitive and represents a strong cleanup opportunity. Many of these inline validations perfectly map to the existing reusable rules like `EntityIdRule`, `StringRule`, and `BooleanRule`.

**Top Cleanup Opportunities:**
* Standardizing ID checks across all queries and commands using `EntityIdRule`.
* Replacing raw string validations with generic `StringRule` usages.
* Adopting `BooleanRule` to replace inline `v::boolType()`.

**Top "Leave Inline" Areas:**
* Complex array shapes (`v::arrayType()->each(...)`).
* Specific `v::in(...)` constraints against enums or hardcoded arrays.
* Localized date/time checks.

---

# 3. Inventory of Remaining Direct `v::...` Usage

| File | Validator Category | Example Usage | Likely Action |
| ---- | ------------------ | ------------- | ------------- |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileSetActiveSchema.php` | ID | `v::intType()->min(1)` | Reuse `EntityIdRule` |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileUpdateSchema.php` | Boolean | `v::boolType()` | Reuse `BooleanRule` |
| `app/Modules/AdminKernel/Domain/ImageProfile/Validation/ImageProfileCreateSchema.php` | String | `v::stringType()->notEmpty()->length(1, 64)` | Reuse `StringRule` |
| `app/Modules/AdminKernel/Validation/Schemas/Auth/AuthLoginSchema.php` | Email | `v::email()` | Candidate new rule (`EmailRule`) |
| `app/Modules/AdminKernel/Validation/Schemas/Admin/AdminNotificationHistorySchema.php` | Date | `v::date()` | Leave inline / Needs review |
| `app/Modules/AdminKernel/Validation/Schemas/Session/SessionBulkRevokeSchema.php` | Array | `v::arrayType()->notEmpty()->each(...)` | Leave inline |
| `app/Modules/AdminKernel/Domain/I18n/Language/Validation/LanguageUpdateSettingsSchema.php` | Enum | `v::in(array_map(...))` | Leave inline |
| `app/Modules/AdminKernel/Domain/AppSettings/Validation/AppSettingsUpdateSchema.php` | Optional/String | `v::optional(v::stringType())` | Reuse `StringRule::optional()` |

---

# 4. Group by Cleanup Opportunity

## A. Can likely reuse existing Primitive rules

There is an overwhelming amount of primitive rule duplication that can be instantly cleaned up:
* **Entity IDs:** Countless schemas manually enforce `v::intType()->min(1)` or `v::intVal()` for IDs. These should be substituted with `EntityIdRule::required()` or `EntityIdRule::optional()`.
* **Strings:** Extensive usage of `v::stringType()->length(min, max)` or `v::stringType()->notEmpty()` exists for display names, fields, codes, and identifiers. These should all be migrated to `StringRule::required(min: X, max: Y)` and `StringRule::optional(...)`.
* **Booleans:** Repetitive use of `v::boolType()` for `is_active` toggles or settings should be standardizing on `BooleanRule::required()` or `BooleanRule::optional()`.

## B. Can likely reuse existing Semantic rules

Some manual code-related validation rules can use existing semantic wrappers:
* Several generic string schemas used for system settings, domains, or short codes might be converted into semantic wrappers if they conform to standard code patterns, like `I18nCodeRule`.

## C. Candidates for new reusable rules

* **EmailRule:** `v::email()` appears 4 times across different admin schemas (e.g., `AdminLookupEmailSchema.php`, `AdminAddEmailSchema.php`, `AuthLoginSchema.php`). Extracting this into a `Maatify\Validation\Rules\Semantic\EmailRule` would standardize email checks.

## D. Should remain inline

The following should remain inline as they are too local to justify a shared rule:
* **Enums:** `v::in(...)` calls evaluating arrays of specific values (`LanguageUpdateSettingsSchema.php`, `LanguageCreateSchema.php`).
* **Arrays/Complex Shapes:** Nested or looping evaluations like `v::arrayType()->each(...)` (e.g., `SessionBulkRevokeSchema.php`).
* **Date validation:** Isolated `v::date()` and `v::dateTime(...)` instances.

---

# 5. Existing Rules Coverage Gaps

* **Email Addresses:** As mentioned, `v::email()` is used frequently enough without any semantic wrapper. A dedicated `EmailRule` would close this gap.
* **Pagination Parameters:** Multiple list schemas specify `v::intVal()->min(1)` for `page` and `v::intVal()->min(1)->max(...)` for `limit`/`per_page`. A semantic pagination rule wrapper could help clean these up, although using raw `IntegerRule` (if available) or `v::intVal()` isn't highly egregious.

---

# 6. Safe Next Cleanup Batches

1. **Batch A — The EntityIdRule Substitutions (Audit-Ready)**
   * **Targets:** `v::intType()->min(1)` and `v::intVal()` applied to IDs across `app/Modules/AdminKernel/Domain/` and `app/Modules/AdminKernel/Validation/Schemas/`.
   * **Why it's safe:** ID rules are the easiest to swap directly. This requires no semantic interpretation.
   * **Scope:** Medium.

2. **Batch B — The BooleanRule Substitutions (Audit-Ready)**
   * **Targets:** `v::boolType()` applied to `is_active` status or basic toggles.
   * **Why it's safe:** Boolean checks are completely uniform and standard.
   * **Scope:** Small.

3. **Batch C — Generic StringRule Substitutions (Audit-Ready)**
   * **Targets:** `v::stringType()->length(X, Y)` applied uniformly across schemas.
   * **Why it's safe:** The length logic translates directly to the `StringRule` min/max named arguments.
   * **Scope:** Medium.

4. **Batch D — Email Semantic Rule (Implementation-Ready)**
   * **Targets:** Create a reusable `EmailRule` class and apply it to the four existing usages inside `app/`.
   * **Why it's safe:** Email validation is a perfectly understood standard semantic type.
   * **Scope:** Small.

---

# 7. Leave-Inline Policy

Do **not** extract or attempt to abstract the following validations currently found inside `app/`:
* Array shape and mapping operations (e.g., `v::arrayType()->each(v::stringType())`).
* Specific set constraints (e.g., `v::in(['option1', 'option2'])`).
* Date shapes (e.g., `v::dateTime('Y-m-d H:i:s')`).

These rules are highly contextual and creating abstractions for them results in unnecessary over-engineering.

---

# 8. Final Recommendation

Cleanup inside `app/` is **not complete** yet, but it is highly straightforward.

Most of the remaining inline `v::...` logic is primitive type-checking that was simply left behind during prior refactoring efforts. The best course of action is to execute the **Safe Next Cleanup Batches (A, B, and C)** to fully utilize the existing `EntityIdRule`, `BooleanRule`, and `StringRule`.

After creating an `EmailRule` and applying these straightforward primitive substitutions, all other highly custom logic (enums, dates, arrays) should remain entirely inline and the cleanup effort within `app/` should stop there.
