<?php include __DIR__ . '/../layouts/header.php'; ?>


<h1>Managing Translations</h1>
<h2>Overview</h2>
<p>Translations allow administrators to manage the specific text that appears across the system's interface. Changes made here dictate exactly what words and phrases users read on the platform.</p>
<h2>How to Access Translations</h2>
<ul>
<li>Navigate to the left sidebar menu.</li>
<li>Under the <strong>Translations</strong> section, you will find two primary options: <strong>Scopes</strong> and <strong>Domains</strong>.</li>
</ul>
<h2>Structure of Translations</h2>
<p>The system groups translation data into three levels:</p>
<ul>
<li><strong>Scopes:</strong> The top-level categories.</li>
<li><strong>Domains:</strong> Sub-categories that belong to specific Scopes.</li>
<li><strong>Keys:</strong> The actual text identifiers and their associated translations in different languages.</li>
</ul>
<h2>Scopes</h2>
<p>When you click on <strong>Scopes</strong> in the sidebar:</p>
<ul>
<li><strong>Table Columns:</strong> You see a table displaying the Scope ID, Code, Name, Description, Active status, and Order.</li>
<li><strong>What buttons exist:</strong> You will see action buttons for "Code" and "Meta" to edit the scope's basic details. You can also click directly on the Scope to open it.</li>
<li><strong>Navigation behavior:</strong> Opening a Scope allows you to view its assigned Domains and manage the Keys specifically attached to that Scope.</li>
</ul>
<h2>Domains</h2>
<p>When viewing the Domains assigned to a Scope (or viewing the global Domains list):</p>
<ul>
<li><strong>Full structure:</strong> You will see a list of domains attached to the scope.</li>
<li><strong>Navigation behavior:</strong> You can click to view the specific Translation Keys belonging to that Domain.</li>
</ul>
<h2>Translation Keys (MOST IMPORTANT)</h2>
<p>When you drill down into a specific Scope and Domain, you will reach the Translations List.</p>
<ul>
<li><strong>EXACTLY how translation values are displayed:</strong> The table lists the "Key Part" (the identifier for the text). Next to it, there is a dedicated column showing the currently translated value. If no translation exists for a language, it displays an italicized "Empty" placeholder. The language itself is clearly indicated next to the value.</li>
<li><strong>EXACTLY how they are edited:</strong> Each row has an <strong>Edit icon</strong> (a pencil).</li>
<li><strong>Input type:</strong> Clicking the Edit icon opens an "Edit Translation" pop-up modal. Inside this modal, there is a text input field (or textarea) where you type the new translation. The text input automatically respects the reading direction (e.g., left-to-right or right-to-left) of the selected language.</li>
<li><strong>Save mechanism:</strong> You must click the <strong>Save</strong> button at the bottom of the modal to apply your changes. There is no auto-save feature.</li>
</ul>
<h2>User Interaction Flow</h2>
<p>To edit a translation:
1. <strong>What the admin clicks:</strong> Click the <strong>Edit icon</strong> next to the specific Key and Language you want to update.
2. <strong>What appears:</strong> A pop-up modal appears on screen containing the Key name, the Language name, and the text input field.
3. <strong>What changes:</strong> You type the new translation into the text field and click <strong>Save</strong>. A success message ("Translation saved successfully") appears, the modal closes, and the table instantly refreshes to show your new value.</p>
<h2>Translation Value</h2>
<ul>
<li><strong>Where the text appears:</strong> The updated translation is now saved and will be displayed to users.</li>
<li><strong>How it is edited:</strong> Via the pop-up edit modal accessed from the Translations List table.</li>
<li><strong>How multiple languages are shown:</strong> Each language translation for a key is listed as its own row in the table, clearly marked with a badge showing the language name and code (e.g., "English (en)").</li>
</ul>
<h2>Navigation Flow</h2>
<ol>
<li><strong>Sidebar → Translations → Scopes:</strong> Start by viewing the highest-level categories.</li>
<li><strong>Scopes → Domains:</strong> Click into a specific Scope to see its assigned sub-categories (Domains).</li>
<li><strong>Domains → Keys:</strong> Click into a Domain to see the actual Translations List table where editing happens.</li>
</ol>
<h2>Filters / Search</h2>
<p>When viewing the Translations List:</p>
<ul>
<li><strong>Search inputs:</strong> A search box is available to find specific Key names or translation values.</li>
<li><strong>Filter dropdowns:</strong> A dropdown menu is provided to filter the table to show only a specific language.</li>
</ul>
<h2>Save Behavior</h2>
<ul>
<li><strong>Is there a Save button?</strong> Yes, the "Edit Translation" modal has an explicit Save button.</li>
<li><strong>Is it auto-save?</strong> No.</li>
<li><strong>When does the change apply?</strong> The change is instantly saved to the system upon clicking Save, and the table refreshes immediately.</li>
</ul>
<hr/>


<?php include __DIR__ . '/../layouts/footer.php'; ?>
