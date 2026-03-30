<?php include __DIR__ . '/../../layouts/header.php'; ?>

<main class="main-content">
<h1>Managing Translation Domains</h1>
<h2>1. What is a Domain</h2>
<p>A Domain is the secondary categorization level in the translation system, acting as a functional grouping or sub-namespace within a larger Scope. It represents a specific feature, module, or logical area of the application.
*   <strong>Examples:</strong> Common domains include <code>auth</code> (for authentication-related text like login and password reset), <code>errors</code> (for system error messages), <code>emails</code> (for email templates), or <code>dashboard</code> (for the main user interface).</p>
<h2>2. Role of Domains in the Architecture</h2>
<ul>
<li><strong>Hierarchy:</strong> Domains sit below Scopes and above Keys in the translation hierarchy: <strong>Scopes → Domains → Keys → Values</strong>.</li>
<li><strong>Mandatory Requirement:</strong> Every translation Key in the system must belong to a specific Scope and Domain combination. A Key cannot exist without a Domain.</li>
<li><strong>Logical Grouping:</strong> Domains provide a structured way to organize thousands of translation keys, making them easier to manage, export, and assign to translators.</li>
</ul>
<h2>3. Domain Data Model</h2>
<p>Domains consist of the following properties:
*   <strong>ID:</strong> The unique system identifier for the Domain.
*   <strong>Code:</strong> The programmatic, unique string identifier used in the system (e.g., "auth").
*   <strong>Name:</strong> The human-readable name displayed in the UI.
*   <strong>Description:</strong> Optional metadata explaining the purpose of the Domain.
*   <strong>Active Status:</strong> A flag determining whether the Domain is currently enabled and available for translation resolution.
*   <strong>Order:</strong> Determines the visual order in which Domains appear in UI lists.
*   <strong>Created At:</strong> Timestamp of when the Domain was registered.</p>
<h2>4. Scope ↔ Domain Relationship</h2>
<ul>
<li><strong>Mapping:</strong> A Domain must be explicitly mapped to one or more Scopes to be used. This mapping defines where the Domain is valid.</li>
<li><strong>Relationship Type:</strong> This is a <strong>Many-to-Many</strong> relationship. A single Domain (like "auth") can be attached to multiple Scopes (like both "admin" and "client"), and a single Scope can have many Domains.</li>
<li><strong>Constraints:</strong><ul>
<li>A Domain can be created independently without being immediately mapped to a Scope.</li>
<li>The "Create Key" button is only available when a Domain is assigned to a Scope.</li>
<li>The combination of a Scope and Domain defines the namespace for translation Keys.</li>
</ul>
</li>
</ul>
<h2>5. Domain Lifecycle (Admin Behavior)</h2>
<h3>Creating a Domain</h3>
<ul>
<li><strong>What admin inputs:</strong> The admin provides a code, name, and optional description.</li>
<li><strong>What validation exists:</strong> The system verifies that the code is unique across all Domains and formatted correctly.</li>
<li><strong>What happens in the system:</strong> The new Domain is securely registered and becomes available to be mapped to Scopes.</li>
</ul>
<h3>Editing a Domain</h3>
<ul>
<li><strong>What fields are editable:</strong> Administrators can edit the Code (via a Change Code action), the Name and Description (via an Update Metadata action), and the Sort Order (via an Update Sort action).</li>
<li><strong>What is restricted:</strong> The internal ID cannot be changed. Modifying the Code is allowed but affects any Keys associated with this Domain across all mapped Scopes.</li>
</ul>
<h3>Activating / Deactivating</h3>
<ul>
<li><strong>What "active" means:</strong> The active status can be toggled on or off.</li>
<li><strong>How it affects system behavior:</strong> Deactivating a Domain globally prevents it from being used in any new mappings and may affect the resolution of existing keys depending on system configuration.</li>
</ul>
<h2>6. Domain List</h2>
<p>The main UI for managing Domains displays a data table with the following structure:
*   <strong>Table Columns:</strong> ID, Code (rendered with a code badge), Name, Description, Active (rendered as an Active/Inactive status badge), Order (sort order badge), and Actions.
*   <strong>Actions:</strong> Each row contains actionable buttons depending on admin permissions:
    *   <strong>Code:</strong> Opens a modal to change the programmatic Domain code.
    *   <strong>Meta:</strong> Opens a modal to update the Name and Description.
    *   <strong>Sort:</strong> Opens a modal to change the Sort Order.
    *   <strong>Activate / Deactivate:</strong> A toggle button to change the global active status of the Domain.
*   <strong>Buttons:</strong> The table includes a global <strong>Create Domain</strong> button at the top to initialize a new Domain.</p>
<h2>7. Domain Interaction Flow</h2>
<ul>
<li><strong>What happens when clicking a Domain:</strong> Clicking a Domain in the table directs the administrator to the specific Domain Details page.</li>
<li><strong>How it leads to Scopes:</strong> The Domain Details page displays the Scope Assignments table, showing all Scopes to which this specific Domain is currently mapped.</li>
<li><strong>What data is loaded:</strong> The system loads the Domain's metadata, its mapped Scopes, and aggregated translation coverage statistics for this Domain.</li>
</ul>
<h2>8. Constraints &amp; Rules</h2>
<ul>
<li><strong>Code uniqueness:</strong> The Domain code must be globally unique across all Domains.</li>
<li><strong>Naming rules:</strong> A valid name is required, while the description is optional.</li>
<li><strong>Dependency constraints:</strong> You cannot create a Key for a specific Domain unless that Domain has been mapped to a Scope and both are active.</li>
</ul>
<h2>9. Impact on Translations System</h2>
<ul>
<li><strong>How Domain affects Keys:</strong> Every Key requires a Domain. If a Domain's code is changed, the system automatically updates the namespace path for all corresponding Keys.</li>
<li><strong>How Domain affects Values:</strong> Values (Translations) are tied to Key IDs. If a Domain is deactivated or deleted, the entire structure of Keys and Values under it becomes unavailable for that Domain's namespace.</li>
<li><strong>UI rendering:</strong> Changes to a Domain's name or sort order immediately update how the translation management menus and filters appear to administrators.</li>
</ul>
<h2>10. System Behavior &amp; Consistency</h2>
<ul>
<li><strong>Immediate vs delayed changes:</strong> All modifications to Domains (creating, renaming, updating metadata) are synchronous and immediately reflected in the system.</li>
<li><strong>Validations:</strong> The system strictly validates all actions performed on a Domain, ensuring that dependent mappings and keys are handled correctly.</li>
<li><strong>Error cases:</strong> Attempting an invalid action, such as creating a Domain with a duplicate code or deleting a Domain that still has active Keys, will be blocked by the system with an error message.</li>
</ul>
<h2>11. Boundaries</h2>
<ul>
<li><strong>What Domain controls:</strong> A Domain strictly controls the secondary namespace boundary for translation keys. It acts as a functional container within a Scope.</li>
<li><strong>What it does NOT control:</strong> A Domain does NOT control Language availability, fallback mechanisms, or the actual translated text values. It is purely an organizational categorization.</li>
</ul>
</main>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
