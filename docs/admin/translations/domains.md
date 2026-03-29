# Managing Translation Domains

## 1. What is a Domain

A Domain is the secondary categorization level in the translation system, acting as a functional grouping or sub-namespace within a larger Scope. It represents a specific feature, module, or logical area of the application.
*   **Examples:** Common domains include `auth` (for authentication-related text like login and password reset), `errors` (for system error messages), `emails` (for email templates), or `dashboard` (for the main user interface).

## 2. Role of Domains in the Architecture

*   **Hierarchy:** Domains sit below Scopes and above Keys in the translation hierarchy: **Scopes → Domains → Keys → Values**.
*   **Mandatory Requirement:** Every translation Key in the system must belong to a specific Scope and Domain combination. A Key cannot exist without a Domain.
*   **Logical Grouping:** Domains provide a structured way to organize thousands of translation keys, making them easier to manage, export, and assign to translators.

## 3. Domain Data Model

Domains consist of the following properties:
*   **ID:** The unique system identifier for the Domain.
*   **Code:** The programmatic, unique string identifier used in the system (e.g., "auth").
*   **Name:** The human-readable name displayed in the UI.
*   **Description:** Optional metadata explaining the purpose of the Domain.
*   **Active Status:** A flag determining whether the Domain is currently enabled and available for translation resolution.
*   **Order:** Determines the visual order in which Domains appear in UI lists.
*   **Created At:** Timestamp of when the Domain was registered.

## 4. Scope ↔ Domain Relationship

*   **Mapping:** A Domain must be explicitly mapped to one or more Scopes to be used. This mapping defines where the Domain is valid.
*   **Relationship Type:** This is a **Many-to-Many** relationship. A single Domain (like "auth") can be attached to multiple Scopes (like both "admin" and "client"), and a single Scope can have many Domains.
*   **Constraints:**
    *   A Domain can be created independently without being immediately mapped to a Scope.
    *   The "Create Key" button is only available when a Domain is assigned to a Scope.
    *   The combination of a Scope and Domain defines the namespace for translation Keys.

## 5. Domain Lifecycle (Admin Behavior)

### Creating a Domain
*   **What admin inputs:** The admin provides a code, name, and optional description.
*   **What validation exists:** The system verifies that the code is unique across all Domains and formatted correctly.
*   **What happens in the system:** The new Domain is securely registered and becomes available to be mapped to Scopes.

### Editing a Domain
*   **What fields are editable:** Administrators can edit the Code (via a Change Code action), the Name and Description (via an Update Metadata action), and the Sort Order (via an Update Sort action).
*   **What is restricted:** The internal ID cannot be changed. Modifying the Code is allowed but affects any Keys associated with this Domain across all mapped Scopes.

### Activating / Deactivating
*   **What "active" means:** The active status can be toggled on or off.
*   **How it affects system behavior:** Deactivating a Domain globally prevents it from being used in any new mappings and may affect the resolution of existing keys depending on system configuration.

## 6. Domain List

The main UI for managing Domains displays a data table with the following structure:
*   **Table Columns:** ID, Code (rendered with a code badge), Name, Description, Active (rendered as an Active/Inactive status badge), Order (sort order badge), and Actions.
*   **Actions:** Each row contains actionable buttons depending on admin permissions:
    *   **Code:** Opens a modal to change the programmatic Domain code.
    *   **Meta:** Opens a modal to update the Name and Description.
    *   **Sort:** Opens a modal to change the Sort Order.
    *   **Activate / Deactivate:** A toggle button to change the global active status of the Domain.
*   **Buttons:** The table includes a global **Create Domain** button at the top to initialize a new Domain.

## 7. Domain Interaction Flow

*   **What happens when clicking a Domain:** Clicking a Domain in the table directs the administrator to the specific Domain Details page.
*   **How it leads to Scopes:** The Domain Details page displays the Scope Assignments table, showing all Scopes to which this specific Domain is currently mapped.
*   **What data is loaded:** The system loads the Domain's metadata, its mapped Scopes, and aggregated translation coverage statistics for this Domain.

## 8. Constraints & Rules

*   **Code uniqueness:** The Domain code must be globally unique across all Domains.
*   **Naming rules:** A valid name is required, while the description is optional.
*   **Dependency constraints:** You cannot create a Key for a specific Domain unless that Domain has been mapped to a Scope and both are active.

## 9. Impact on Translations System

*   **How Domain affects Keys:** Every Key requires a Domain. If a Domain's code is changed, the system automatically updates the namespace path for all corresponding Keys.
*   **How Domain affects Values:** Values (Translations) are tied to Key IDs. If a Domain is deactivated or deleted, the entire structure of Keys and Values under it becomes unavailable for that Domain's namespace.
*   **UI rendering:** Changes to a Domain's name or sort order immediately update how the translation management menus and filters appear to administrators.

## 10. System Behavior & Consistency

*   **Immediate vs delayed changes:** All modifications to Domains (creating, renaming, updating metadata) are synchronous and immediately reflected in the system.
*   **Validations:** The system strictly validates all actions performed on a Domain, ensuring that dependent mappings and keys are handled correctly.
*   **Error cases:** Attempting an invalid action, such as creating a Domain with a duplicate code or deleting a Domain that still has active Keys, will be blocked by the system with an error message.

## 11. Boundaries

*   **What Domain controls:** A Domain strictly controls the secondary namespace boundary for translation keys. It acts as a functional container within a Scope.
*   **What it does NOT control:** A Domain does NOT control Language availability, fallback mechanisms, or the actual translated text values. It is purely an organizational categorization.