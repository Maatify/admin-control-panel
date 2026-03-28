# Managing Application Settings

## 1. What are Application Settings

Application Settings are dynamic, database-driven configurations that control the runtime behavior of the platform. They act as a configuration engine, allowing administrators to toggle features, adjust limits, and configure external integration logic directly from the control panel without requiring a developer to change code or restart the server.

*   **Difference between `.env` and App Settings:** The `.env` file represents static, infrastructure-level configuration (like database passwords or server ports) that must remain completely secure and is only changed during deployment. App Settings, conversely, represent dynamic business logic variables that are safe for administrators to modify on the fly.

## 2. Core Architecture

The architecture of an App Setting is built on a strict typed key-value pairing:

### Setting Key
*   **Unique identifier:** A setting is uniquely identified by the combination of its `group` and its `key`.
*   **Naming conventions:** They are typically namespaced using dot notation internally (e.g., `system.timezone`), but managed as distinct `setting_group` (e.g., "system") and `setting_key` (e.g., "timezone") components.

### Setting Value
*   **Data type:** Every setting explicitly declares a logical type via the `AppSettingValueTypeEnum`: `STRING`, `INT`, `BOOL`, or `JSON`.
*   **Storage format:** Regardless of the logical type, all values are stored in the database as raw text strings. The service layer handles casting the text back into the correct PHP type (e.g., casting `"1"` to `true`).

### Grouping
*   **Categories:** The `setting_group` column physically groups related settings together, allowing the system to query entire blocks of configuration at once (e.g., loading all `smtp` settings).

### Metadata
*   **Editable flag:** Settings have an `is_active` flag. Inactive settings are effectively ignored by the application.
*   **System flag:** Settings can be marked as `is_protected` (cannot be modified or disabled by the UI) and `is_whitelisted` (must be defined in the application code to be valid).

## 3. Storage Model

*   **Database table structure:** Settings are stored in the `app_settings` MySQL table. The primary columns are `id`, `setting_group`, `setting_key`, `setting_value`, `setting_type`, and `is_active`.
*   **Key → value mapping:** The table strictly maps the composite `setting_group` + `setting_key` to a single `setting_value`.
*   **JSON structure usage:** If a setting is declared with the `JSON` type, the `setting_value` column contains a serialized JSON string.
*   **Normalization:** There is no deep normalization; the table acts as a flat, highly indexed key-value registry.

## 4. Retrieval Flow (CRITICAL)

When the application needs a configuration value:
1.  **How settings are loaded:** The application code calls the `AppSettingsService::get()` or `AppSettingsService::getTyped()` method, passing the group and key (e.g., `getTyped('system', 'timezone')`).
2.  **Service responsible:** The `AppSettingsService` immediately checks the `AppSettingsWhitelistPolicy` to ensure the requested setting is actually known to the system.
3.  **Database Lookup:** The service then calls the `PdoAppSettingsRepository::findOne()`, which executes a direct SQL `SELECT` query against the `app_settings` table.
4.  **Casting:** If `getTyped()` was called, the service looks at the `setting_type` (e.g., `INT`) and casts the database string into an actual integer or array before returning it to the application.

## 5. Caching Strategy (VERY IMPORTANT)

*   **Cache layer:** Based on the codebase extraction (`PdoAppSettingsRepository`), there is **no caching layer** (like Redis or Memcached) currently implemented at the repository level for App Settings.
*   Every call to `AppSettingsService::getTyped()` results in a direct, real-time database query to ensure absolute consistency.

## 6. Update Flow

1.  **How admin updates a setting:** The administrator edits the setting's value via the UI and clicks save. The payload (group, key, value, type) is sent to `AppSettingsService::update()`.
2.  **Validation rules:** The service first checks the `AppSettingsProtectionPolicy` to ensure the setting isn't locked. It then calls `validateValue()`, which strictly checks the payload against the declared type (e.g., if the type is `INT`, `filter_var` is used to ensure the string represents a valid integer). If validation fails, an `InvalidAppSettingException` is thrown.
3.  **What happens after update:** The repository executes an `UPDATE app_settings SET setting_value = :value` SQL statement.
4.  **Immediate effect:** Because there is no caching layer to invalidate, the database is the sole source of truth. The update takes **immediate effect**. The very next time any code requests that setting, it receives the new value.

