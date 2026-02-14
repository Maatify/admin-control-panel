# I18N OPERATIONAL CONTROL MANUAL

**Status:** Official Single Source of Truth
**Target Audience:** Translators, QA, Product Managers, Non-technical Admins
**Objective:** Complete Operational Control of the I18n System.

---

## 1️⃣ COMPLETE NAVIGATION TREE

This map defines every reachable screen and click path in the system.

*   **Dashboard** (Home)
*   **Languages** (Sidebar Link)
    *   **Create Language** (Button)
    *   **Edit Language** (Button)
    *   **Translations List** (Click on Language ID)
        *   **Edit Translation** (Button on row)
        *   **Clear Translation** (Button on row)
*   **Settings** (Sidebar Group)
    *   **Translations** (Sidebar Sub-group)
        *   **Scopes** (Sidebar Link)
            *   **Create Scope** (Button)
            *   **Scope Details** (Click on Scope ID)
                *   **Assign Domain** (Button)
                *   **Unassign Domain** (Button)
                *   **Scope Keys** (Purple "Keys" Button)
                    *   **Create Key** (Button)
                    *   **Rename Key** (Button)
                *   **Domain Translations** (Click "Translations" on Domain Row)
                *   **Domain Keys** (Click "Keys" on Domain Row)
                *   **Coverage Breakdown** (Click "View Domains" on Coverage Row)
                    *   **Domain Translations** (Click "Go ->" on Domain Row)
        *   **Domains** (Sidebar Link)
            *   **Create Domain** (Button)
            *   **Edit Domain** (Button)

---

## 2️⃣ ENTRY POINT MATRIX

This table defines exactly how screens can be accessed and what is required.

| Screen | Direct URL Access? | Requires Scope? | Requires Domain Assigned? | Requires Active Language? |
| :--- | :--- | :--- | :--- | :--- |
| **Languages List** | Yes | No | No | No |
| **Language Translations** | Yes | No | No | No (Viewable if inactive) |
| **Scopes List** | Yes | No | No | No |
| **Scope Details** | Yes | Yes (ID) | No | No |
| **Scope Keys** | Yes | Yes (ID) | No (View) / Yes (Create) | No |
| **Domain Translations** | Yes | Yes (ID) | **YES** (Strict) | No |
| **Domain Keys** | Yes | Yes (ID) | **YES** (Strict) | No |
| **Coverage Breakdown** | Yes | Yes (ID) | No | Yes (ID) |
| **Domains List** | Yes | No | No | No |

---

## 3️⃣ SCREEN CONTRACTS

### 🌍 Languages List
*   **Purpose:** Registry of all supported languages.
*   **User Sees:** List of languages with ID, Code, Name, Direction, Icon, Sort Order, Status.
*   **User Can Do:** Create new languages, toggle active status, set fallback language, reorder languages.
*   **Prerequisites:** None.
*   **Button Destinations:** "Create" -> Modal. "ID" -> Language Translations.

### 📝 Language Translations
*   **Purpose:** The primary workspace for translators working on a single language.
*   **User Sees:** Flat list of ALL translations for the selected language, filterable by Scope/Domain.
*   **User Can Do:** Edit text values, clear translations (revert to fallback).
*   **Prerequisites:** Language must exist.
*   **Button Destinations:** "Edit" -> Edit Modal. "Clear" -> Delete Confirmation.

### 🌐 Scopes List
*   **Purpose:** Registry of application sections.
*   **User Sees:** List of scopes with ID, Code, Name, Description, Status.
*   **User Can Do:** Create scopes, toggle status.
*   **Prerequisites:** None.
*   **Button Destinations:** "ID" -> Scope Details.

### 🔍 Scope Details
*   **Purpose:** The central hub for configuring a specific section.
*   **User Sees:** Scope metadata, Language Coverage charts, Domain Assignment list.
*   **User Can Do:** Assign/Unassign domains, jump to Keys, jump to specific Domain Translations.
*   **Prerequisites:** Scope ID must exist.
*   **Button Destinations:** "Keys" -> Scope Keys. "Translations" -> Domain Translations. "View Domains" -> Coverage Breakdown.

### 🗝️ Scope Keys
*   **Purpose:** Management of content identifiers (Keys) within a scope.
*   **User Sees:** List of keys created in this scope.
*   **User Can Do:** Create new keys, rename keys, update key descriptions.
*   **Prerequisites:** Scope ID must exist. To **Create**, a Domain must be assigned.
*   **Button Destinations:** "Create" -> Modal.

### 📝 Domain Translations (Scope Context)
*   **Purpose:** Focused translation of ONE domain within ONE scope.
*   **User Sees:** Translations filtered strictly to the selected Domain and Scope.
*   **User Can Do:** Translate keys.
*   **Prerequisites:** Scope must exist. Domain must exist. **Domain must be ASSIGNED to Scope.**
*   **Failure:** If domain is not assigned, the screen will show an error or be inaccessible.

---

## 4️⃣ REACHABILITY MAP

**Scope Details (`/i18n/scopes/{id}`)**
*   **Reachable From:**
    *   Scopes List (Click ID)
    *   Direct URL
*   **Leads To:**
    *   Scope Keys (`/keys`)
    *   Domain Translations (`/domains/{id}/translations`)
    *   Domain Keys (`/domains/{id}/keys`)
    *   Coverage Breakdown (`/coverage/languages/{id}`)

**Language Translations (`/languages/{id}/translations`)**
*   **Reachable From:**
    *   Languages List (Click ID)
    *   Direct URL
*   **Leads To:**
    *   None (Terminal screen)

---

