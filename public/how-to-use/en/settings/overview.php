<?php include __DIR__ . '/../../layouts/header.php'; ?>

<main class="main-content">
<h1>Managing System Settings — Overview</h1>
<h2>1. Purpose of the Settings Module</h2>
<p>The Settings module serves as the central control hub for configuring the platform's behavior and managing its authoritative, long-form content. Administrators use this system to control dynamic application configurations (feature toggles, external integration keys) and legal or policy documents (Terms of Service, Privacy Policies) directly from the control panel. It exists to ensure that both the operational parameters of the system and the legally binding agreements presented to users can be governed, versioned, and audited securely through the interface.</p>
<h2>2. Types of Settings in the System</h2>
<p>There are two distinct types of settings managed under this module:</p>
<ul>
<li><strong>Content Documents:</strong> Represents formal, versioned texts such as legal agreements, privacy policies, or compliance documents. These are structured entities that track revisions, support multi-language translations per version, and optionally require explicit acceptance from end-users.</li>
<li><strong>Application Settings (App Settings):</strong> Represents the technical key-value configurations that control application logic. These settings are grouped logically (e.g., system, features, limits) and enforce strict data types (e.g., Text, Integer, Boolean) to ensure the platform reads configurations safely.</li>
</ul>
<h2>3. Core Architecture of Settings</h2>
<p>The system separates configuration into two distinct areas:</p>
<ul>
<li><strong>App Settings:</strong> This is a strictly typed key-value store. Settings are identified by a composite of a Group and a Key (e.g., Group: "security", Key: "max_login_attempts"). The system stores the value, its explicit type, and an active status flag.</li>
<li><strong>Content Documents:</strong> This is a deeply structured, version-controlled system. It is composed of three hierarchical levels:<ul>
<li><strong>Document Type:</strong> Defines the broad category (e.g., Terms of Service). It dictates whether the document is a system requirement and if it defaults to requiring user acceptance.</li>
<li><strong>Document Version:</strong> A specific iteration of a Document Type (e.g., Version 2.0). It holds metadata about its state, such as whether it is active, published, or archived.</li>
<li><strong>Document Translation:</strong> The actual localized, long-form content (title, HTML body) tied to a specific Document Version and a specific language.</li>
</ul>
</li>
</ul>
<h2>4. Content Documents (High-Level)</h2>
<ul>
<li><strong>What they represent:</strong> Content Documents represent authoritative content that end-users must read or agree to.</li>
<li><strong>Why they are separated from translations:</strong> While the Translations area handles short, UI-level text snippets (like "Save Button" or "Welcome Message"), Content Documents handle entire pages of formatted HTML. More importantly, Content Documents are immutable once published. They track distinct versions (so the system knows exactly which version of the Terms of Service a user agreed to in 2024 vs. 2026), whereas standard translations just overwrite the current text without preserving historical context.</li>
<li><strong>Their role in the system:</strong> They act as the legal and compliance backbone of the platform, enforcing user acceptance gates before allowing access to certain platform features.</li>
</ul>
<h2>5. Application Settings (High-Level)</h2>
<ul>
<li><strong>What kind of configuration they control:</strong> They control operational behavior, feature toggling, rate limits, and external integrations.</li>
<li><strong>Examples:</strong> While specific keys are dynamic, the interface supports groups and typed values. An example would be a setting grouped under <code>system</code> with the key <code>maintenance_mode</code>, having a boolean value of <code>true</code>.</li>
<li><strong>How they affect system behavior:</strong> Core platform features read these settings at runtime. Changing an App Setting instantly modifies how the system behaves.</li>
</ul>
<h2>6. Admin Interaction Model</h2>
<ul>
<li><strong>How admins access settings:</strong> Administrators navigate to the "Settings" section of the control panel, where they select either the "App Settings" or "Content Documents" management areas.</li>
<li><strong>How they modify system behavior (App Settings):</strong> Admins create or edit a key-value pair via the provided forms, explicitly define its group and data type, and toggle it active or inactive.</li>
<li><strong>How they modify system behavior (Content Documents):</strong> Admins follow a strict, versioned flow. They cannot simply edit a published legal document. Instead, they:<ol>
<li>Create a new Document Version under an existing Document Type.</li>
<li>Provide localized content (Translations) for that specific version.</li>
<li>Publish the version (which optionally archives older versions).</li>
<li>The system then immediately begins serving the newly published version to end-users.</li>
</ol>
</li>
</ul>
<h2>7. System Behavior</h2>
<ul>
<li><strong>When changes take effect:</strong><ul>
<li>For <strong>App Settings</strong>, changes to a value or its active status take effect immediately across the platform upon saving.</li>
<li>For <strong>Content Documents</strong>, changes only take effect when a Document Version transitions its state to Published and Active. Draft versions are completely hidden from the frontend.</li>
</ul>
</li>
<li><strong>Validation or restrictions:</strong><ul>
<li><strong>App Settings:</strong> The interface validates that the provided value matches the declared type (e.g., you cannot save letters if the type is declared as an Integer). It also enforces uniqueness on the Group + Key combination to prevent conflicts.</li>
<li><strong>Content Documents:</strong> The system enforces immutability. Once a Document Version is published and users begin accepting it, its core content is locked to maintain legal integrity. To change the text, an admin must generate a new Version.</li>
</ul>
</li>
</ul>
<h2>8. Relationship with Other Modules</h2>
<ul>
<li><strong>Relationship with Languages:</strong> Content Documents heavily rely on the platform's active languages. When an admin writes the actual content for a Document Version, they must attach it to a valid language provided by the platform.</li>
<li><strong>Relationship with Translations:</strong> The Settings module has no direct relationship with the standard Translations area. They solve different problems. Translations handle dynamic UI rendering of small strings. Content Documents handle long-form, version-controlled HTML content.</li>
<li><strong>Relationship with Admin System:</strong> Access to manage App Settings and Content Documents is strictly governed by the Roles &amp; Permissions system.</li>
</ul>
<h2>9. Boundaries of the Settings Module</h2>
<ul>
<li><strong>What Settings control:</strong> They strictly control dynamic configuration values (key-value pairs) and long-form, versioned platform documents (legal policies, terms, announcements).</li>
<li><strong>What they do NOT control:</strong> They do not control the wording of UI buttons, menu items, or system error messages (handled by Translations). They do not control administrator accounts or roles (handled by Admins/RBAC). They do not control the addition of new system languages (handled by Languages).</li>
</ul>
</main>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
