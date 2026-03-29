# Managing Application Settings

## 1. What are Application Settings

Application Settings are dynamic configurations that control the runtime behavior of the platform. They act as a configuration engine, allowing administrators to toggle features, adjust limits, and configure external integration logic directly from the control panel without requiring a developer to intervene.

*   **Difference between static configuration and App Settings:** Core infrastructure configurations (like server ports) must remain completely secure and are only changed during deployment. App Settings, conversely, represent dynamic business logic variables that are safe for administrators to modify on the fly through the UI.

## 2. Core Architecture

The architecture of an App Setting is built on a strict typed key-value pairing:

### Setting Key
*   **Unique identifier:** A setting is uniquely identified by the combination of its Group and its Key.
*   **Naming conventions:** They are typically namespaced using dot notation internally (e.g., `system.timezone`), but managed as distinct Group (e.g., "system") and Key (e.g., "timezone") components.

### Setting Value
*   **Data type:** Every setting explicitly declares a logical type: Text, Integer, Boolean, or JSON.
*   **Storage format:** Regardless of the logical type, all values are stored and presented logically. The system handles converting the value into the correct format behind the scenes.

### Grouping
*   **Categories:** The Group categorization collects related settings together, allowing the system to query entire blocks of configuration at once (e.g., loading all `smtp` settings).

### Metadata
*   **Editable flag:** Settings have an active status flag. Inactive settings are effectively ignored by the application.
*   **System flag:** Settings can be marked as protected (cannot be modified or disabled by the UI) and whitelisted (must be recognized by the application code to be valid).

## 3. Storage Model

*   **Data structure:** Settings consist of properties such as ID, Group, Key, Value, Type, and Active Status.
*   **Key → value mapping:** The system uniquely maps the group and key to a single value.
*   **JSON structure usage:** If a setting is declared as JSON, it stores a structured JSON format.
*   **Normalization:** Settings are managed as a flat, high-performance registry.

## 4. Retrieval Flow

When the application needs a configuration value:
1.  **How settings are loaded:** The application retrieves the setting using its group and key.
2.  **Verification:** The system verifies the setting against a strict whitelist to ensure the requested setting is actually known to the system.
3.  **System lookup:** The system performs an immediate lookup to retrieve the current value.
4.  **Casting:** The system formats the retrieved value according to its defined type before using it.

## 5. Caching Strategy

*   **Cache layer:** There is no caching layer implemented for App Settings.
*   Every request performs a direct, real-time check to ensure absolute consistency.

## 6. Update Flow

1.  **How admin updates a setting:** The administrator edits the setting's value via the UI and clicks save. The information is processed by the system.
2.  **Validation rules:** The system first verifies that the setting isn't locked. It then strictly validates the provided value against the declared type (e.g., ensuring text entered for an integer setting is actually a number). If validation fails, the system blocks the update and displays an error.
3.  **What happens after update:** The system instantly updates the value.
4.  **Immediate effect:** Because there is no caching layer, the system serves as the absolute source of truth. The update takes immediate effect. The very next time the platform requests that setting, it receives the new value.

## 7. Runtime Behavior

App Settings are used across the platform to govern business rules dynamically.

*   **How settings affect system behavior:** They act as conditional gates within the platform.
*   **Examples:**
    *   **Toggles (Boolean):** A `feature.maintenance_mode` setting used to block user logins.
    *   **Limits (Integer):** A `security.max_login_attempts` limit applied before locking an account.
    *   **Configurations (JSON):** An `integration.payment_gateways` setting that stores an array of enabled providers for the checkout screen.

## 8. Admin Interaction Flow

Administrators manage these configurations through the specific App Settings list interface:
*   **Table Columns:** The table displays ID, Group, Key, Value, Type, Status, and Actions. It includes visual badges for context (e.g., a 🔒 lock icon for protected settings, and a ⚠️ warning icon for orphaned settings not recognized by the system whitelist).
*   **Editing settings:** Admins click an edit action on a row to modify the value and the logical type.
*   **Saving changes:** Submitting the form updates the system instantly, and the UI table refreshes.

## 9. Constraints & Rules

The system enforces strict governance over what can be modified:
*   **System-protected:** The system hardcodes critical infrastructure settings (e.g., `system.base_url`) that are completely blocked from modification via the UI. Attempting to change them will be blocked by the system.
*   **Whitelist policy:** The system strictly ensures that invalid or unrecognized settings cannot be created. Only setting keys that are explicitly declared in the application's internal whitelist can be created or queried.

## 10. Relationship with Other Modules

*   **Auth / Admin System:** Changes to security settings (like timeout limits) immediately impact how the platform processes user sessions.
*   **Content Documents & Localization:** App Settings are completely decoupled from Languages and Translations. They manage system logic, not localized text or legal document versions.

## 11. Boundaries

*   **What belongs to App Settings:** Dynamic feature toggles, administrative email routing addresses, pagination limits, and active integration flags.
*   **What MUST NOT be stored here:**
    *   **Secrets:** API tokens, database passwords, and encryption keys must never be stored here; they belong exclusively in core infrastructure configuration.
    *   **Large content:** Large HTML blocks or legal policies must not be stored here; they belong in the Content Documents module.
    *   **Translations:** User-facing text strings belong in the Translations module.

## 12. Risks & Misuse

*   **Dangers of misuse:** Because changes take immediate effect, entering an invalid configuration (e.g., setting a pagination limit to `0` or `1000000`) can instantly break UI layouts or cause performance issues platform-wide.
*   **Wrong usage patterns:** Administrators attempting to use App Settings to store user-specific preferences or large localized strings violate the module's architectural boundaries.
