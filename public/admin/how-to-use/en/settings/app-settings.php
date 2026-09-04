<?php include __DIR__ . '/../../layouts/header.php'; ?>


<h1>Managing Application Settings</h1>

<h2>1. Overview</h2>
<p>Application Settings are configurations that control the behavior of the platform. This section allows administrators to toggle features, adjust limits, and configure integration logic directly from the control panel.</p>

<h2>2. Admin Interaction Flow</h2>
<p>Administrators manage these configurations through the App Settings list interface.</p>

<h3>Search &amp; Filters</h3>
<p>At the top of the page, you can use filters to quickly find specific settings:</p>
<ul>
    <li><strong>ID:</strong> Search by the exact setting ID.</li>
    <li><strong>Group:</strong> Filter by category (e.g., <code>system</code>, <code>security</code>).</li>
    <li><strong>Key:</strong> Search by the specific setting name.</li>
    <li><strong>Status:</strong> Filter by Active or Inactive settings.</li>
    <li><strong>Search Button:</strong> Applies the entered filters.</li>
    <li><strong>Reset Button:</strong> Clears all column filters and reloads the default list.</li>
</ul>

<h3>Global Search</h3>
<p>Below the filters is a quick search bar:</p>
<ul>
    <li><strong>Quick Search:</strong> Type to search across group, key, or value simultaneously.</li>
    <li><strong>Clear Button:</strong> Removes the global search text.</li>
</ul>

<h3>Settings List (Table)</h3>
<p>The table displays all available settings with the following columns:</p>
<ul>
    <li><strong>ID:</strong> The unique identifier for the setting.</li>
    <li><strong>Group:</strong> The category the setting belongs to.</li>
    <li><strong>Key:</strong> The name of the setting.</li>
    <li><strong>Value:</strong> The current configured value.</li>
    <li><strong>Type:</strong> Indicates the format of the value.</li>
    <li><strong>Status:</strong> Indicates if the setting is currently Active or Inactive.</li>
    <li><strong>Actions:</strong> Contains buttons to interact with the setting.</li>
</ul>

<h3>Visual Indicators</h3>
<ul>
    <li><strong>Protected Settings (🔒 Lock Icon):</strong> Some settings are critical to the system and cannot be edited. These appear with a lock icon next to their key.</li>
    <li><strong>Orphaned Settings (⚠️ Warning Icon):</strong> Settings that are no longer recognized by the system appear with a warning icon. These can only be deactivated.</li>
</ul>

<h2>3. How to Edit a Setting</h2>
<p>To modify an existing setting:</p>
<ol>
    <li>Locate the setting in the list using the search or filters.</li>
    <li>Click the <strong>Edit</strong> button in the Actions column for that row.</li>
    <li>An <strong>Edit App Setting</strong> modal will open on your screen.</li>
    <li>Modify the <strong>Value</strong> in the provided input field.</li>
    <li>If necessary, change the <strong>Type</strong> from the dropdown menu.</li>
    <li>Click the <strong>Save Changes</strong> button inside the modal.</li>
    <li>A success message will appear, the modal will close, and the table will automatically refresh to show the updated value.</li>
</ol>

<h2>4. How to Create a Setting</h2>
<p>If your account has the necessary permissions, you can create new settings:</p>
<ol>
    <li>Click the <strong>Create Setting</strong> button located near the search filters.</li>
    <li>A creation modal will open on your screen.</li>
    <li>Fill in the required input fields (Group, Key, Value, and Type).</li>
    <li>Click the <strong>Save</strong> button inside the modal.</li>
    <li>A success message will appear, the modal will close, and the table will automatically refresh to show the newly created setting.</li>
</ol>

<h2>5. Usage Guidance</h2>
<ul>
    <li><strong>Active vs. Inactive:</strong> You can change a setting's status by clicking the toggle button in the Actions column for that row. A success message is shown and the table refreshes to display the new status.</li>
    <li><strong>Locked Settings:</strong> You cannot modify settings marked with the 🔒 icon. The interface restricts editing for these configurations.</li>
    <li><strong>Immediate Updates:</strong> Any changes made and saved in this interface will appear immediately in the table.</li>
</ul>


<?php include __DIR__ . '/../../layouts/footer.php'; ?>
