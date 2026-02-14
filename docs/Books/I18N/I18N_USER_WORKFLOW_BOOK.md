# 🌍 I18n User Workflow Book

> **The Single Source of Truth for I18n Users**
>
> This manual describes the exact workflows, screens, and navigation paths available in the Admin Control Panel for managing internationalization (I18n). It is strictly based on the current system implementation.

---

## 1. What is this System?

The I18n (Internationalization) system allows you to manage languages and translations for your application. It uses a structured approach to organize text so that it can be easily translated and maintained.

### Core Concepts (Mental Model)

Imagine a library:

1.  **Scope**: A "Section" of the library (e.g., "User Profile", "Checkout", "Admin Panel").
2.  **Domain**: A "Topic" or "Category" of books (e.g., "Buttons", "Error Messages", "Labels").
    *   *Note:* A Domain exists globally but must be **Assigned** to a Scope to be used there.
3.  **Key**: A specific "Book Title" (e.g., `btn_save`, `err_invalid_email`).
    *   A Key is unique within a Scope + Domain combination.
4.  **Translation**: The "Content" of the book in a specific Language (e.g., "Save", "Invalid Email").
5.  **Language**: The language the content is written in (e.g., English, Arabic).

**Visual Hierarchy:**

```
[ APP ]
 ├── [ Language: English ]
 ├── [ Language: Arabic ]
 │
 └── [ SCOPE: User Profile ]
      │
      ├── [ DOMAIN: Buttons ] (Assigned)
      │    ├── Key: save   -> "Save"
      │    └── Key: cancel -> "Cancel"
      │
      └── [ DOMAIN: Errors ] (Assigned)
           ├── Key: invalid_email -> "Invalid Email"
           └── Key: required      -> "Field Required"
```

---

## 2. Navigation Tree

This map shows every clickable path in the system starting from the Sidebar.

*   **Dashboard** (Home)
*   **Languages** (`/languages`)
    *   `[ID]` (Clickable Link) -> **Language Translations** (`/languages/{id}/translations`)
*   **Settings**
    *   **Translations**
        *   **Scopes** (`/i18n/scopes`)
            *   `[ID]` (Clickable Link) -> **Scope Details** (`/i18n/scopes/{id}`)
                *   **Keys** (Button) -> **Scope Keys** (`/i18n/scopes/{id}/keys`)
                *   **View Domains** (Link on Coverage Row) -> **Language Coverage Breakdown** (`/i18n/scopes/{id}/coverage/languages/{lang_id}`)
                    *   **Go** (Link on Domain Row) -> **Scope Domain Translations** (Filtered) (`/i18n/scopes/{id}/domains/{domain_id}/translations?language_id={lang_id}`)
                *   **Translations** (Button on Domain Row) -> **Scope Domain Translations** (`/i18n/scopes/{id}/domains/{domain_id}/translations`)
                *   **Keys** (Button on Domain Row) -> **Scope Domain Keys** (`/i18n/scopes/{id}/domains/{domain_id}/keys`)
        *   **Domains** (`/i18n/domains`)

---

## 3. Entry Points & Workflows

There are three main ways to use the system depending on your goal.

### Workflow A: "Language-First" (Translating Content)
*Best for: Translators or Content Managers focusing on one language.*

1.  Click **Languages** in the sidebar.
2.  Find the language you want to translate (e.g., "Arabic").
3.  Click the **ID** (blue number) of that language.
4.  You are now on the **Translations List** page.
5.  Use filters to find specific keys (by Scope, Domain, or Value).
6.  Click **Edit** (blue pencil button) to add or change a translation.

### Workflow B: "Scope-First" (Developer/Admin Setup)
*Best for: Setting up new features, assigning domains, or auditing coverage.*

1.  Click **Settings** -> **Translations** -> **Scopes**.
2.  Click the **ID** of the Scope you are working on (e.g., "User Profile").
3.  **To Assign a Domain:**
    *   Scroll to "Domain Assignments".
    *   Find the Domain (use search if needed).
    *   Click **Assign** (green button).
4.  **To Add Keys:**
    *   Click **Keys** (purple button) at the top of the Scope Details page.
    *   Click **Create Key**.
    *   Select the Domain and enter the Key Name.
5.  **To Translate Specific Domain:**
    *   In "Domain Assignments", find the assigned domain.
    *   Click **Translations** (blue eye button).

