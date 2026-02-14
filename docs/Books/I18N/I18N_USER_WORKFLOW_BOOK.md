# I18N OPERATIONAL HANDBOOK

**Version:** 1.0
**Target Audience:** Translators, Content Managers, Project Leads, QA
**Objective:** The Single Source of Truth for operating the Internationalization System.

---

## 1️⃣ System Purpose

This system is designed to solve a specific problem: **How do we manage text in multiple languages efficiently, without losing context?**

In traditional systems, text is often buried in code or scattered across spreadsheets. This leads to broken translations, missing context, and duplicate work.

Our system organizes text into a strict hierarchy:
1.  **Scopes:** Define *where* the text appears (e.g., "Checkout Page").
2.  **Domains:** Define *what* kind of text it is (e.g., "Error Messages").
3.  **Keys:** Identify the specific phrase (e.g., `invalid_email`).
4.  **Translations:** Provide the actual words in each language.

**Why this matters:**
*   **Context:** Translators know *exactly* where text appears (Scope).
*   **Consistency:** The same error message (Domain) can be reused across different parts of the app.
*   **Coverage:** The system tracks completion percentages, so you never launch with missing translations.

---

## 2️⃣ Mental Model (Visual & Simple)

Think of the system as a **Digital Library**.

*   **The Building (The App):** The entire application.
*   **The Floors (Languages):** Each floor is a different language version (English Floor, Arabic Floor, etc.).
*   **The Sections (Scopes):** Each floor has sections like "Fiction" (User Profile), "Science" (Checkout), "History" (Admin Panel).
*   **The Books (Domains):** Inside each section, books are categorized by topic (Buttons, Errors, Labels).
    *   *Crucial Rule:* A book (Domain) exists in the library catalog, but it only appears on the shelf if you **Assign** it to that Section (Scope).
*   **The Pages (Keys):** Inside the book are pages with specific titles (Keys).
*   **The Text (Translations):** The actual words written on the page.

**Visual Hierarchy:**

```
[ LIBRARY: Your App ]
 │
 ├── [ FLOOR: English ]
 │    │
 │    └── [ SECTION: User Profile ]
 │         │
 │         ├── [ BOOK: Buttons ] (Assigned)
 │         │    ├── Page: "Save"
 │         │    └── Page: "Cancel"
 │         │
 │         └── [ BOOK: Error Messages ] (Assigned)
 │              ├── Page: "Invalid Email"
 │              └── Page: "Required Field"
 │
 └── [ FLOOR: Arabic ]
      │
      └── [ SECTION: User Profile ]
           │
           ├── [ BOOK: Buttons ] (Assigned)
           │    ├── Page: "حفظ" (Save)
           │    └── Page: "إلغاء" (Cancel)
           ...
```

---

## 3️⃣ Complete Navigation Map (User-Level)

This map shows exactly where to click to navigate the system.

**1. Dashboard (Home)**
*   Your starting point.

**2. Sidebar -> Languages**
*   **Screen:** List of all languages.
*   **Click:** The Blue ID Number of a language.
*   **Goes to:** **Language Translations List**.
    *   *Action:* Translate any key for that specific language.

**3. Sidebar -> Settings -> Translations -> Scopes**
*   **Screen:** List of all application sections (Scopes).
*   **Click:** The Blue ID Number of a scope.
*   **Goes to:** **Scope Details**.
    *   *View:* Language Coverage Charts (Pie charts).
    *   *View:* Domain Assignments (List of books in this section).
    *   **Click:** "Keys" button (Purple).
        *   **Goes to:** **Scope Keys Management**.
            *   *Action:* Create new keys.
    *   **Click:** "Translations" button (Blue Eye) on a Domain row.
        *   **Goes to:** **Domain Translations List**.
            *   *Action:* Translate keys for this specific domain.
    *   **Click:** "View Domains" link on a Language Coverage row.
        *   **Goes to:** **Coverage Breakdown**.
            *   *Action:* See which domains are missing translations for that language.

**4. Sidebar -> Settings -> Translations -> Domains**
*   **Screen:** Global catalog of Domains (Books).
*   **Action:** Create new Categories.
*   **Note:** You cannot add keys here. You must go to a Scope first.

---

## 4️⃣ All Operational Workflows

### A. Translator Workflow
*Goal: I need to translate the "User Profile" section into Arabic.*

1.  Go to **Sidebar -> Scopes**.
2.  Click the **ID** for "User Profile".
3.  Scroll to **Language Coverage**.
4.  Find "Arabic" and look at the pie chart.
5.  Click **View Domains** next to Arabic.
6.  You see a list of Domains (e.g., Buttons, Errors).
7.  Click **Go ->** next to a Domain with < 100% completion.
8.  You are now in the Translation Editor.
9.  Click the **Edit (Pencil)** button next to empty values.
10. Enter the Arabic text and click **Save**.
11. Repeat until Coverage is 100%.

### B. Project Setup Workflow
*Goal: I am launching a new "Checkout" feature.*

1.  **Define Categories:**
    *   Go to **Domains**. Do we have a "Checkout" domain? If not, **Create Domain**.
2.  **Define Section:**
    *   Go to **Scopes**. **Create Scope** called "Checkout".
3.  **Assign Categories:**
    *   Click the **ID** of the new "Checkout" scope.
    *   Scroll to "Domain Assignments".
    *   Search for "Checkout" domain (and others like "Buttons", "Errors").
    *   Click **Assign** (Green +) for each.
