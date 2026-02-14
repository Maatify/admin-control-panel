# I18N OPERATIONAL MANUAL

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

## 2️⃣ ALL ENTRY POINTS

This table defines exactly how screens can be accessed and what is required.

| Screen | Entry Path | Requirements |
| :--- | :--- | :--- |
| **Languages Screen** | Sidebar -> Languages | None. |
| **Language Translations** | Languages Screen -> Click ID | Language must exist. (Active or Inactive). |
| **Scopes Screen** | Sidebar -> Settings -> Translations -> Scopes | None. |
| **Scope Details** | Scopes Screen -> Click ID | Scope must exist. |
| **Scope Keys** | Scope Details -> Click "Keys" | Scope must exist. (Domain required to create keys). |
| **Domain Translations** | Scope Details -> Assignments -> Click "Translations" | **Domain must be ASSIGNED to Scope.** |
| **Coverage Breakdown** | Scope Details -> Coverage -> Click "View Domains" | Scope must exist. |
| **Domains Screen** | Sidebar -> Settings -> Translations -> Domains | None. |

---

## 3️⃣ ALL OPERATIONAL FLOWS

### A. Language-First Workflow (Translator)
*Goal: Translate everything for a specific language.*
1.  Go to **Languages** screen.
2.  Click the blue **ID** of your target language (e.g., "Spanish").
3.  You see a list of ALL keys in the system.
4.  Use the filter bar to search for "Empty Values" (if available) or specific keywords.
5.  Click **Edit** (Pencil) on a row.
6.  Enter the translation.
7.  Click **Save**.

### B. Scope-First Workflow (Feature Owner)
*Goal: Translate everything for a specific feature (e.g., Checkout).*
1.  Go to **Scopes** screen.
2.  Click the **ID** of the feature (e.g., "Checkout").
3.  Scroll to "Domain Assignments".
4.  Click **Translations** (Eye icon) next to a specific Domain (e.g., "Buttons").
5.  You see only keys for "Checkout" + "Buttons".
6.  Translate the keys.

### C. Coverage-First Workflow (QA / Manager)
*Goal: Find and fix missing translations.*
1.  Go to **Scopes** screen.
2.  Click the **ID** of the feature.
3.  Look at the **Language Coverage** section.
4.  If a language is not 100% (Red/Yellow bar):
    *   Click **View Domains** next to that language.
5.  Identify which Domain has missing keys.
6.  Click **Go ->** next to that Domain.
7.  You are taken to the translation screen, pre-filtered for the missing items.

### D. Structure Creation Workflow (Developer)
*Goal: Set up a new feature area.*
1.  **Create Domain:** Go to **Domains**. Click **Create Domain**. Name it (e.g., "Checkout Labels").
2.  **Create Scope:** Go to **Scopes**. Click **Create Scope**. Name it (e.g., "Checkout").
3.  **Assign:** Click the new Scope ID. Scroll to Assignments. Click **Assign** and select "Checkout Labels".
4.  **Create Keys:** Click **Keys** (Purple button). Click **Create Key**. Select "Checkout Labels" -> Enter Key Name.

### E. Release Audit Workflow (Release Manager)
*Goal: Verify system is ready for deployment.*
1.  Go to **Scopes**.
2.  Scan the list.
3.  Verify **Coverage** column shows 100% for all required languages.
4.  If any Scope is < 100%, perform **Coverage-First Workflow**.
5.  Go to **Languages**.
6.  Ensure all required languages are **Active**.

### F. New Language Workflow (Admin)
*Goal: Add support for a new language (e.g., French).*
1.  Go to **Languages**.
2.  Click **Create Language**.
3.  Enter Name ("French"), Code ("fr"), Direction ("LTR").
4.  Set **Fallback Language** (usually "English").
5.  **Result:** Coverage starts at 0%. Fallback text will be shown until translations are added.

### G. Fix Single Typo Workflow (Anyone)
*Goal: Fix a mistake seen on screen.*
1.  Go to **Languages**.
2.  Click the **ID** of the language with the typo.
3.  Type the incorrect word into the **Global Search** bar.
4.  Find the specific key (check Scope/Domain columns to confirm context).
5.  Click **Edit**. Correct typo. Click **Save**.

### H. Add New Feature Workflow (Developer)
*Goal: Add a new button to an existing page.*
1.  Go to **Scopes**. Click the Scope ID (e.g., "User Profile").
2.  Click **Keys** (Purple Button).
3.  Click **Create Key**.
4.  Select the relevant Domain (e.g., "Buttons").
5.  Enter Key Name (e.g., `btn_export_pdf`).
6.  **Translation:** Go to **Languages** -> English -> Search `btn_export_pdf` -> Add text "Export PDF".

---

## 4️⃣ ALL BRANCHES & PRECONDITIONS

**If Domain is NOT Assigned:**
*   You **cannot** create keys for that domain in the scope.
*   You **cannot** view the Domain Translations screen.
*   The Domain will not appear in the "Create Key" dropdown.

**If Scope is Empty (No Keys):**
*   Coverage will show 0% or "N/A".
*   Translations list will be empty.

