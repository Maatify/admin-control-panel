# I18n User Workflow Book

This is the definitive guide to navigating and using the Internationalization (I18n) system in the Admin Panel. It describes the real behavior of the system, step-by-step.

---

## 1️⃣ What is This System?

*   **Scope:** A high-level container for your project (e.g., "Client App", "Admin Panel", "Marketing Site").
*   **Domain:** A functional area within a Scope (e.g., "Authentication", "Profile", "Checkout").
*   **Key:** A unique identifier for a text string (e.g., `login.submit_button`). Keys belong to a Domain.
*   **Language:** A target locale (e.g., English, French).
*   **Translation:** The text value for a specific Key in a specific Language.
*   **Coverage:** The percentage of Keys that have a Translation for a specific Language.

---

## 2️⃣ Starting Point: The I18n Section

All translation management starts from the main sidebar.

1.  Look for the **Settings** group in the sidebar.
2.  Expand **Translations**.
3.  You will see two options:
    *   **Scopes** (Main Entry Point)
    *   **Domains** (Global Management)

*Most work happens inside **Scopes**.*

---

## 3️⃣ Managing Scopes

**Route:** Settings > Translations > Scopes

This screen lists all the projects (Scopes) defined in the system.

### The Scopes Table
*   **ID:** The internal database ID.
*   **Code:** The technical identifier used by developers (e.g., `client_app`).
*   **Name:** The human-readable name.
*   **Active:** Whether this scope is currently used.
*   **Sort Order:** The display order.

### Actions
*   **Search:** Use the filter bar at the top to find scopes by Name or Code.
*   **Create:** Click the "Create Scope" button to add a new one. You must provide a unique Code and Name.
*   **View Keys:** Click the "Keys" button to see all text keys defined in this scope.
*   **Details:** Click the **ID** (blue link) to open the **Scope Dashboard**.

---

## 4️⃣ Inside a Scope (The Dashboard)

**Route:** Click a Scope ID from the list.

This dashboard gives you a complete health check of the Scope. It has three main sections.

### A. Overview
Shows the Scope's metadata (Name, Code, Status).

### B. Language Coverage
This table shows how well-translated this Scope is for each language.
*   **Language:** The name of the language.
*   **Completion:** A progress bar (Green/Yellow/Red).
*   **Keys:** Total keys in this scope.
*   **Missing:** Number of keys waiting for translation.
*   **Action:** Click **"View Domains"** to see exactly *which* parts of the scope are missing translations for that language.

### C. Domain Assignments
This table controls which features (Domains) belong to this Scope.
*   **Assigned:** Green badge means the domain is active in this scope.
*   **Not Assigned:** Gray badge means it is available but not linked.
*   **Actions:**
    *   **Assign:** Link a domain to this scope.
    *   **Unassign:** Remove a domain from this scope.
    *   **View Translations:** (If assigned) Click the "Translation" icon/button to edit text for this domain.

---

## 5️⃣ Editing Translations (Step-by-Step)

There are multiple ways to reach the translation editor. Here is the standard flow:

### Flow: Scope → Domain
1.  Go to **Settings > Translations > Scopes**.
2.  Click the **ID** of your target Scope.
3.  Scroll down to **Domain Assignments**.
4.  Find the Domain you want to edit (e.g., "Auth").
5.  Click the **View Translations** button (or "Translations" icon).

### The Editor Screen
You are now viewing the translations for **one Scope** and **one Domain**.

*   **Filters:**
    *   **Language:** Use the dropdown to select a specific language (e.g., "French").
    *   **Key:** Search for a specific key (e.g., `password`).
    *   **Value:** Search for existing text.
*   **The Table:**
    *   Shows the Key, Language, and current Value.
    *   If a value is missing, it shows "Empty".

### How to Edit
1.  Find the row you want to change.
2.  Click the **Edit (Pencil)** button.
3.  A popup window appears.
4.  Type the new text.
5.  Click **Save Changes**.
6.  The table updates instantly, and a success message appears.

---

## 6️⃣ Understanding Coverage

Coverage tells you "How much work is left?".

*   **100%:** Every key in every assigned domain has a value.
*   **Missing Count:** The exact number of empty translations.

**Drill-Down Flow:**
1.  On the Scope Dashboard, look at the **Language Coverage** table.
2.  If "French" says "50 Missing", click **"View Domains"**.
3.  You will see a new page: **Coverage Breakdown**.
4.  This lists every Domain, sorted by the number of missing keys (worst first).
5.  Find the Domain with the most missing items.
6.  Click **"Go"**.
7.  This takes you directly to the Translation Editor, with "French" *already selected* for you.

---

## 7️⃣ Real User Scenarios

### Scenario 1: "Translate entire French language"
1.  Go to **Settings > Translations > Scopes**.
2.  Click the Scope ID.
3.  In **Language Coverage**, find French.
4.  Click **"View Domains"**.
5.  Click **"Go"** on the top Domain.
6.  Edit all rows marked "Empty".
7.  Use the browser "Back" button to return to the list and pick the next Domain.

### Scenario 2: "Fix typo in login button"
1.  Go to **Settings > Translations > Scopes**.
2.  Click the Scope ID.
3.  Scroll to **Domain Assignments**.
4.  Find the "Auth" (or relevant) Domain.
5.  Click **"Assign"** if it's not assigned (unlikely if you are fixing a typo, but check status).
6.  Click **View Translations**.
7.  In the **Value** filter at the top, type the word with the typo.
8.  Click **Search**.
9.  Click **Edit** on the result and fix it.

### Scenario 3: "Add new domain and translate it"
1.  Go to **Settings > Translations > Domains**.
2.  Click **Create Domain**.
3.  Enter Code (e.g., `checkout`) and Name (e.g., "Checkout Flow").
4.  Go to **Settings > Translations > Scopes**.
5.  Click the Scope ID.
6.  Scroll to **Domain Assignments**.
7.  Find "Checkout Flow" (it will say "Not Assigned").
8.  Click **Assign**.
9.  Click **View Translations**.
10. Start adding translations (Keys must be created first via the "Keys" screen or by developers).

### Scenario 4: "Add new key and translate it"
1.  Go to **Settings > Translations > Scopes**.
2.  Find the Scope.
3.  Click the **"Keys"** button (in the Actions column).
4.  Click **Create Key**.
5.  Select the Domain (e.g., "Auth") and enter the Key Part (e.g., `forgot_password`).
6.  Click **Save**.
7.  Go back to the Scope Dashboard.
8.  Go to **Domain Assignments** > "Auth" > **View Translations**.
9.  You will see the new key with "Empty" values for all languages.
10. Translate it.

---

## 8️⃣ Hidden Logic (Behavior)

*   **Why a Domain is missing:** You must **Assign** a Domain to a Scope before you can translate it in that context. Check the "Domain Assignments" list.
*   **Why Coverage updates:** The system recalculates statistics immediately after you save a translation.
*   **Pre-selection:** When you click "Go" from the Coverage Breakdown page, the system remembers which language you were looking at and applies that filter automatically to the editor.
