<?php include __DIR__ . '/../../layouts/header.php'; ?>

<main class="main-content">
<h1>Managing Translation Scopes</h1>
<h2>1. What is a Scope</h2>
<p>A Scope is the highest-level namespace and category in the translation system. It represents a major application boundary or environment. Scopes exist to organize text logically so that translations for completely different parts of the system do not overlap or conflict.
*   <strong>Examples:</strong> Common scopes in the system typically include <code>admin</code> (text intended for the administrative control panel), <code>client</code> (text for the end-user application), or <code>api</code> (text returned in API responses).</p>
<h2>2. Role of Scopes in the Architecture</h2>
<ul>
<li><strong>Hierarchy:</strong> Scopes sit at the very top of the translation hierarchy: <strong>Scopes → Domains → Keys → Values</strong>.</li>
<li><strong>Mandatory Requirement:</strong> A Scope is strictly mandatory for any translation. Every single translation key in the system must belong to a specific Scope and Domain tuple.</li>
<li><strong>Uniqueness:</strong> Scope plays a critical role in Key uniqueness. A Key (like <code>login.title</code>) can exist multiple times in the system as long as it belongs to a different Scope or Domain. The system enforces uniqueness strictly on the combination of a scope, domain, and key.</li>
</ul>
<h2>3. Scope Data Model</h2>
<p>Scopes consist of the following properties:
*   <strong>ID:</strong> The unique system identifier for the Scope.
*   <strong>Code:</strong> The programmatic, unique string identifier used in the system (e.g., "admin").
*   <strong>Name:</strong> The human-readable name displayed in the UI.
*   <strong>Description:</strong> Optional metadata explaining the purpose of the Scope.
*   <strong>Active Status:</strong> A flag determining whether the Scope is currently enabled and available for translation resolution.
*   <strong>Order:</strong> Determines the visual order in which Scopes appear in UI lists.
*   <strong>Created At:</strong> Timestamp of when the Scope was registered.</p>
<h2>4. Scope ↔ Domain Relationship</h2>
<ul>
<li><strong>How domains are attached:</strong> Domains are explicitly linked to Scopes via a mapping.</li>
<li><strong>Mapping:</strong> The relationship explicitly maps a scope to a domain along with its active status.</li>
<li><strong>Relationship Type:</strong> This is a <strong>Many-to-Many</strong> relationship. A single Domain (like "auth") can be attached to multiple Scopes (like both "admin" and "client"), and a single Scope can have many Domains.</li>
<li><strong>Constraints:</strong><ul>
<li>A Domain can exist independently without being mapped to any Scope.</li>
<li>A Scope can exist without any Domains assigned to it.</li>
<li>However, before an administrator can create a Translation Key, the system strictly verifies that the specific Domain is explicitly allowed and mapped to the specific Scope.</li>
</ul>
</li>
</ul>
<h2>5. Scope Lifecycle (Admin Behavior)</h2>
<h3>Creating a Scope</h3>
<ul>
<li><strong>What admin inputs:</strong> The admin provides a code, name, and optional description.</li>
<li><strong>What validation exists:</strong> The system verifies that the code is unique and formatted correctly.</li>
<li><strong>What happens in the system:</strong> The new scope is securely registered.</li>
</ul>
<h3>Editing a Scope</h3>
<ul>
<li><strong>What fields are editable:</strong> Administrators can edit the Code (via a dedicated Change Code action), the Name and Description (via an Update Metadata action), and the Sort Order (via an Update Sort action).</li>
<li><strong>What is restricted:</strong> If you attempt to change the Code, a warning modal appears requiring confirmation.</li>
</ul>
<h3>Activating / Deactivating</h3>
<ul>
<li><strong>What "active" means:</strong> The active status is toggled on or off.</li>
<li><strong>How it affects system behavior:</strong> Deactivating a Scope hides it from standard lists and prevents new keys from being resolved under that namespace.</li>
</ul>
<h2>6. Scope List</h2>
<p>The main UI for managing Scopes displays a data table with the following structure:
*   <strong>Table Columns:</strong> ID, Code (rendered with a code badge), Name, Description, Active (rendered as an Active/Inactive status badge), Order (sort order badge), and Actions.
*   <strong>Actions:</strong> Each row contains multiple actionable buttons depending on the admin's permissions:
    *   <strong>Code:</strong> Opens a modal to change the programmatic Scope code.
    *   <strong>Meta:</strong> Opens a modal to update the Name and Description.
    *   <strong>Sort:</strong> Opens a modal to change the Sort Order.
    *   <strong>Activate / Deactivate:</strong> A toggle button to change the active status.
*   <strong>Buttons:</strong> The table also features a global <strong>Create Scope</strong> button at the top to initialize a new Scope.</p>
<h2>7. Scope Interaction Flow</h2>
<ul>
<li><strong>What happens when clicking a Scope:</strong> Clicking the Scope's ID in the table redirects the administrator to the specific Scope Details page.</li>
<li><strong>How it leads to Domains:</strong> The Scope Details page loads the Domain Assignments table. This shows only the Domains mapped to this specific Scope.</li>
<li><strong>What data is loaded:</strong> The system loads the Scope's metadata, its mapped domains, and its calculated translation coverage statistics.</li>
</ul>
<h2>8. Constraints &amp; Rules</h2>
<ul>
<li><strong>Code uniqueness:</strong> The Scope code must be entirely unique globally.</li>
<li><strong>Naming rules:</strong> The name must be provided, while the description is optional.</li>
<li><strong>Dependency constraints:</strong> You cannot assign a Domain to a Scope if that Domain does not exist. You cannot create a Key for a Scope+Domain combination if that combination is not mapped and marked active.</li>
</ul>
<h2>9. Impact on Translations System</h2>
<ul>
<li><strong>How Scope affects Keys:</strong> Every Key requires a Scope. If a Scope's code is changed, the system automatically updates all corresponding translation coverage statistics.</li>
<li><strong>How Scope affects Values:</strong> If a Scope is deactivated, its associated Translations are hidden.</li>
<li><strong>UI rendering:</strong> Changing a Scope name or sort order updates how the translation management menus appear to administrators.</li>
</ul>
<h2>10. System Behavior &amp; Consistency</h2>
<ul>
<li><strong>Immediate vs delayed changes:</strong> All modifications to Scopes (creating, renaming, updating metadata) are synchronous and immediate.</li>
<li><strong>Validations:</strong> The system strictly validates that any action performed on a Key verifies that its parent Scope and Domain are allowed.</li>
<li><strong>Error cases:</strong> If an admin attempts an invalid action, the system will block the request and display an error.</li>
</ul>
<h2>11. Boundaries</h2>
<ul>
<li><strong>What Scope controls:</strong> A Scope strictly controls the top-level namespace boundary for translation keys. It acts as a mandatory parent container that dictates which Domains are allowed to be used within it.</li>
<li><strong>What it does NOT control:</strong> A Scope does NOT control Language availability, fallback mechanisms, or the actual text values. It is purely an organizational namespace.</li>
</ul>
</main>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
