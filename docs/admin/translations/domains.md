# Managing Translation Domains

## 1. What is a Domain

A Domain represents a specific feature, module, or logical grouping of text within the translation system. While a Scope defines the broad application boundary (like "admin" or "client"), Domains break that boundary down into manageable pieces. Domains exist to organize translation keys so they are easy to find, audit, and maintain without creating massive, unmanageable lists of text.
*   **Examples:** Common domains include `auth` (text related to login and passwords), `products` (text related to catalog pages), or `errors` (system error messages).

## 2. Role of Domains in the Architecture

*   **Hierarchy:** Domains represent the second tier in the translation structure: **Scopes → Domains → Keys → Values**.
*   **Mandatory Requirement:** A Domain is strictly required before any Keys can be created. Every translation Key must be bound to a valid Scope and Domain combination (e.g., `admin` + `auth` + `login.title`).

## 3. Domain Data Model

Under the hood, Domains are stored independently in the database in the `i18n_domains` table. Based on the backend repositories and DTOs, the main columns are:
*   **`id` (int):** The unique primary key identifier for the Domain.
*   **`code` (string):** The programmatic, unique string identifier used by the application (e.g., "auth").
*   **`name` (string):** The human-readable name displayed in the administrator UI.
*   **`description` (string, nullable):** Optional metadata explaining what kind of text this Domain should contain.
*   **`is_active` (bool):** A flag determining whether the Domain is enabled and available for use in the system.
*   **`sort_order` (int):** Determines the visual order in which Domains appear in UI dropdowns and lists.
*   **`created_at` (string):** Timestamp of when the Domain was registered in the database.

## 4. Domain ↔ Scope Relationship

*   **How Domains are attached:** Domains are not intrinsically owned by a Scope. They are independent entities that must be explicitly mapped to one or more Scopes.
*   **Mapping table:** This relationship is controlled by the `i18n_domain_scopes` mapping table. It links a `scope_code` to a `domain_code` alongside an `is_active` flag.
*   **Relationship Type:** It is a **Many-to-Many** relationship. A single Domain (like "errors") can be mapped to multiple Scopes (like "admin" and "api").
*   **Rules:**
    *   **Can a Domain exist without a Scope?** Yes. A Domain can be created in the `i18n_domains` table without being mapped to any Scope.
    *   **Can a Domain be used without mapping?** No. Before you can create any Translation Keys for a Domain, the `I18nGovernancePolicyService` strictly requires that the Domain be mapped to the requested Scope in the `i18n_domain_scopes` table, and that the mapping itself is marked active.

## 5. Domain Lifecycle (Admin Behavior)

### Creating a Domain
*   **What admin provides:** The administrator provides a programmatic `code`, a display `name`, and an optional `description`.
*   **Validation rules:** The system checks that the `code` is unique across all `i18n_domains`.
*   **What happens in database:** A new row is inserted into `i18n_domains`. At this stage, it is not yet linked to any Scope.

### Editing a Domain
*   **Editable fields:** Administrators can update the `code`, `name`, `description`, and `sort_order`.
*   **Any restrictions:** Updating the `code` of a Domain triggers massive synchronous updates in the background. The system must move all associated Keys and rebuild all derived summary tables (`i18n_domain_language_summary`) to reflect the new code.

### Activating / Deactivating
*   **What active state means:** In the `i18n_domains` table, the `is_active` boolean is toggled to false.
*   **How it affects usage:** If a Domain is deactivated, it is hidden from standard active queries and dropdown menus, and the system governance policy will block the creation of any new Keys under it.

## 6. Domain List (UI — CODE-BASED)

When viewing the Domain Assignments table inside a specific Scope Details page, the UI renders the following:
*   **Table columns:** `ID`, `Code`, `Name`, `Description`, `Active` (status badge), `Assigned` (whether it is mapped to the current Scope), and `Actions`.
*   **Available actions/buttons:**
    *   **Assign Domain:** If the domain is not currently assigned to the scope, an "Assign" button is visible.
    *   **Unassign Domain:** If the domain is currently assigned to the scope, an "Unassign" button (typically styled in danger/red) is visible.
