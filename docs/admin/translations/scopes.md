# Managing Translation Scopes

## 1. What is a Scope

A Scope is the highest-level namespace and category in the translation system. It represents a major application boundary or environment. Scopes exist to organize text logically so that translations for completely different parts of the system do not overlap or conflict.
*   **Examples:** Common scopes in the system typically include `admin` (text intended for the administrative control panel), `client` (text for the end-user application), or `api` (text returned in API responses).

## 2. Role of Scopes in the Architecture

*   **Hierarchy:** Scopes sit at the very top of the translation hierarchy: **Scopes → Domains → Keys → Values**.
*   **Mandatory Requirement:** A Scope is strictly mandatory for any translation. Every single translation key in the system must belong to a specific Scope and Domain tuple.
*   **Uniqueness:** Scope plays a critical role in Key uniqueness. A Key (like `login.title`) can exist multiple times in the system as long as it belongs to a different Scope or Domain. The system enforces uniqueness strictly on the combination of a scope, domain, and key.

## 3. Scope Data Model

Scopes consist of the following properties:
*   **ID:** The unique system identifier for the Scope.
*   **Code:** The programmatic, unique string identifier used in the system (e.g., "admin").
*   **Name:** The human-readable name displayed in the UI.
*   **Description:** Optional metadata explaining the purpose of the Scope.
*   **Active Status:** A flag determining whether the Scope is currently enabled and available for translation resolution.
*   **Order:** Determines the visual order in which Scopes appear in UI lists.
*   **Created At:** Timestamp of when the Scope was registered.

## 4. Scope ↔ Domain Relationship

*   **How domains are attached:** Domains are explicitly linked to Scopes via a mapping.
*   **Mapping:** The relationship explicitly maps a scope to a domain along with its active status.
*   **Relationship Type:** This is a **Many-to-Many** relationship. A single Domain (like "auth") can be attached to multiple Scopes (like both "admin" and "client"), and a single Scope can have many Domains.
*   **Constraints:**
    *   A Domain can exist independently without being mapped to any Scope.
    *   A Scope can exist without any Domains assigned to it.
    *   However, before an administrator can create a Translation Key, the system strictly verifies that the specific Domain is explicitly allowed and mapped to the specific Scope.

## 5. Scope Lifecycle (Admin Behavior)

### Creating a Scope
*   **What admin inputs:** The admin provides a code, name, and optional description.
*   **What validation exists:** The system verifies that the code is unique and formatted correctly.
*   **What happens in the system:** The new scope is securely registered.

### Editing a Scope
*   **What fields are editable:** Administrators can edit the Code (via a dedicated Change Code action), the Name and Description (via an Update Metadata action), and the Sort Order (via an Update Sort action).
*   **What is restricted:** You cannot directly edit the internal ID. Changing the Code is allowed but has massive downstream implications for any Keys tied to the old code.

### Activating / Deactivating
*   **What "active" means:** The active status is toggled on or off.
*   **How it affects system behavior:** Deactivating a Scope hides it from standard lists and prevents new keys from being resolved under that namespace.

## 6. Scope List

The main UI for managing Scopes displays a data table with the following structure:
*   **Table Columns:** ID, Code (rendered with a code badge), Name, Description, Active (rendered as an Active/Inactive status badge), Order (sort order badge), and Actions.
*   **Actions:** Each row contains multiple actionable buttons depending on the admin's permissions:
    *   **Code:** Opens a modal to change the programmatic Scope code.
    *   **Meta:** Opens a modal to update the Name and Description.
    *   **Sort:** Opens a modal to change the Sort Order.
    *   **Activate / Deactivate:** A toggle button to change the active status.
*   **Buttons:** The table also features a global **Create Scope** button at the top to initialize a new Scope.

## 7. Scope Interaction Flow

*   **What happens when clicking a Scope:** Clicking the Scope's ID in the table redirects the administrator to the specific Scope Details page.
*   **How it leads to Domains:** The Scope Details page loads the Domain Assignments table. This shows only the Domains mapped to this specific Scope.
*   **What data is loaded:** The system loads the Scope's metadata, its mapped domains, and its calculated translation coverage statistics.

## 8. Constraints & Rules

*   **Code uniqueness:** The Scope code must be entirely unique globally.
*   **Naming rules:** The name must be provided, while the description is optional.
*   **Dependency constraints:** You cannot assign a Domain to a Scope if that Domain does not exist. You cannot create a Key for a Scope+Domain combination if that combination is not mapped and marked active.

## 9. Impact on Translations System

*   **How Scope affects Keys:** Every Key requires a Scope. If a Scope's code is changed, the system automatically updates all corresponding translation coverage statistics.
*   **How Scope affects Values:** Values (Translations) are tied to Key IDs. While the Value doesn't care about the Scope, if the parent Scope is deactivated or deleted, the entire tree of Keys and Values under it becomes unavailable to the application.
*   **UI rendering:** Changing a Scope name or sort order updates how the translation management menus appear to administrators.

## 10. System Behavior & Consistency

*   **Immediate vs delayed changes:** All modifications to Scopes (creating, renaming, updating metadata) are synchronous and immediate.
*   **Validations:** The system strictly validates that any action performed on a Key verifies that its parent Scope and Domain are allowed.
*   **Error cases:** If an admin attempts an invalid action, the system will block the request and display an error.

## 11. Boundaries

*   **What Scope controls:** A Scope strictly controls the top-level namespace boundary for translation keys. It acts as a mandatory parent container that dictates which Domains are allowed to be used within it.
*   **What it does NOT control:** A Scope does NOT control Language availability, fallback mechanisms, or the actual text values. It is purely an organizational namespace.