# I18n User Workflow Book

This guide explains how to manage translations, languages, and coverage in the Admin Panel. It is designed for administrators and translators.

---

## 1️⃣ What is This System?

*   **Scope:** A broad area of your application (e.g., "Client App", "Admin Panel", "Website"). Scopes contain Domains.
*   **Domain:** A specific feature or section within a Scope (e.g., "Authentication", "Profile", "Errors"). Domains contain Keys.
*   **Key:** The unique identifier for a piece of text (e.g., `login.title`, `error.password_required`). Keys are what developers use in the code.
*   **Translation:** The actual text value for a specific Key in a specific Language (e.g., "Welcome" in English, "Bienvenue" in French).
*   **Language:** The target locale (e.g., English `en`, French `fr`).
*   **Coverage:** A percentage showing how much of the content has been translated. 100% means all keys have a translation value.

---

## 2️⃣ Starting Point: The I18n Section

To access the translation management tools:

1.  Open the main **Sidebar Menu**.
2.  Click on **Settings** (usually near the bottom).
3.  Look for the **Translations** group.
4.  Click on **Scopes**.

This is your primary dashboard. From here, you can navigate to everything else.

---

## 3️⃣ Managing Scopes

The **I18n Scopes** screen shows the high-level projects in your system.

**What you see:**
*   **Scope ID & Code:** Technical identifiers (e.g., `client`, `admin`).
*   **Name:** Human-readable name (e.g., "Client Application").
*   **Status:** Whether the scope is Active or Inactive.

**Actions:**
*   **Search/Filter:** Use the form at the top to find a specific scope.
*   **Create Scope:** (If you have permission) Add a new scope.
*   **View Details:** Click on a row (or the eye icon) to open the **Scope Dashboard**.

---

## 4️⃣ Inside a Scope (Scope Dashboard)

Clicking a Scope opens its details page. This is where you see the health of your translations.

### Section 1: Overview
Shows the basic details of the scope (Name, Code, Sort Order).

### Section 2: Language Coverage
This table is your **progress report**. It lists every language in the system and shows:
*   **Completion:** A percentage bar (Green = Complete, Yellow = In Progress, Red = Low).
*   **Keys:** Total number of translation keys in this scope.
*   **Missing:** Number of keys that *do not* have a translation for this language.
*   **Action:** Click **"View Domains"** to drill down into a specific language.

### Section 3: Domain Assignments
This table lists all Domains (features) assigned to this Scope.
*   **Status:** Shows if a domain is actively assigned.
*   **Actions:**
    *   **View Keys:** See the list of keys defined in this domain.
    *   **View Translations:** Open the translation editor for this specific domain.

---

## 5️⃣ Editing Translations (Step-by-Step)

To translate text, follow this flow:

**Step 1: Open the Scope**
Go to **Settings > Translations > Scopes** and click the Scope you want to work on.

**Step 2: Choose a Path**
*   *Option A (By Language):* Look at the **Language Coverage** table. Find your target language (e.g., French). Click **"View Domains"**.
*   *Option B (Direct):* Scroll to **Domain Assignments**, find the feature (e.g., "Auth"), and click **"View Translations"**.

**Step 3: The Translation Editor**
You are now viewing the **Translations List** for a specific Domain.

**The Interface:**
*   **Filters:** At the top, you can filter by:
    *   **Key Part:** Search for a specific text key (e.g., `button.save`).
    *   **Language:** Select "All Languages" or a specific one (e.g., "Spanish"). *Note: If you came from the Coverage page, this is already selected for you.*
    *   **Value:** Search for existing text.
*   **The Table:**
    *   **Key Part:** The technical ID of the text.
    *   **Language:** Which language this row belongs to.
    *   **Value:** The current text. If empty, it will show "Empty".

**Step 4: Editing**
1.  Find the row you want to change.
2.  Click the **Edit (Pencil)** icon.
3.  A modal window opens showing the Key and Language.
4.  Type the new text in the **Translation Value** box.
5.  Click **Save Changes**.

**Feedback:**
*   A success message ("Translation saved successfully") will appear at the top.
*   The table will refresh automatically to show your new text.

---

## 6️⃣ Understanding Coverage

Coverage helps you find what work is left to do.

**The Numbers:**
*   **Total Keys:** The count of all text items developers have created for this area.
*   **Translated:** How many have text saved in the database.
*   **Missing:** `Total - Translated`. This is your "To Do" list count.

**Drill-Down Reports:**
1.  In the Scope Dashboard, click **"View Domains"** next to a language.
2.  You will see the **Coverage Breakdown** page.
3.  This lists every Domain (feature).
4.  **Sorting:** By default, it sorts by **Missing Count (Highest First)**. This brings the most incomplete areas to the top.
5.  **Action:** Click **"Go"** next to a domain to jump straight to the Translation Editor for that domain/language combination.

---

## 7️⃣ Common User Scenarios

### Scenario A: "I want to translate everything for French."
1.  Navigate to **Settings > Translations > Scopes**.
2.  Click on the relevant Scope (e.g., "Client App").
3.  Scroll to the **Language Coverage** table.
4.  Find the row for **French**.
5.  Click **"View Domains"**.
6.  You will see a list of domains. Look for the top row (highest missing count).
7.  Click **"Go"**.
8.  You are now in the editor with French selected. Fill in the "Empty" values using the Edit button.

### Scenario B: "I want to fix a specific typo."
1.  Go to **Settings > Translations > Scopes**.
2.  Open the Scope where the text appears.
3.  Scroll to **Domain Assignments**.
4.  Find the Domain (feature) where the typo is (e.g., if it's on the login screen, choose "Auth").
5.  Click **"View Translations"**.
6.  Use the **Value Filter** at the top to search for the word with the typo.
7.  Click **Search**.
8.  Click the **Edit** button on the result row and fix it.

### Scenario C: "I want to check which domains are incomplete."
1.  Go to the **Scope Dashboard**.
2.  Look at the **Language Coverage** table.
3.  Pick the language you are concerned about.
4.  Click **"View Domains"**.
5.  The resulting list gives you a prioritized checklist of incomplete areas.

---

## 8️⃣ Hidden Logic (Explained Simply)

*   **Why coverage updates instantly:** The system recalculates stats every time you save a translation. Your progress bars update in real-time.
*   **Why a domain might be missing:** A Domain must be explicitly "Assigned" to a Scope to appear in the coverage reports. If a developer creates a new Domain but forgets to assign it, it won't show up here until assigned.
*   **Pre-selection:** If you navigate from a Coverage report using the "Go" button, the system remembers which language you were looking at and automatically filters the translation list for you.