## 7. Runtime Behavior (CRITICAL)

App Settings are used across the platform to govern business rules dynamically.

*   **How settings affect system behavior:** They act as conditional gates within controllers and backend services.
*   **Examples:**
    *   **Toggles (BOOL):** A `feature.maintenance_mode` setting checked by middleware to block user logins.
    *   **Limits (INT):** A `security.max_login_attempts` setting queried by the authentication service before locking an account.
    *   **Configurations (JSON):** An `integration.payment_gateways` setting that stores an array of enabled providers for the checkout screen.

## 8. Admin Interaction Flow

Administrators manage these configurations through the specific App Settings list interface:
*   **Listing settings:** The table displays `ID`, `Group`, `Key`, `Value`, `Type`, `Status`, and `Actions`. It includes visual badges for context (e.g., a 🔒 lock icon for protected settings, and a ⚠️ warning icon for orphaned settings not recognized by the system whitelist).
*   **Editing settings:** Admins click an edit action on a row to modify the value and the logical type.
*   **Saving changes:** Submitting the form updates the database instantly, and the UI table refreshes.

## 9. Constraints & Rules

The system enforces strict governance over what can be modified:
*   **System-protected:** The `AppSettingsProtectionPolicy` hardcodes critical infrastructure keys (e.g., `system.base_url`, `system.environment`, `system.timezone`) that are completely blocked from modification via the UI. Attempting to change them throws an `AppSettingProtectedException`.
*   **Whitelist policy:** The `AppSettingsWhitelistPolicy` ensures the database cannot be filled with garbage data. Only setting keys that are explicitly declared in the application's internal whitelist can be created or queried.

## 10. Relationship with Other Modules

*   **Auth / Admin System:** Changes to security settings (like timeout limits) immediately impact how the authentication controllers process user sessions.
*   **Content Documents & Localization:** App Settings are completely decoupled from `LanguageCore` and `I18n`. They manage system logic, not localized text or legal document versions.

## 11. Boundaries

*   **What belongs to App Settings:** Dynamic feature toggles, administrative email routing addresses, pagination limits, and active integration flags.
*   **What MUST NOT be stored here:**
    *   **Secrets:** API tokens, database passwords, and encryption keys must never be stored here; they belong exclusively in `.env`.
    *   **Large content:** Large HTML blocks or legal policies must not be stored here; they belong in the `ContentDocuments` module.
    *   **Translations:** User-facing text strings belong in the `I18n` module.

## 12. Risks & Misuse (IMPORTANT)

*   **Dangers of misuse:** Because changes take immediate effect, entering an invalid configuration (e.g., setting a pagination limit to `0` or `1000000`) can instantly break UI layouts or cause database performance issues platform-wide.
*   **Wrong usage patterns:** Developers attempting to use App Settings to store user-specific preferences or large localized strings violate the module's architectural boundaries.

## 13. Coverage Confirmation

*   **Storage fully documented:** Yes, the `app_settings` MySQL structure and the text-based storage format are explicitly explained.
*   **Retrieval flow explained:** Yes, the `AppSettingsService::getTyped()` casting and database querying process is mapped.
*   **Cache explained:** Yes, explicitly confirmed that no caching layer exists in the current implementation; all queries are real-time DB reads.
*   **Runtime behavior covered:** Yes, explained how Boolean, Integer, and JSON values dynamically gate feature toggles and logic limits.
*   **No assumptions:** Yes, all policies (`AppSettingsProtectionPolicy`), enums (`AppSettingValueTypeEnum`), and UI badges (🔒, ⚠️) were extracted directly from the system's PHP and JavaScript codebase.