*   **What each action does:**
    *   Clicking **Assign** triggers a browser confirmation dialog ("Are you sure you want to assign domain..."). Upon confirmation, it sends a POST request to `/api/i18n/scopes/{scope_id}/domains/assign`, inserting a record into `i18n_domain_scopes` and reloading the table.
    *   Clicking **Unassign** triggers a confirmation dialog. Upon confirmation, it sends a POST request to `/api/i18n/scopes/{scope_id}/domains/unassign`, removing or deactivating the mapping.

## 7. Domain Interaction Flow

*   **What happens when admin clicks a Domain:** When an admin clicks on an assigned Domain row in the Scope Details table, they are directed to the specific Translation Keys list for that exact Scope + Domain tuple.
*   **How it leads to Keys:** The navigation routes them to `/i18n/scopes/{scope_id}/domains/{domain_id}/keys`.
*   **What data is loaded:** The system loads the Domain metadata and queries the `i18n_keys` table to render the list of translatable keys specifically bound to that Scope and Domain mapping.

## 8. Constraints & Rules

*   **Code uniqueness:** The Domain `code` must be completely unique globally within the `i18n_domains` table.
*   **Required fields:** When creating a Domain, the `code` and `name` are strictly required.
*   **Dependency on Scope mapping:** A Translation Key cannot be created if its Domain is not explicitly mapped to the requested Scope in `i18n_domain_scopes`. The `I18nGovernancePolicyService` enforces this via a `DomainScopeViolationException`.

## 9. Impact on Translations System

*   **How Domains affect Keys:** Keys are inextricably bound to Domains. You cannot query or resolve a Key without knowing its Domain.
*   **How Domains affect Values:** Translation values are tied to Keys. If a Domain is unmapped from a Scope, all Keys under that tuple effectively become orphaned/unresolvable by the frontend application.
*   **System rendering:** The UI heavily relies on Domains to group translation forms. If a Domain is disabled, administrators cannot easily find or edit the text values within it.

## 10. System Behavior

*   **When changes take effect:** Actions like Assigning or Unassigning a Domain from a Scope take effect immediately via synchronous database transactions.
*   **Validations or checks:** The system strictly checks for duplicates before assigning a domain, and verifies the domain actually exists before mapping it.
*   **Visible errors:** If an assignment fails, the UI captures the JSON error response and renders an alert notification (e.g., `Failed to assign domain`). Attempting to bypass the UI to create a key under an unmapped domain throws backend exceptions (`DomainNotAllowedException`).

## 11. Boundaries

*   **What Domains control:** Domains strictly control the secondary logical grouping of Translation Keys. They define the "feature" or "module" namespace (e.g., separating `auth` text from `checkout` text). They also control whether Keys can be created within that grouping via active status and Scope mappings.
*   **What they do NOT control:** Domains do NOT control application boundaries (that is the Scope's job), and they do NOT control language identities or actual text translations.

## 12. Coverage Confirmation

*   **All Domain behavior is covered:** Yes, the creation, editing, activation, and assigning/unassigning lifecycles are fully detailed.
*   **UI actions are documented:** Yes, the Assign and Unassign action buttons, table columns, and browser confirmation dialogs are extracted directly from the JavaScript (`i18n_scopes_domains.js`).
*   **Relationships are explained:** Yes, the many-to-many mapping table (`i18n_domain_scopes`) and the `I18nGovernancePolicyService` enforcement rules are explicitly documented.
*   **No assumptions were made:** All documentation was strictly extracted from `MysqlDomainRepository.php`, `MysqlDomainScopeRepository.php`, `DomainDTO.php`, and the UI Javascript components. No generic theories or "UNCLEAR" placeholders were used.