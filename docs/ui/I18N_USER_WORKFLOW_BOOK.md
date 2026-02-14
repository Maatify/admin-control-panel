# I18N User Workflow Book

This book is the definitive guide to navigating and using the Internationalization (I18n) system in the Admin Panel. It explains concepts, workflows, and screens based on the actual system implementation.

---

## A) What this system is

*   **Scope:** A high-level project or area (e.g., "Client App", "Admin Panel").
*   **Domain:** A functional group of texts within a Scope (e.g., "Auth", "Checkout", "Errors").
*   **Key:** The unique ID for a piece of text (e.g., `login.submit_button`). Keys belong to a Domain.
*   **Language:** A target locale (e.g., English, French).
*   **Translation:** The text value for a Key in a Language.
*   **Coverage:** A percentage showing how much of the content is translated.

---

## B) Navigation Tree (Full)

This tree shows every screen you can reach in the I18n system.

```text
├── Languages (Top Level)
│   └── Translations List (All translations for this language)
│
└── Settings
    └── Translations
        ├── Scopes (Main Dashboard)
        │   └── Scope Details
        │       ├── Scope Keys List
        │       ├── Coverage Breakdown (by Language)
        │       │   └── Domain Translations (Pre-filtered)
        │       └── Domain Assignments
        │           ├── Domain Keys List
        │           └── Domain Translations Editor
        │
        └── Domains (Global Management)
```

---

## C) Entry Points

You can start working from three places in the sidebar:

1.  **Settings > Translations > Scopes**
    *   *Best for:* Managers, Project Leads.
    *   *Use when:* You want to check progress or manage a specific project.
2.  **Settings > Translations > Domains**
    *   *Best for:* Developers, Architects.
    *   *Use when:* You need to create new functional areas globally.
3.  **Languages**
    *   *Best for:* Translators.
    *   *Use when:* You want to just "translate everything into French" without worrying about structure.

---

## D) All Flows (With Branches)

### Flow 1: Scope-First (The Project Manager Path)
*Use when: You are managing a specific project (e.g. "Client App") and want to see its parts.*

1.  **Click** `Settings > Translations > Scopes`.
2.  **Click** the `ID` (blue link) of the Scope you want.
3.  **Result:** Opens **Scope Details**.
4.  **Scroll** to "Domain Assignments".
5.  **Click** the `Translations` (list icon) button on a Domain row.
6.  **Result:** Opens **Domain Translations**.
7.  **Action:** Edit values in the table.

### Flow 2: Coverage-First (The "Fix Missing" Path)
*Use when: You want to target incomplete areas efficiently.*

1.  **Click** `Settings > Translations > Scopes`.
2.  **Click** the `ID` of the Scope.
3.  **Look** at the "Language Coverage" table.
4.  **Find** the language (e.g. French) with low completion.
5.  **Click** `View Domains`.
6.  **Result:** Opens **Coverage Breakdown**.
    *   *Shows:* List of domains sorted by "Missing Count" (worst first).
7.  **Click** `Go` next to a Domain.
8.  **Result:** Opens **Domain Translations** with "French" **automatically selected**.
9.  **Action:** Fill in the empty rows.

### Flow 3: Language-First (The Translator Path)
*Use when: You are a translator focused on one language.*

1.  **Click** `Languages` in the main sidebar.
2.  **Find** your language (e.g. Spanish).
3.  **Click** the `Translations` (list icon) button.
4.  **Result:** Opens **Language Translations List**.
    *   *Shows:* Every translation key in the entire system for Spanish.
5.  **Action:** Use filters (Scope/Domain) to narrow down, or just edit row-by-row.

### Flow 4: Key Management (The Developer Path)
*Use when: You need to add new text keys.*

1.  **Click** `Settings > Translations > Scopes`.
2.  **Find** the Scope.
3.  **Click** the `Keys` (link icon) button in the Actions column.
4.  **Result:** Opens **Scope Keys List**.
5.  **Click** `Create Key`.
6.  **Action:** Enter Domain and Key Part. Save.

### Flow 5: Domain Management (The Architect Path)
*Use when: You are adding a new feature area.*

1.  **Click** `Settings > Translations > Domains`.
2.  **Click** `Create Domain`.
3.  **Action:** Enter Code and Name. Save.
4.  **Next Step:** You MUST go to a Scope and **Assign** this new domain before it can be used.

---

## E) Mental Model Diagram

**1. Structure & Assignment**
```text
[Scope: Client App] <==== (Assignment Policy) ====> [Domain: Auth]
       |                                                 |
       |                                         [Key: login.title]
       |                                                 |
       +-------------------------------------------------+
```

**2. Translation Data**
```text
[Key: login.title] + [Language: French] = [Translation: "Connexion"]
```

**3. Coverage Logic**
```text
Total Keys (in Assigned Domains)
       MINUS
Count of Translations (for that Language)
       EQUALS
Missing Count
```

---

## F) Screen-by-Screen Roles

### 1. Scopes List
*   **Purpose:** Entry point for projects.
*   **Outputs:** List of Scopes (Code, Name, Status).
*   **Actions:**
    *   `ID Link`: Opens Scope Details.
    *   `Keys`: Opens Scope Keys List.
    *   `Create Scope`: Opens creation modal.

