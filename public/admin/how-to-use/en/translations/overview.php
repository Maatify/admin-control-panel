<?php include __DIR__ . '/../../layouts/header.php'; ?>


<h1>Managing Translations — System Overview</h1>
<h2>1. Purpose of the Translations System</h2>
<p>The Translations System is a localization layer designed to manage all localized text across the platform. It solves the problem of hardcoding strings into applications by allowing administrators to dynamically define, edit, and organize text in multiple languages. Administrators use this system to control exactly what users read in the interface, ensuring consistent terminology and allowing the platform to serve a global audience without requiring code deployments for text changes.</p>
<h2>2. Core Architecture</h2>
<p>The system organizes translations into a four-level hierarchy:</p>
<ul>
<li><strong>Scopes:</strong> The top-level namespace (e.g., admin, client, api). It represents the major application boundary. It connects to Domains to organize specific features.</li>
<li><strong>Domains:</strong> A sub-grouping within a Scope (e.g., auth, products, errors). It represents a specific feature or module. A Domain must be explicitly allowed for a Scope before Keys can be created under it.</li>
<li><strong>Keys:</strong> The structured registry of valid text identifiers (e.g., login.title). A Key is a strict combination of Scope, Domain, and Key Part. It acts as the anchor for the actual text values.</li>
<li><strong>Values:</strong> The actual localized text strings. Each value is tied directly to a specific Key and Language.</li>
</ul>
<h2>3. How Translations Work in the System</h2>
<p>When the system renders text in the UI, it follows these rules:</p>
<ol>
<li><strong>Language Resolution:</strong> The system determines the user's requested language.</li>
<li><strong>System Lookup:</strong> The system retrieves the exact translation matching the requested language and key.</li>
<li><strong>Fallback Behavior:</strong> If the exact translation is missing, the system utilizes a strict fallback chain. It checks if the requested language has a fallback language defined. If so, it automatically attempts to load the translation for that fallback language. If both the primary and fallback languages are missing the translation, the system returns the raw key string.</li>
</ol>
<h2>4. Admin Interaction Model</h2>
<p>Administrators interact with the system through a drill-down flow designed to enforce the strict hierarchy:</p>
<ol>
<li><strong>Start at Scopes:</strong> The admin begins at the top level, viewing the available namespaces.</li>
<li><strong>Drill down to Domains:</strong> The admin selects a Scope to view its explicitly assigned Domains.</li>
<li><strong>Drill down to Keys:</strong> The admin selects a Domain to view the registry of Keys belonging to that specific Scope and Domain pair.</li>
<li><strong>Actual Editing:</strong> The admin reaches the deepest level (the Translations List) where they manage the actual text values tied to those Keys across different languages.</li>
</ol>
<h2>5. Translation Editing Model</h2>
<ul>
<li><strong>How values are edited:</strong> Admins edit values directly through the translation interface.</li>
<li><strong>How multiple languages are handled:</strong> Translations for multiple languages are attached to the same Key.</li>
<li><strong>How updates propagate to the system:</strong> The system uses a strict consistency model. There are no background delays. When a translation is updated, it is immediately applied. Simultaneously, all translation coverage statistics are instantly recalculated to ensure admin dashboards reflect the new coverage immediately.</li>
</ul>
<h2>6. Language Dependency</h2>
<p>The Translations module depends on the active languages defined in the system.</p>
<ul>
<li><strong>How languages affect translations:</strong> The Translations module does not define what languages exist. It strictly relies on the active languages list. Every translation must be attached to a valid language.</li>
<li><strong>What happens when a language is inactive/missing:</strong> If a language is deleted or deactivated, translations tied to that language become unavailable. The system will block any attempts to save a translation for an invalid language.</li>
<li><strong>How fallback language is used:</strong> The fallback language is defined in the Languages management section. The Translations module simply reads this fallback configuration during runtime to resolve missing keys.</li>
</ul>
<h2>7. System Behavior &amp; Consistency</h2>
<ul>
<li><strong>Immediate vs delayed updates:</strong> All updates are strongly consistent and immediate. The system enforces instantaneous updates.</li>
<li><strong>Caching:</strong> Translations are updated immediately without requiring cache clears.</li>
<li><strong>Impact of changing shared keys:</strong> Because Keys are strictly registered and globally queried by the application, changing the value of a shared key (e.g., changing a generic "Save" button text) will instantly and immediately update that text everywhere the key is referenced across the entire platform.</li>
</ul>
<h2>8. Navigation Overview</h2>
<p>The high-level navigation structure mirrors the architectural hierarchy:</p>
<p><code>Translations</code> → <code>Scopes</code> → <code>Domains</code> → <code>Keys</code></p>
<h2>9. Boundaries of This Module</h2>
<ul>
<li><strong>What this module DOES:</strong> It strictly manages the registry of valid text Keys, enforces the Scope and Domain hierarchy, and stores and retrieves the actual localized text strings. It maintains statistics about translation coverage.</li>
<li><strong>What it DOES NOT do:</strong> It does NOT manage language identity, language codes, or fallback configurations. Those responsibilities belong entirely to the Languages module.</li>
</ul>


<?php include __DIR__ . '/../../layouts/footer.php'; ?>
