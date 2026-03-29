# Managing Content Documents

## 1. What are Content Documents

Content Documents represent the long-form, authoritative texts of the platform. Unlike UI labels or button text, these are formal policies, agreements, or announcements that users must read or explicitly accept. They exist to enforce legal compliance, define platform rules, and maintain a rigorous audit trail of exactly what a user agreed to at a specific point in time.
*   **Examples:** Terms of Service, Privacy Policy, Cookie Policy, End User License Agreements (EULA).

## 2. Core Architecture

The system models Content Documents via three distinct relational elements:

### Document Type
*   **What it is:** The highest-level container. It represents the structural category of the document.
*   **What it controls:** It stores the core programmatic key, defines whether it is a system-critical document, and configures the default behavior for whether new versions under this type require explicit user acceptance.

### Document Version
*   **What it represents:** An exact, point-in-time iteration of a Document Type (e.g., "Version 2.0"). It represents a specific version of a document.
*   **State fields:** It holds critical lifecycle state flags:
    *   Active status (boolean): Is this version currently active in the system?
    *   Acceptance requirement (boolean): Does this specific version mandate user acceptance?
    *   Published timestamp: When the document was formally published.
    *   Archived timestamp: When the document was retired.

### Document Translation
*   **What it contains:** The actual localized text for a specific Document Version. It holds the title, optional SEO metadata, and the HTML body.
*   **Language dependency:** Every translation is strictly bound to a language and the specific version, not the overarching Document Type.

## 3. Document Lifecycle (CRITICAL)

A Document Version transitions through a strict, one-way state machine.

### Draft
*   **What defines a draft:** A version is considered a Draft if it has not yet been published.
*   **Visibility:** Drafts are entirely invisible to end-users and the frontend application. They exist only in the admin panel.

### Editing
*   **What can be edited:** Administrators can safely add, edit, or remove content (the actual HTML body and titles) while the document is in the Draft state.
*   **Restrictions:** The system enforces strict immutability. If a Document Version is marked as active, published, or archived, the system will instantly reject any attempt to save or modify translations.

### Publishing
*   **What happens when publishing:** The document transitions to the published state.
*   **What fields change:** The published timestamp is populated.
*   **Impact on previous versions:** Publishing inherently locks the document content. It does not automatically archive other versions unless configured by specific business logic, but it makes the current version permanently immutable. Once published, a document must be explicitly activated to be served to users.

### Archiving
*   **When it happens:** A version is archived when it is superseded by a newer policy or is no longer legally applicable.
*   **Effect:** The archived timestamp is populated. The document is permanently retired. The system strictly prevents archived documents from ever being published or activated again.

## 4. Versioning Model

*   **Why versioning exists:** Versioning exists for legal integrity. If the Terms of Service change, the system cannot simply overwrite the old text, because it needs to prove exactly which text a user agreed to three years ago versus today.
*   **How versions are created:** An admin explicitly generates a new document version under an existing document type.
*   **How system determines "current" version:** The system automatically serves the single document under a type that is published, active, and not archived.
*   **Relationship between versions:** Versions are independent records tied together only by their shared document type.

## 5. Immutability Rules (VERY IMPORTANT)

*   **What becomes locked after publishing:** The entire content of the document (including title and HTML body) becomes permanently locked.
*   **What cannot be changed:** You cannot edit typos, add new languages, or change the legal text.
*   **Why (legal integrity):** This guarantees that the text a user accepts on Tuesday cannot be secretly altered by an administrator on Wednesday. If a typo needs fixing or a clause needs updating, a completely new Draft version must be created, translated, published, and potentially re-accepted by users.

## 6. Translation Model

*   **How multiple languages are handled:** Multiple language translations can be attached to a single document version.
*   **Relationship with Languages:** The system links translations to the platform's registered languages.
*   **How translations are tied to versions:** Translations are strictly bound to the specific version. This ensures that "Version 1.0" can have translations in English and Spanish, while "Version 2.0" can have translations in English, Spanish, and French, without the translations bleeding across versions.

## 7. Acceptance Model (CRITICAL)

*   **When users must accept documents:** If a Document Version has an acceptance requirement set to true, the application gateway can force users to explicitly agree to the document before accessing the platform.
*   **How acceptance is tracked:** Acceptance is securely recorded by the system.
*   **Relationship between version and acceptance:** The acceptance record permanently binds the user to the exact version they read, securely logging the timestamp, IP address, and browser details for compliance auditing.

## 8. Admin Interaction Flow

The administrative workflow strictly follows the lifecycle state machine:
1.  **Create Type:** (If not already existing) Define the broad category (e.g., "Privacy Policy").
2.  **Create Version:** Generate a new Draft version under the chosen Type.
3.  **Add Translations:** Write and save the HTML content for the Draft version across the required languages.
4.  **Publish:** Lock the Draft content to make it a formal system document.
5.  **Activate:** Make the published document the live, active version served to end-users.
6.  **Archive:** Archive any older, superseded versions of the same Type to retire them.

## 9. System Behavior

*   **When changes take effect:** Changes to content are invisible until the version is published and activated.
*   **Draft vs Published visibility:** The platform only serves Active, Published, Non-Archived documents.
*   **What users see:** Users see the localized text for the currently active version. If the new active version has an acceptance requirement enabled and they haven't accepted it yet, the platform will prompt them to do so.

## 10. Constraints & Rules

*   **Cannot edit published version:** The system strictly prevents edits to any active, published, or archived document.
*   **Cannot skip required steps:** A document must be Published before it can be Activated. An archived document can never be published or activated.
*   **Validation rules:** Translations require a valid document version and language. You cannot accept an archived document.

## 11. Relationship with Other Modules

*   **Languages:** Relies entirely on the Languages module for valid language identifiers.
*   **Translations:** Fundamentally different from standard translations. The Translations module handles dynamic, editable UI snippets that overwrite each other. Content Documents handle immutable, versioned, multi-page HTML content.
*   **Auth / Admin:** Access to create, edit, and publish Content Documents is strictly governed by the Roles & Permissions system.

## 12. Boundaries

*   **What Content Documents control:** They strictly control authoritative, versioned texts that require historical preservation and optional explicit user acceptance.
*   **What they do NOT control:** They do not control standard UI strings, email templates, or dynamic application settings.
