<?php include __DIR__ . '/../layouts/header.php'; ?>

<main class="main-content">
<h1>Managing Languages</h1>
<h2>Overview</h2>
<p>The Languages page allows administrators to manage the list of available languages used across the platform. While this page does not handle the actual text translations, it defines which languages exist, their programmatic codes (e.g., "en", "ar"), their reading direction (left-to-right or right-to-left), and their active status. Other parts of the system use these languages to display content.</p>
<h2>How to Access Languages</h2>
<p>To manage the platform's supported languages:
1. Locate the left sidebar navigation menu.
2. Click on the <strong>Languages</strong> link.
3. This opens the main Languages List page.</p>
<h2>Languages List</h2>
<p>The main interface is a data table displaying all registered languages.</p>
<ul>
<li><strong>Table Columns:</strong><ul>
<li><strong>ID:</strong> The unique system identifier for the language.</li>
<li><strong>Name:</strong> The human-readable name of the language (e.g., "English").</li>
<li><strong>Code:</strong> The programmatic identifier (e.g., "en").</li>
<li><strong>Direction:</strong> The reading direction for the language (e.g., <code>ltr</code> or <code>rtl</code>).</li>
<li><strong>Order:</strong> The numerical sort order determining how languages appear in dropdowns across the platform.</li>
<li><strong>Status:</strong> A visual badge indicating if the language is "Active" or "Inactive".</li>
<li><strong>Fallback:</strong> Displays a link icon with the ID of another language if a fallback is configured, or "None" with an X icon if not.</li>
<li><strong>Actions:</strong> Contains all interactive buttons for modifying the language row.</li>
</ul>
</li>
</ul>
<h3>Filters and Search</h3>
<p>Above the table, the interface provides comprehensive search and filtering tools:
*   <strong>Global Search:</strong> A search input box that allows you to instantly search across the table. It features a 1-second auto-search delay as you type, or you can press "Enter" or click the Search button to trigger it immediately. A "Clear" button resets this specific input.
*   <strong>Column Filters:</strong> A filter form allowing you to narrow down the table by specific fields: ID, Name, Code, Direction, and Status.
*   <strong>Reset Filters Button:</strong> A dedicated button that clears all active column filters and resets the table view to page 1.</p>
<h2>Creating a Language</h2>
<p>When introducing a new language option to the platform:</p>
<ol>
<li>Click the <strong>Create Language</strong> button located above the table.</li>
<li>A modal or form will open requiring the new language's <strong>Name</strong>, <strong>Code</strong>, and <strong>Direction</strong> (LTR or RTL). You can also optionally provide an icon, set the initial Active status, and assign a Fallback Language.</li>
<li>Click the save/create button to submit the form.</li>
<li><strong>Validation:</strong> The system strictly verifies that the provided <strong>Code</strong> does not already exist. If it does, a "Language Already Exists" error is displayed.</li>
<li><strong>Result:</strong> The language is immediately created and assigned the next available sort order automatically. It instantly appears in the Languages List.</li>
</ol>
<h2>Editing a Language</h2>
<p>Unlike bulk-edit forms, modifying a language in this system is split into highly specific actions to ensure data integrity.</p>
<p>From the <strong>Actions</strong> column in the Languages List, you can perform the following modifications:
*   <strong>Update Settings:</strong> Click the Edit Settings button to modify the language's reading Direction and Icon.
*   <strong>Update Name:</strong> Allows you to change the human-readable Name of the language.
*   <strong>Update Code:</strong> Allows you to change the programmatic Code. <em>Warning:</em> The system will strictly validate that the new code is not already in use by another language.
*   <strong>Update Sort Order:</strong> Allows you to manually adjust the numerical priority of the language.</p>
<ul>
<li><strong>Save Behavior:</strong> Each of these actions applies immediately. Upon success, the UI table refreshes instantly to display the updated data.</li>
</ul>
<h2>Activating / Deactivating a Language</h2>
<p>You can control whether a registered language is currently active in the system.</p>
<ol>
<li>Locate the language row.</li>
<li>Click the <strong>Activate</strong> or <strong>Deactivate</strong> toggle button in the Actions column.</li>
<li><strong>What happens after:</strong> The Status badge changes immediately. When deactivated, the language is generally removed from user-facing selection options, though existing translations tied to it remain securely preserved.</li>
</ol>
<h2>Managing Fallback Languages</h2>
<p>A "Fallback Language" instructs the system to display text from an alternative language if a translation is missing for the user's selected language.</p>
<p>From the <strong>Actions</strong> column:
1.  <strong>Set Fallback:</strong> If the language currently has "None" listed, click the <strong>Set Fallback</strong> button (purple link icon). A modal opens allowing you to input the ID of another language.
2.  <strong>Clear Fallback:</strong> If a fallback is currently configured, click the <strong>Clear Fallback</strong> button (red X icon) to remove the fallback.</p>
<h2>Deleting a Language</h2>
<p>There is <strong>no delete functionality</strong> for languages. Languages are permanently referenced by translations and user settings across the platform. If a language is no longer needed, administrators must use the <strong>Deactivate</strong> action to hide it from the active platform.</p>
<h2>What Happens When Languages Change</h2>
<p>Because this is the central language list for the platform:
*   <strong>Immediate Application:</strong> Any change to a language's Code, Direction, or Active status takes effect immediately across the interface.
*   <strong>Translation Dependencies:</strong> Modifying a language directly impacts the translations area, as all text values are loaded based on the languages and fallbacks defined here.</p>
<hr/>
</main>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
