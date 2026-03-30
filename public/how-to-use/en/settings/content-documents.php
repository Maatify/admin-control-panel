<?php include __DIR__ . '/../../layouts/header.php'; ?>


<h1>Managing Content Documents</h1>
<h2>1. What are Content Documents</h2>
<p>Content Documents represent the long-form, authoritative texts of the platform. Unlike UI labels or button text, these are formal policies, agreements, or announcements that users must read or explicitly accept. They exist to enforce legal compliance, define platform rules, and maintain a rigorous audit trail of exactly what a user agreed to at a specific point in time.</p>
<ul>
<li><strong>Examples:</strong> Terms of Service, Privacy Policy, Cookie Policy, End User License Agreements (EULA).</li>
</ul>
<h2>2. Core Architecture</h2>
<p>The system models Content Documents via three distinct relational elements:</p>
<h3>Document Type</h3>
<ul>
<li><strong>What it is:</strong> The highest-level container. It represents the structural category of the document.</li>
<li><strong>What it controls:</strong> It stores the core programmatic key, defines whether it is a system-critical document, and configures the default behavior for whether new versions under this type require explicit user acceptance.</li>
</ul>
<h3>Document Version</h3>
<ul>
<li><strong>What it represents:</strong> An exact, point-in-time iteration of a Document Type (e.g., "Version 2.0"). It represents a specific version of a document.</li>
<li><strong>State fields:</strong> It holds critical lifecycle state flags:<ul>
<li>Active status (boolean): Is this version currently active in the system?</li>
<li>Acceptance requirement (boolean): Does this specific version mandate user acceptance?</li>
<li>Published timestamp: When the document was formally published.</li>
<li>Archived timestamp: When the document was retired.</li>
</ul>
</li>
</ul>
<h3>Document Translation</h3>
<ul>
<li><strong>What it contains:</strong> The actual localized text for a specific Document Version. It holds the title, optional SEO metadata, and the HTML body.</li>
<li><strong>Language dependency:</strong> Every translation is strictly bound to a language and the specific version, not the overarching Document Type.</li>
</ul>
<h2>3. Document Lifecycle (CRITICAL)</h2>
<p>A Document Version transitions through a strict, one-way state machine.</p>
<h3>Draft</h3>
<ul>
<li><strong>What defines a draft:</strong> A version is considered a Draft if it has not yet been published.</li>
<li><strong>Visibility:</strong> Drafts are entirely invisible to end-users and the frontend application. They exist only in the admin panel.</li>
</ul>
<h3>Editing</h3>
<ul>
<li><strong>What can be edited:</strong> Administrators can safely add, edit, or remove content (the actual HTML body and titles) while the document is in the Draft state.</li>
<li><strong>Restrictions:</strong> The system enforces strict immutability. If a Document Version is marked as active, published, or archived, the system will instantly reject any attempt to save or modify translations.</li>
</ul>
<h3>Publishing</h3>
<ul>
<li><strong>What happens when publishing:</strong> The document transitions to the published state.</li>
<li><strong>What fields change:</strong> The published timestamp is populated.</li>
<li><strong>Impact on previous versions:</strong> Publishing inherently locks the document content. It does not automatically archive other versions unless configured by specific business logic, but it makes the current version permanently immutable. Once published, a document must be explicitly activated to be served to users.</li>
</ul>
<h3>Archiving</h3>
<ul>
<li><strong>When it happens:</strong> A version is archived when it is superseded by a newer policy or is no longer legally applicable.</li>
<li><strong>Effect:</strong> The archived timestamp is populated. The document is permanently retired. The system strictly prevents archived documents from ever being published or activated again.</li>
</ul>
<h2>4. Versioning Model</h2>
<ul>
<li><strong>Why versioning exists:</strong> Versioning exists for legal integrity. If the Terms of Service change, the system cannot simply overwrite the old text, because it needs to prove exactly which text a user agreed to three years ago versus today.</li>
<li><strong>How versions are created:</strong> An admin explicitly generates a new document version under an existing document type.</li>
<li><strong>How system determines "current" version:</strong> The system automatically serves the single document under a type that is published, active, and not archived.</li>
<li><strong>Relationship between versions:</strong> Versions are independent records tied together only by their shared document type.</li>
</ul>
<h2>5. Immutability Rules (VERY IMPORTANT)</h2>
<ul>
<li><strong>What becomes locked after publishing:</strong> The entire content of the document (including title and HTML body) becomes permanently locked.</li>
<li><strong>What cannot be changed:</strong> You cannot edit typos, add new languages, or change the legal text.</li>
<li><strong>Why (legal integrity):</strong> This guarantees that the text a user accepts on Tuesday cannot be secretly altered by an administrator on Wednesday. If a typo needs fixing or a clause needs updating, a completely new Draft version must be created, translated, published, and potentially re-accepted by users.</li>
</ul>
<h2>6. Translation Model</h2>
<ul>
<li><strong>How multiple languages are handled:</strong> Multiple language translations can be attached to a single document version.</li>
<li><strong>Relationship with Languages:</strong> The system links translations to the platform's registered languages.</li>
<li><strong>How translations are tied to versions:</strong> Translations are strictly bound to the specific version. This ensures that "Version 1.0" can have translations in English and Spanish, while "Version 2.0" can have translations in English, Spanish, and French, without the translations bleeding across versions.</li>
</ul>
<h2>7. Acceptance Model (CRITICAL)</h2>
<ul>
<li><strong>When users must accept documents:</strong> If a Document Version has an acceptance requirement set to true, the application gateway can force users to explicitly agree to the document before accessing the platform.</li>
<li><strong>How acceptance is tracked:</strong> Acceptance is securely recorded by the system.</li>
<li><strong>Relationship between version and acceptance:</strong> The acceptance record permanently binds the user to the exact version they read, securely logging the timestamp, IP address, and browser details for compliance auditing.</li>
</ul>
<h2>8. Admin Interaction Flow</h2>
<p>The administrative workflow strictly follows the lifecycle state machine:
1.  <strong>Create Type:</strong> (If not already existing) Define the broad category (e.g., "Privacy Policy").
2.  <strong>Create Version:</strong> Generate a new Draft version under the chosen Type.
3.  <strong>Add Translations:</strong> Write and save the HTML content for the Draft version across the required languages.
4.  <strong>Publish:</strong> Lock the Draft content to make it a formal system document.
5.  <strong>Activate:</strong> Make the published document the live, active version served to end-users.
6.  <strong>Archive:</strong> Archive any older, superseded versions of the same Type to retire them.</p>
<h2>9. System Behavior</h2>
<ul>
<li><strong>When changes take effect:</strong> Changes to content are invisible until the version is published and activated.</li>
<li><strong>Draft vs Published visibility:</strong> The platform only serves Active, Published, Non-Archived documents.</li>
<li><strong>What users see:</strong> Users see the localized text for the currently active version. If the new active version has an acceptance requirement enabled and they haven't accepted it yet, the platform will prompt them to do so.</li>
</ul>
<h2>10. Constraints &amp; Rules</h2>
<ul>
<li><strong>Cannot edit published version:</strong> The system strictly prevents edits to any active, published, or archived document.</li>
<li><strong>Cannot skip required steps:</strong> A document must be Published before it can be Activated. An archived document can never be published or activated.</li>
<li><strong>Validation rules:</strong> Translations require a valid document version and language. You cannot accept an archived document.</li>
</ul>
<h2>11. Relationship with Other Modules</h2>
<ul>
<li><strong>Languages:</strong> Relies entirely on the Languages module for valid language identifiers.</li>
<li><strong>Translations:</strong> Fundamentally different from standard translations. The Translations module handles dynamic, editable UI snippets that overwrite each other. Content Documents handle immutable, versioned, multi-page HTML content.</li>
<li><strong>Auth / Admin:</strong> Access to create, edit, and publish Content Documents is strictly governed by the Roles &amp; Permissions system.</li>
</ul>
<h2>12. Boundaries</h2>
<ul>
<li><strong>What Content Documents control:</strong> They strictly control authoritative, versioned texts that require historical preservation and optional explicit user acceptance.</li>
<li><strong>What they do NOT control:</strong> They do not control standard UI strings, email templates, or dynamic application settings.</li>
</ul>


<?php include __DIR__ . '/../../layouts/footer.php'; ?>