4.  **Create Content:**
    *   Click the **Keys** button (Purple) at the top.
    *   Click **Create Key**.
    *   Select Domain ("Checkout") -> Enter Key Name (`page_title`).
    *   Repeat for all text in the design.
5.  **Translate:**
    *   Go to **Sidebar -> Languages**.
    *   Click the **ID** for "English".
    *   Filter by Scope "Checkout".
    *   Translate all keys.

### C. Coverage Recovery Workflow
*Goal: We launched, but users report missing text in French.*

1.  Go to **Sidebar -> Scopes**.
2.  Click the **ID** of the reported scope.
3.  Check the **Language Coverage** chart for French.
4.  If it's not 100%, click **View Domains**.
5.  Identify which Domain is incomplete (e.g., "Errors" is at 50%).
6.  Click **Go ->**.
7.  Filter for "Empty/Missing" values.
8.  Translate immediately.

### D. Quality Control Workflow
*Goal: Ensure all translations are contextually correct.*

1.  Go to **Sidebar -> Languages**.
2.  Click the **ID** for the target language.
3.  Use the **Global Search** to find specific terms (e.g., "Submit").
4.  Verify that "Submit" is translated consistently across all Scopes.
5.  If a translation is wrong for a specific context (Scope), Click **Edit** and correct it.

---

## 5️⃣ Role-Based Usage Guide

### 👨‍💻 Developer
*   **Responsibility:** creating the structure.
*   **Primary Screens:** Scopes, Domains, Scope Keys.
*   **Task:** Create Scopes, Create Domains, Assign Domains, Create Keys.
*   **Never:** Guess translations. Leave them empty or set a fallback.

### 👩‍💼 Product Manager / Content Lead
*   **Responsibility:** Defining the text.
*   **Primary Screens:** Scope Keys, Language Translations (Base Language).
*   **Task:** Review Key names for clarity. Enter the initial "English" (or base) text.

### 🌎 Translator
*   **Responsibility:** Localizing content.
*   **Primary Screens:** Language Translations, Coverage Breakdown.
*   **Task:** Monitor Coverage % and fill in blanks.
*   **Never:** Create Keys or Domains.

### 🕵️ QA Specialist
*   **Responsibility:** Verifying completeness.
*   **Primary Screens:** Scope Details (Coverage Charts).
*   **Task:** Check that all Scopes are 100% covered for supported languages before release.

---

## 6️⃣ Screen Roles & Boundaries

**Scope Page vs. Domain Page**
*   **Scope Page:** The "Workbench". This is where you connect things. You spend 90% of your setup time here.
*   **Domain Page:** The "Catalog". You only go here to add a new *type* of content. You rarely visit this.

**Keys Page vs. Translation Page**
*   **Keys Page:** Defines *identifiers* (`btn_submit`). No text exists here, only the *concept* of the text.
*   **Translation Page:** Defines *content* ("Submit"). No identifiers are created here, only the *words* for a specific language.

**Language-First vs. Scope-First**
*   **Language-First:** "I am a translator." -> I want to see *everything* in Spanish, regardless of where it appears.
*   **Scope-First:** "I am a feature owner." -> I want to see *everything* about the Checkout page, regardless of language.

---

## 7️⃣ Common Mistakes & How to Avoid Them

### ❌ Mistake 1: Creating a Domain but forgetting to Assign it.
*   **Symptom:** You go to create a Key, but the dropdown is empty.
*   **Fix:** Go to **Scope Details**, find the Domain in the list, and click **Assign**.

### ❌ Mistake 2: Translating in the wrong place.
*   **Symptom:** You edit a translation, but it changes everywhere.
*   **Reality:** Translations are tied to a Key + Domain. If you share the "Buttons" domain across scopes, changing "Save" changes it globally.
*   **Fix:** If a scope needs a *different* translation for "Save", create a specific key (e.g., `checkout_save`) in a specific domain.

### ❌ Mistake 3: Ignoring Coverage Charts.
*   **Symptom:** Users see fallback text (English) instead of their language.
*   **Fix:** QA must check **Scope Details** coverage charts before every release.

---

## 8️⃣ Strategic Usage Patterns

### The "Shared vs. Specific" Strategy
*   Use a **Shared Domain** (e.g., "Global Buttons") for generic terms like "OK", "Cancel", "Back".
*   Use a **Specific Domain** (e.g., "Checkout Labels") for unique terms like "Place Order", "Billing Address".
*   *Why?* This keeps your translation memory clean while allowing flexibility.

### The "Weekly Audit" Pattern
1.  Every Friday, Project Lead checks **Scope List**.
2.  Sort scopes by "Newest".
3.  Check Coverage % for all target languages.
4.  Assign missing work to translators immediately.

---

## 9️⃣ Practical Quick Guides

### "I want to translate everything into Arabic."
1.  Sidebar -> Languages.
2.  Click Arabic ID.
3.  Filter by "Empty Values".
4.  Start typing.

### "I want to add a new feature section."
1.  Sidebar -> Settings -> Translations -> Scopes.
2.  Create Scope.
3.  Click ID.
4.  Assign Domains (Buttons, Errors, etc.).
5.  Click Keys -> Create Keys.

### "I want to fix one word."
1.  Sidebar -> Languages.
2.  Click Language ID.
3.  Use Global Search (top of screen).
4.  Type the word.
5.  Edit and Save.

### "I want to see what’s missing."
1.  Sidebar -> Scopes.
2.  Click Scope ID.
3.  Look at the colored pie charts.
4.  Red/Yellow = Work needed.

---

**End of Handbook**