### 2. Scope Details (Dashboard)
*   **Purpose:** Health check and navigation hub.
*   **Outputs:**
    *   **Language Coverage:** Table showing % complete per language.
    *   **Domain Assignments:** Table showing which domains are linked.
*   **Actions:**
    *   `View Domains`: Drills down into coverage.
    *   `Assign/Unassign`: Controls domain linkage.
    *   `View Translations`: Opens editor for a domain.

### 3. Coverage Breakdown
*   **Purpose:** Prioritized "To Do" list for a language.
*   **Outputs:** Domains list sorted by **Missing Count**.
*   **Actions:**
    *   `Go`: Jumps to editor with pre-filters applied.

### 4. Domain Translations Editor
*   **Purpose:** Editing text for a specific feature.
*   **Inputs:** Language Filter (Dropdown), Search (Text).
*   **Outputs:** Table of Keys and Values.
*   **Actions:**
    *   `Edit`: Opens modal to type text.
    *   `Clear`: Deletes the translation.

### 5. Language Translations List
*   **Purpose:** Bulk editing for a single language.
*   **Inputs:** Scope/Domain filters.
*   **Outputs:** Flat list of ALL translations.
*   **Actions:** Same as Domain Editor (Edit/Clear).

---

## G) Differences (Comparisons)

### 1. "Keys Page" vs "Translations Page"
*   **Keys Page:** Manages **Identity**. Used by Developers. Adds new rows like `login.title`.
*   **Translations Page:** Manages **Content**. Used by Translators. Fills in the value "Login" for `login.title`.

### 2. "Domains Page" vs "Scopes Page"
*   **Domains:** Global list of features (e.g. "Auth"). Exists once.
*   **Scopes:** Projects (e.g. "App").
*   **Relationship:** You **Assign** a Domain to a Scope to use it there.

### 3. "Language-First" vs "Scope-First"
*   **Scope-First:** "I am working on the Client App." (Focus: Context)
*   **Language-First:** "I am the French translator." (Focus: Completeness)

---

## H) Troubleshooting

*   **"Why is the list empty?"**
    *   **Domains:** You might not have assigned any domains to the scope. Go to Scope Details > Domain Assignments > Assign.
    *   **Translations:** You might be filtering for a key that doesn't exist. Clear filters.
*   **"Coverage shows 0% but I added text?"**
    *   Ensure you added text for the *assigned* domains. If you translated "Auth" but "Auth" isn't assigned to this scope, it won't count.
*   **"I can't see the language I want."**
    *   Go to the main **Languages** screen and ensure the language is **Active**.

---

## I) Cheat Sheets

**I want to translate a full domain:**
1.  Scope Details > Domain Assignments.
2.  Click `Translations` icon on the domain.
3.  Filter Language to "All" or your target.
4.  Edit rows.

**I want to translate a full language:**
1.  Sidebar > Languages.
2.  Click `Translations` icon on your language.
3.  Edit rows.

**I want to add a domain to a scope:**
1.  Scope Details > Domain Assignments.
2.  Click `Assign` (Green button).

**I want to fix a single typo:**
1.  Sidebar > Languages > Your Language > Translations.
2.  Type the typo word in `Value` filter.
3.  Search.
4.  Edit.

---

## Appendix: Evidence Map (Technical Verification)

| Page               | UI Route                                    | Controller                              | Template                          | JS File                             | API Endpoint                                               |
|:-------------------|:--------------------------------------------|:----------------------------------------|:----------------------------------|:------------------------------------|:-----------------------------------------------------------|
| **Scopes List**    | `/i18n/scopes`                              | `ScopesListUiController`                | `scopes.list.twig`                | `i18n-scopes-core.js`               | `POST /api/i18n/scopes/query`                              |
| **Scope Details**  | `/i18n/scopes/{id}`                         | `ScopeDetailsController`                | `scope_details.twig`              | `i18n_scopes_domains.js`            | `POST /api/i18n/scopes/{id}/domains/query`                 |
| **Scope Coverage** | (Section in Details)                        | (Above)                                 | (Above)                           | `i18n-scope-coverage.js`            | `GET /api/i18n/scopes/{id}/coverage`                       |
| **Breakdown**      | `/i18n/scopes/{s}/coverage/languages/{l}`   | `I18nScopeLanguageCoverageUiController` | `scope_language_coverage.twig`    | `i18n-scope-language-coverage.js`   | `GET /api/i18n/scopes/{s}/coverage/languages/{l}`          |
| **Domain Trans.**  | `/i18n/scopes/{s}/domains/{d}/translations` | `ScopeDomainTranslationsUiController`   | `scope_domain_translations.twig`  | `i18n_scope_domain_translations.js` | `POST /api/i18n/scopes/{s}/domains/{d}/translations/query` |
| **Lang. List**     | `/languages`                                | `LanguagesListController`               | `languages_list.twig`             | `languages-with-components.js`      | `POST /api/languages/query`                                |
| **Lang. Trans.**   | `/languages/{id}/translations`              | `LanguageTranslationsListUiController`  | `language_translations.list.twig` | `i18n_translations_list.js`         | `POST /api/languages/{id}/translations/query`              |