**If Language is Inactive:**
*   It disappears from the end-user application menus.
*   It remains visible and editable in the Admin Panel.

**If Translation is Missing:**
*   The system checks the **Fallback Language** setting.
*   If Fallback exists, that text is shown.
*   If Fallback is missing, the raw **Key Identifier** (e.g., `btn_save`) is shown.

**If Key is Renamed:**
*   The connection to the application code breaks immediately.
*   The old translation remains attached to the new key name in the database.
*   **Result:** Users see "Missing Translation" or raw key until code is updated.

**If Domain is Unassigned:**
*   All keys and translations for that domain in that scope become inaccessible to the application.
*   They are NOT deleted from the database, just hidden.

---

## 5️⃣ MENTAL MODEL DIAGRAM

**Visual Hierarchy:**

```
[ LIBRARY (Your App) ]
  │
  ├── [ FLOOR: English ]
  ├── [ FLOOR: Spanish ]
  │
  └── [ SECTION: User Profile (Scope) ]
       │
       ├── [ BOOK: Buttons (Domain) ]  <-- Must be ASSIGNED
       │    │
       │    ├── Page: "save" (Key)
       │    │    └── Text: "Guardar" (Translation)
       │    │
       │    └── Page: "cancel" (Key)
       │         └── Text: "Cancelar" (Translation)
       │
       └── [ BOOK: Errors (Domain) ]   <-- Must be ASSIGNED
            │
            └── Page: "invalid_email" (Key)
                 └── Text: "Email inválido" (Translation)
```

**Key Concepts:**
*   **Scope:** The *Place* (Where?)
*   **Domain:** The *Category* (What?)
*   **Key:** The *Identifier* (Which one?)
*   **Translation:** The *Content* (The words)

---

## 6️⃣ SCREEN CONTRACTS

### 🌍 Languages Screen
*   **Purpose:** Registry of all supported languages.
*   **User Sees:** List of languages, active status, completion.
*   **User Can:** Create, Edit, Toggle Active, Reorder.
*   **User Cannot:** Delete languages (usually restricted).
*   **Leads To:** Language Translations List.

### 📝 Language Translations Screen
*   **Purpose:** Global translation editor.
*   **User Sees:** Every translation in the system for one language.
*   **User Can:** Edit text, Clear text (delete).
*   **Required:** Language must exist.
*   **Leads To:** Nowhere (Terminal).

### 🔍 Scope Details Screen
*   **Purpose:** Configuration hub for a feature.
*   **User Sees:** Coverage charts, Assigned Domains list.
*   **User Can:** Assign/Unassign domains, Audit coverage.
*   **Required:** Scope must exist.
*   **Leads To:** Keys, Domain Translations, Coverage Breakdown.

### 🗝️ Scope Keys Screen
*   **Purpose:** Manage identifiers.
*   **User Sees:** List of keys (e.g., `btn_save`).
*   **User Can:** Create new keys, Rename keys.
*   **User Cannot:** Enter translations.
*   **Required:** Domain must be assigned to create a key.
*   **Leads To:** Create Key Modal.

---

## 7️⃣ ROLE BOUNDARIES

### 👨‍💻 Developer Role
*   **Must Do:** Create Scopes, Create Domains, Assign Domains, Create Keys.
*   **NEVER Do:** Enter final translations (placeholder text only).
*   **NEVER Do:** Rename keys without updating the codebase.

### 🌎 Translator Role
*   **Must Do:** Enter text values in Language Translations screen.
*   **Must Do:** Use Coverage Breakdown to find work.
*   **NEVER Do:** Unassign domains.
*   **NEVER Do:** Rename keys.

### 👩‍💼 Product Role
*   **Must Do:** Define the names of keys (semantics).
*   **Must Do:** Review "English" text for tone.
*   **NEVER Do:** Change technical structure (Assignments).

### 🕵️ QA Role
*   **Must Do:** audit Coverage % before release.
*   **Must Do:** Verify Fallbacks are set.
*   **NEVER Do:** Create content directly (report issues instead).

### 🛡️ Admin Role
*   **Must Do:** Manage Languages (Create, Active/Inactive).
*   **Must Do:** Manage global settings.

---

## 8️⃣ SAFETY RULES

1.  **NEVER Unassign a Domain** unless you are certain it is unused.
    *   *Result:* Immediate disappearance of text from the app.
2.  **NEVER Rename a Key** without developer coordination.
    *   *Result:* Code breaks, user sees raw key name (`btn_submit`).
3.  **NEVER Change Shared Text** (e.g., "OK") to specific text (e.g., "Accept").
    *   *Result:* It changes on *every* page in the application. Create a new key instead.
4.  **ALWAYS Check Coverage** before release.
    *   *Result:* Prevents users from seeing missing text.
5.  **ALWAYS Set Fallback** for new languages.
    *   *Result:* Ensures users see English instead of errors if a translation is missing.

---

## 9️⃣ ZERO DEVELOPER LANGUAGE RULE

*   **Screen** (Not Route)
*   **System** (Not API/Backend)
*   **Click** (Not Request)
*   **Identifier** (Not Key ID)
*   **App** (Not Frontend)
*   **Value** (Not String/DTO)

---

**End of Operational Manual**