## 5️⃣ STATE PRECONDITIONS & FAILURE BEHAVIOR

### What happens if...

*   **No Domain Assigned?**
    *   You CANNOT create keys for that domain in the scope.
    *   You CANNOT view the "Domain Translations" screen (Access Denied / Error).
    *   The Domain will not appear in the "Create Key" dropdown.

*   **Language Inactive?**
    *   The language disappears from the end-user application.
    *   It remains visible in the Admin Panel.
    *   You CAN still translate it.

*   **Translation Missing?**
    *   The application looks for a "Fallback" language (usually English).
    *   If Fallback is missing, it shows the raw Key ID (e.g., `btn_submit`).

*   **Key Renamed?**
    *   If the code is not updated to match, the text will vanish from the application.
    *   The old translation remains attached to the new key name in the database.

*   **Scope Empty (No Keys)?**
    *   Coverage will show 0% (or N/A).
    *   Translations list will be empty.

---

## 6️⃣ FULL OPERATIONAL FLOWS (EXPANDED)

### A. Language-First Workflow (The Translator's Path)
*Goal: "I want to translate everything into Spanish."*

1.  Click **Languages** in the sidebar.
2.  Find "Spanish" in the list.
3.  Click the blue **ID number**.
4.  **Result:** You see every single key in the system.
5.  Use the **Filter Bar** to search for "Empty Values" (if available) or specific words.
6.  Click **Edit** (Pencil) on a row.
7.  Type the Spanish text.
8.  Click **Save**.
9.  Repeat.

### B. Scope-First Workflow (The Feature Owner's Path)
*Goal: "I am building the Checkout page."*

1.  Click **Settings** -> **Translations** -> **Scopes**.
2.  Click **Create Scope** -> Name it "Checkout".
3.  Click the new **ID** to enter Scope Details.
4.  Scroll to **Assignments**.
5.  Click **Assign**. Select "Buttons" domain.
6.  Click **Assign**. Select "Errors" domain.
7.  Scroll up. Click **Keys** (Purple Button).
8.  Click **Create Key**.
    *   Select "Buttons".
    *   Name: `pay_now`.
9.  Repeat for all keys.

### C. Coverage-Driven Workflow (The QA Path)
*Goal: "What is missing for the release?"*

1.  Click **Settings** -> **Translations** -> **Scopes**.
2.  Click the **ID** of the feature (e.g., "Checkout").
3.  Look at the **Language Coverage** section.
4.  If "French" is 50% (Red/Yellow):
    *   Click **View Domains** next to French.
5.  **Result:** You see "Buttons" is 100%, but "Errors" is 0%.
6.  Click **Go ->** next to "Errors".
7.  **Result:** You are now on the translation screen, filtered for Checkout + Errors + French.
8.  Translate the missing items.

### D. Global Domain Management
*Goal: "We need a new category for 'Legal Terms'."*

1.  Click **Settings** -> **Translations** -> **Domains**.
2.  Click **Create Domain**.
3.  Code: `legal`. Name: "Legal Terms".
4.  **Stop.** You cannot do anything else here.
5.  Go to a **Scope** to use this new domain.

---

## 7️⃣ REVERSE TRACE SECTION

**If you are at: Domain Translations Screen**
*   **You likely came from:** Scope Details -> Assignments List -> "Translations" button.
*   **Your mental model:** "I want to translate the *Buttons* specifically for the *Checkout* page."
*   **Next Action:** Translate keys. Return to Scope Details.

**If you are at: Scope Details Screen**
*   **You likely came from:** Scopes List -> Clicked ID.
*   **Your mental model:** "I need to configure this feature" OR "I need to check if this feature is ready."
*   **Next Action:** Assign domains, Check coverage, or Manage Keys.

**If you are at: Language Translations Screen**
*   **You likely came from:** Languages List -> Clicked ID.
*   **Your mental model:** "I am a translator working through a backlog" OR "I need to fix a typo globally."
*   **Next Action:** Search, Filter, Edit.

---

## 8️⃣ DIFFERENCE CLARITY MATRIX

| Concept A | Concept B | Difference |
| :--- | :--- | :--- |
| **Scope** | **Domain** | A Scope is a *Place* (Checkout). A Domain is a *Topic* (Buttons). |
| **Keys Page** | **Translation Page** | Keys Page creates the *Identifier* (`btn_save`). Translation Page creates the *Text* ("Save"). |
| **Language-First** | **Scope-First** | Language-First shows *everything* for one language. Scope-First shows *everything* for one feature. |
| **Coverage View** | **Direct View** | Coverage tells you *what* is missing. Direct View lets you *fix* it. |

---

## 9️⃣ OPERATIONAL SAFETY RULES

1.  **NEVER unassign a Domain** unless you are 100% sure no keys are using it.
    *   *Consequence:* All text for that domain vanishes from the scope immediately.
2.  **NEVER rename a Key** without talking to a developer.
    *   *Consequence:* The application code will lose the link to the text.
3.  **NEVER change a "Shared" translation** (e.g., "OK") to something specific (e.g., "Agree").
    *   *Consequence:* You change "OK" to "Agree" on *every single page* in the app.
4.  **ALWAYS check Coverage** before approving a release.
    *   *Rule:* If it's not 100%, it's not ready.

---

## 🔟 ZERO DEVELOPER LANGUAGE

*   Use **"Screen"**, not "Route".
*   Use **"Click"**, not "Request".
*   Use **"System"**, not "Backend".
*   Use **"Identifier"**, not "Key ID".
*   Use **"App"**, not "Frontend".

---

**End of Operational Control Manual**