### Workflow C: "Global Domain Management"
*Best for: Creating new categories of text.*

1.  Click **Settings** -> **Translations** -> **Domains**.
2.  Click **Create Domain** (green button).
3.  Enter Code (e.g., `buttons`) and Name (e.g., "Buttons").
4.  *Note:* After creating a domain, you must go to **Scope-First Workflow** to assign it to a Scope.

---

## 4. Screen Reference

### 🌍 Languages List (`/languages`)
*   **Purpose:** Overview of all supported languages.
*   **Actions:**
    *   **Create Language:** Add a new language to the system.
    *   **Edit Settings:** Change text direction (LTR/RTL) or Icon.
    *   **Edit Name/Code:** Rename the language.
    *   **Sort:** Change the display order.
    *   **Set/Clear Fallback:** Define which language to show if a translation is missing.
    *   **Toggle Status:** Activate or Deactivate a language.
    *   **Link (ID):** Go to translations for this language.

### 📝 Language Translations (`/languages/{id}/translations`)
*   **Purpose:** Translate all keys for a single language.
*   **Filters:** ID, Scope, Domain, Key Segment, Value.
*   **Actions:**
    *   **Edit:** Open a modal to enter/change the text.
    *   **Clear:** Remove the translation (reverts to fallback).
    *   **Global Search:** Search across all scopes/domains for this language.

### 🌐 I18n Scopes (`/i18n/scopes`)
*   **Purpose:** Manage the "Sections" of your application.
*   **Actions:**
    *   **Create Scope:** Add a new section.
    *   **Link (ID):** Go to Scope Details.
    *   **Keys (Button):** Jump directly to all keys in this scope.
    *   **Edit Code/Meta/Sort:** Manage scope properties.

### 🔍 Scope Details (`/i18n/scopes/{id}`)
*   **Purpose:** The central hub for a specific Scope.
*   **Sections:**
    *   **Overview:** Basic info.
    *   **Language Coverage:** Pie charts showing translation progress per language.
    *   **Domain Assignments:** List of all available domains.
*   **Actions:**
    *   **Assign:** specific Domain to this Scope.
    *   **Unassign:** Remove a Domain from this Scope.
    *   **Translations (Button):** View translations for a specific assigned domain.
    *   **Keys (Button):** View keys for a specific assigned domain.
    *   **View Domains (Link):** Drill down to see which domains are missing translations for a specific language.

### 📊 Language Coverage Breakdown (`/i18n/scopes/{id}/coverage/languages/{lang_id}`)
*   **Purpose:** See exactly which domains are missing translations for a selected language within a scope.
*   **Actions:**
    *   **Go (Link):** Jump to the translations page for a specific domain, pre-filtered for this language.

### 🗝️ Scope Keys (`/i18n/scopes/{id}/keys`)
*   **Purpose:** Manage the Keys (identifiers) within a Scope.
*   **Actions:**
    *   **Create Key:** Define a new key (must select an assigned Domain).
    *   **Rename:** Change the key identifier.
    *   **Update Description:** Add context for translators.

### 📚 Domains List (`/i18n/domains`)
*   **Purpose:** Create and manage the global list of Domains (categories).
*   **Note:** You cannot "enter" a domain here. Domains are used inside Scopes.
*   **Actions:** Create, Edit Code, Edit Meta, Sort.

---

## 5. Differences & Comparisons

To avoid confusion, here is how different concepts and screens compare:

### **Scope Page vs. Domain Page**
*   **Scope Page:** Where you assemble your project. You spend most of your time here assigning domains and creating keys.
*   **Domain Page:** Where you define categories. You rarely visit this page once your categories (Buttons, Errors, etc.) are set up.

### **Keys Page vs. Translation Page**
*   **Keys Page:** For **Developers**. You define *identifiers* (e.g., `btn_save`). You do not enter text here.
*   **Translation Page:** For **Translators**. You enter *text* (e.g., "Save"). You cannot change identifiers here.

### **Language-First vs. Scope-First Workflow**
*   **Language-First:** "I am a translator. I want to translate everything into Spanish." -> Use this to see a long list of all keys in Spanish.
*   **Scope-First:** "I am a developer. I am building the Checkout page." -> Use this to manage keys and domains specifically for the Checkout scope.

