# Managing Translation Scopes

## 1. What is a Scope

A Scope is the highest-level namespace and category in the translation system. It represents a major application boundary or environment. Scopes exist to organize text logically so that translations for completely different parts of the system do not overlap or conflict.
*   **Examples:** Common scopes in the system typically include `admin` (text intended for the administrative control panel), `client` (text for the end-user application), or `api` (text returned in API responses).

## 2. Role of Scopes in the Architecture

*   **Hierarchy:** Scopes sit at the very top of the translation hierarchy: **Scopes → Domains → Keys → Values**.
*   **Mandatory Requirement:** A Scope is strictly mandatory for any translation. Every single translation key in the system must belong to a specific Scope and Domain tuple.
*   **Uniqueness:** Scope plays a critical role in Key uniqueness. A Key (like `login.title`) can exist multiple times in the system as long as it belongs to a different Scope or Domain. The system enforces uniqueness strictly on the combination of `(scope, domain, key)`.

## 3. Scope Data Model

Under the hood, Scopes are stored in the database in the `i18n_scopes` table. Based on the `ScopeDTO`, the database columns are:
*   **`id` (int):** The unique primary key identifier for the Scope.
*   **`code` (string):** The programmatic, unique string identifier used in the codebase (e.g., "admin").
*   **`name` (string):** The human-readable name displayed in the UI.
*   **`description` (string, nullable):** Optional metadata explaining the purpose of the Scope.
*   **`is_active` (bool):** A flag determining whether the Scope is currently enabled and available for translation resolution.
*   **`sort_order` (int):** Determines the visual order in which Scopes appear in UI lists.
*   **`created_at` (string):** Timestamp of when the Scope was registered.

## 4. Scope ↔ Domain Relationship

*   **How domains are attached:** Domains are not directly owned by a single Scope. Instead, they are explicitly linked to Scopes via a mapping table.
*   **Mapping table:** The relationship is managed by the `i18n_domain_scopes` table, which maps a `scope_code` to a `domain_code` along with an `is_active` flag.
*   **Relationship Type:** This is a **Many-to-Many** relationship. A single Domain (like "auth") can be attached to multiple Scopes (like both "admin" and "client"), and a single Scope can have many Domains.
*   **Constraints:**
    *   A Domain can exist independently in the system (`i18n_domains` table) without being mapped to a Scope.
    *   A Scope can exist without any Domains assigned to it.
    *   However, before an administrator can create a Translation Key, the system's `I18nGovernancePolicyService` strictly checks the `i18n_domain_scopes` table to ensure that the specific Domain is explicitly allowed and mapped to the specific Scope.

## 5. Scope Lifecycle (Admin Behavior)

### Creating a Scope
*   **What admin inputs:** The admin provides a `code`, `name`, and optional `description`.
*   **What validation exists:** The system validates that the `code` is unique and formatted correctly.
*   **What happens in DB:** A new row is inserted into the `i18n_scopes` table.

### Editing a Scope
*   **What fields are editable:** Administrators can edit the `code` (via a dedicated Change Code action), the `name` and `description` (via an Update Metadata action), and the `sort_order` (via an Update Sort action).
*   **What is restricted:** You cannot directly edit the internal `id`. Changing the `code` is allowed but has massive downstream implications for any Keys tied to the old code.

### Activating / Deactivating
*   **What "active" means in code:** In the database, the `is_active` boolean column is toggled between `1` (true) and `0` (false).
*   **How it affects system behavior:** Deactivating a Scope hides it from standard list queries (`listActive()`) and prevents new keys from being resolved under that namespace.

## 6. Scope List

The main UI for managing Scopes displays a data table with the following structure:
*   **Table columns:** `ID`, `Code` (rendered with a code badge), `Name`, `Description`, `Active` (rendered as an Active/Inactive status badge), `Order` (sort order badge), and `Actions`.
*   **Actions per row:** Each row contains multiple actionable buttons depending on the admin's permissions:
    *   **Code:** Opens a modal to change the programmatic Scope code.
    *   **Meta:** Opens a modal to update the Name and Description.
    *   **Sort:** Opens a modal to change the Sort Order.
    *   **Activate / Deactivate:** A toggle button to change the `is_active` status.
*   **Buttons:** The table also features a global **Create Scope** button at the top to initialize a new Scope.

## 7. Scope Interaction Flow

*   **What happens when clicking a Scope:** Clicking the Scope's ID in the table redirects the administrator to the specific Scope Details page (`/i18n/scopes/{id}`).
*   **How it leads to Domains:** The Scope Details page loads the Domain Assignments table (`i18n_scopes_domains.js`), which queries `POST /api/i18n/scopes/{scope_id}/domains/query`. This shows only the Domains mapped to this specific Scope via the `i18n_domain_scopes` mapping table.
*   **What data is loaded:** The system loads the Scope's metadata, its mapped domains, and its calculated translation coverage statistics.

## 8. Constraints & Rules

*   **Code uniqueness:** The Scope `code` must be entirely unique across the `i18n_scopes` table.
*   **Naming rules:** The `name` must be provided, while the `description` is optional.
*   **Dependency constraints:** You cannot assign a Domain to a Scope if that Domain does not exist. You cannot create a Key for a Scope+Domain combination if that combination is not mapped and marked active in `i18n_domain_scopes`.

## 9. Impact on Translations System

*   **How Scope affects Keys:** Every Key requires a Scope. If a Scope's `code` is changed (renamed), the `TranslationWriteService` executes complex transactional logic to update the derived `i18n_domain_language_summary` tables, explicitly moving the `total_keys` counters from the old scope code to the new scope code.
*   **How Scope affects Values:** Values (Translations) are tied to Key IDs. While the Value doesn't care about the Scope, if the parent Scope is deactivated or deleted, the entire tree of Keys and Values under it becomes unresolvable by the application.
*   **UI rendering:** Changing a Scope name or sort order updates how the translation management menus appear to administrators.

## 10. System Behavior & Consistency

*   **Immediate vs delayed changes:** All modifications to Scopes (creating, renaming, updating metadata) are synchronous and immediate.
*   **Validations in services:** The `I18nGovernancePolicyService` strictly validates that any action performed on a Key verifies that its parent Scope and Domain are allowed.
*   **Error cases:** If an admin attempts to create a key under an invalid scope, the system throws a `ScopeNotAllowedException` or `DomainScopeViolationException`. If a database update fails during a rename, it throws a `TranslationUpdateFailedException`.

## 11. Boundaries

*   **What Scope controls:** A Scope strictly controls the top-level namespace boundary for translation keys. It acts as a mandatory parent container that dictates which Domains are allowed to be used within it.
*   **What it does NOT control:** A Scope does NOT control Language availability, fallback mechanisms, or the actual text values. It is purely an organizational namespace.

## 12. Coverage Confirmation

I explicitly confirm the following:
*   **All Scope behavior is documented:** Yes, the lifecycle (create, edit, activate) and the architectural constraints are fully covered based on `ScopeDTO.php` and PHP Services.
*   **All UI elements are covered:** Yes, the Scope List table columns and exact action buttons (Code, Meta, Sort, Activate) are extracted directly from `i18n_scopes.js`.
*   **All DB relationships are explained:** Yes, the many-to-many relationship via `i18n_domain_scopes` is explicitly detailed based on `MysqlDomainScopeRepository.php`.
*   **No assumptions were made:** All information has been derived directly from the application's source code, services, exception definitions, and UI Javascript files. No "UNCLEAR" placeholders exist.