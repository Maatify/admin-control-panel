<?php include __DIR__ . '/../../layouts/header.php'; ?>


<h1>Managing Application Settings</h1>
<h2>1. What are Application Settings</h2>
<p>Application Settings are dynamic configurations that control the runtime behavior of the platform. They act as a configuration engine, allowing administrators to toggle features, adjust limits, and configure external integration logic directly from the control panel without requiring a developer to intervene.</p>
<ul>
<li><strong>Difference between static configuration and App Settings:</strong> Core infrastructure configurations (like server ports) must remain completely secure and are only changed during deployment. App Settings, conversely, represent dynamic business logic variables that are safe for administrators to modify on the fly through the UI.</li>
</ul>
<h2>2. Core Architecture</h2>
<p>The architecture of an App Setting is built on a strict typed key-value pairing:</p>
<h3>Setting Key</h3>
<ul>
<li><strong>Unique identifier:</strong> A setting is uniquely identified by the combination of its Group and its Key.</li>
<li><strong>Naming conventions:</strong> They are typically namespaced using dot notation internally (e.g., <code>system.timezone</code>), but managed as distinct Group (e.g., "system") and Key (e.g., "timezone") components.</li>
</ul>
<h3>Setting Value</h3>
<ul>
<li><strong>Data type:</strong> Every setting explicitly declares a logical type: Text, Integer, Boolean, or JSON.</li>
<li><strong>Storage format:</strong> Regardless of the logical type, all values are stored and presented logically. The system handles converting the value into the correct format behind the scenes.</li>
</ul>
<h3>Grouping</h3>
<ul>
<li><strong>Categories:</strong> The Group categorization collects related settings together, allowing the system to query entire blocks of configuration at once (e.g., loading all <code>smtp</code> settings).</li>
</ul>
<h3>Metadata</h3>
<ul>
<li><strong>Editable flag:</strong> Settings have an active status flag. Inactive settings are effectively ignored by the application.</li>
<li><strong>System flag:</strong> Settings can be marked as protected (cannot be modified or disabled by the UI) and whitelisted (must be recognized by the application code to be valid).</li>
</ul>
<h2>3. Storage Model</h2>
<ul>
<li><strong>Data structure:</strong> Settings consist of properties such as ID, Group, Key, Value, Type, and Active Status.</li>
<li><strong>Key → value mapping:</strong> The system uniquely maps the group and key to a single value.</li>
<li><strong>JSON structure usage:</strong> If a setting is declared as JSON, it stores a structured JSON format.</li>
<li><strong>Normalization:</strong> Settings are managed as a flat, high-performance registry.</li>
</ul>
<h2>4. Retrieval Flow</h2>
<p>When the application needs a configuration value:
1.  <strong>How settings are loaded:</strong> The application retrieves the setting using its group and key.
2.  <strong>Verification:</strong> The system verifies the setting against a strict whitelist to ensure the requested setting is actually known to the system.
3.  <strong>System lookup:</strong> The system performs an immediate lookup to retrieve the current value.
4.  <strong>Casting:</strong> The system formats the retrieved value according to its defined type before using it.</p>
<h2>5. Caching Strategy</h2>
<ul>
<li><strong>Cache layer:</strong> There is no caching layer implemented for App Settings.</li>
<li>Every request performs a direct, real-time check to ensure absolute consistency.</li>
</ul>
<h2>6. Update Flow</h2>
<ol>
<li><strong>How admin updates a setting:</strong> The administrator edits the setting's value via the UI and clicks save. The information is processed by the system.</li>
<li><strong>Validation rules:</strong> The system first verifies that the setting isn't locked. It then strictly validates the provided value against the declared type (e.g., ensuring text entered for an integer setting is actually a number). If validation fails, the system blocks the update and displays an error.</li>
<li><strong>What happens after update:</strong> The system instantly updates the value.</li>
<li><strong>Immediate effect:</strong> Because there is no caching layer, the system serves as the absolute source of truth. The update takes immediate effect. The very next time the platform requests that setting, it receives the new value.</li>
</ol>
<h2>7. Runtime Behavior</h2>
<p>App Settings are used across the platform to govern business rules dynamically.</p>
<ul>
<li><strong>How settings affect system behavior:</strong> They act as conditional gates within the platform.</li>
<li><strong>Examples:</strong><ul>
<li><strong>Toggles (Boolean):</strong> A <code>feature.maintenance_mode</code> setting used to block user logins.</li>
<li><strong>Limits (Integer):</strong> A <code>security.max_login_attempts</code> limit applied before locking an account.</li>
<li><strong>Configurations (JSON):</strong> An <code>integration.payment_gateways</code> setting that stores an array of enabled providers for the checkout screen.</li>
</ul>
</li>
</ul>
<h2>8. Admin Interaction Flow</h2>
<p>Administrators manage these configurations through the specific App Settings list interface:
*   <strong>Table Columns:</strong> The table displays ID, Group, Key, Value, Type, Status, and Actions. It includes visual badges for context (e.g., a 🔒 lock icon for protected settings, and a ⚠️ warning icon for orphaned settings not recognized by the system whitelist).
*   <strong>Editing settings:</strong> Admins click an edit action on a row to modify the value and the logical type.
*   <strong>Saving changes:</strong> Submitting the form updates the system instantly, and the UI table refreshes.</p>
<h2>9. Constraints &amp; Rules</h2>
<p>The system enforces strict governance over what can be modified:
*   <strong>System-protected:</strong> The system hardcodes critical infrastructure settings (e.g., <code>system.base_url</code>) that are completely blocked from modification via the UI. Attempting to change them will be blocked by the system.
*   <strong>Whitelist policy:</strong> The system strictly ensures that invalid or unrecognized settings cannot be created. Only setting keys that are explicitly declared in the application's internal whitelist can be created or queried.</p>
<h2>10. Relationship with Other Modules</h2>
<ul>
<li><strong>Auth / Admin System:</strong> Changes to security settings (like timeout limits) immediately impact how the platform processes user sessions.</li>
<li><strong>Content Documents &amp; Localization:</strong> App Settings are completely decoupled from Languages and Translations. They manage system logic, not localized text or legal document versions.</li>
</ul>
<h2>11. Boundaries</h2>
<ul>
<li><strong>What belongs to App Settings:</strong> Dynamic feature toggles, administrative email routing addresses, pagination limits, and active integration flags.</li>
<li><strong>What MUST NOT be stored here:</strong><ul>
<li><strong>Secrets:</strong> API tokens, database passwords, and encryption keys must never be stored here; they belong exclusively in core infrastructure configuration.</li>
<li><strong>Large content:</strong> Large HTML blocks or legal policies must not be stored here; they belong in the Content Documents module.</li>
<li><strong>Translations:</strong> User-facing text strings belong in the Translations module.</li>
</ul>
</li>
</ul>
<h2>12. Risks &amp; Misuse</h2>
<ul>
<li><strong>Dangers of misuse:</strong> Because changes take immediate effect, entering an invalid configuration (e.g., setting a pagination limit to <code>0</code> or <code>1000000</code>) can instantly break UI layouts or cause performance issues platform-wide.</li>
<li><strong>Wrong usage patterns:</strong> Administrators attempting to use App Settings to store user-specific preferences or large localized strings violate the module's architectural boundaries.</li>
</ul>


<?php include __DIR__ . '/../../layouts/footer.php'; ?>