### **Coverage vs. Translation Editor**
*   **Coverage:** A read-only report telling you *what* is missing (e.g., "Spanish is 50% complete").
*   **Translation Editor:** The tool to *fix* what is missing (e.g., Type in the missing words).

---

## 6. Troubleshooting & FAQ

**Q: Why is the "Translations" list empty?**
A: Check your filters. Also, ensure that the Language is active and that Keys have been created in a Scope.

**Q: Why can't I find a Domain in the "Create Key" dropdown?**
A: The Domain must be **Assigned** to the Scope first. Go to **Scope Details** and assign the domain.

**Q: Why is coverage 0%?**
A: You may have keys but no translations entered for that language. Use the **Language-First Workflow** to add translations.

**Q: I created a Domain, but I can't use it.**
A: Domains are global but must be **Assigned** to a Scope. Go to **Scopes** -> Select Scope -> **Assign** the domain.

**Q: How do I delete a Key?**
A: Keys cannot be deleted directly to prevent breaking the application. You can deactivate the Scope or Domain if needed.

---

## 7. Quick Reference Cheat Sheet

| I want to... | Go to... | Click... |
| :--- | :--- | :--- |
| **Translate text** | Sidebar -> Languages | `[ID]` of the language |
| **Add a new Key** | Sidebar -> Scopes -> `[ID]` | `[Keys]` -> `[Create Key]` |
| **Create a new category** | Sidebar -> Settings -> Domains | `[Create Domain]` |
| **Use a category in a section** | Sidebar -> Scopes -> `[ID]` | Find Domain -> `[Assign]` |
| **See what's missing** | Sidebar -> Scopes -> `[ID]` | Look at "Language Coverage" |
| **Fix missing translations** | Coverage Table | `[View Domains]` -> `[Go]` |

---

## 8. Visual Flow Maps

**Adding a New Translation:**
`[Languages]` -> Click `[ID]` -> Search Key -> Click `[Edit]` -> Save.

**Adding a New Key:**
`[Scopes]` -> Click `[ID]` -> Click `[Keys]` -> `[Create Key]` -> Select Domain -> Enter Name -> Save.

**Enabling a New Feature Section:**
1. `[Domains]` -> Create Domain (if new category).
2. `[Scopes]` -> Create Scope (if new section).
3. `[Scopes]` -> Click `[ID]` -> Find Domain -> `[Assign]`.
4. `[Keys]` -> Create Keys.
5. `[Languages]` -> Click `[ID]` -> Translate Keys.

---

## CODE VERIFICATION SUMMARY

*   **UI Routes Confirmed:**
    *   `/languages` (LanguagesListController)
    *   `/languages/{id}/translations` (LanguageTranslationsListUiController)
    *   `/i18n/scopes` (ScopesListUiController)
    *   `/i18n/scopes/{id}` (ScopeDetailsController)
    *   `/i18n/scopes/{id}/keys` (ScopeKeysController)
    *   `/i18n/scopes/{id}/domains/{domain_id}/translations` (ScopeDomainTranslationsUiController)
    *   `/i18n/domains` (DomainsListUiController)
    *   `/i18n/scopes/{id}/coverage/languages/{lang_id}` (I18nScopeLanguageCoverageUiController)

*   **Twig Templates Verified:**
    *   `languages_list.twig`
    *   `language_translations.list.twig`
    *   `scopes.list.twig`
    *   `scope_details.twig`
    *   `scope_keys.twig`
    *   `domains.list.twig`
    *   `scope_domain_translations.twig`
    *   `scope_language_coverage.twig`

*   **JS Files Verified:**
    *   `languages-with-components.js`
    *   `i18n_translations_list.js`
    *   `i18n-scopes-core.js`
    *   `i18n_scopes_domains.js`
    *   `i18n_scope_keys.js`
    *   `i18n-domains-core.js`
    *   `i18n_scope_domain_translations.js`
    *   `i18n-scope-language-coverage.js`

*   **Buttons & Links Confirmed:**
    *   Sidebar links (NavigationProvider)
    *   Table ID links (JS renderers)
    *   Action buttons (Create, Edit, Assign, Translate)
    *   Coverage drill-down links (JS renderers)

*   **No Invented Flows:** All documented workflows correspond 1:1 with the verified code paths.
