# I18N ENTERPRISE OPERATIONS PLAYBOOK

**Version:** 2.0 (Enterprise Edition)
**Authority:** Operational Governance Board
**Objective:** Complete Operational Mastery of the Internationalization System.

---

## 1️⃣ System Architecture Mental Model (Operational)

This section defines the operational physics of the I18n system. Understanding these dependencies is critical to preventing architectural decay.

### The Six Operational Lifecycles

1.  **Scope Lifecycle:**
    *   *Creation:* Born when a new feature or distinct UI section is architected (e.g., "Checkout", "User Profile").
    *   *Role:* Acts as a container for context. It answers "Where does this text live?".
    *   *Dependency:* Independent. Does not depend on other entities.

2.  **Domain Lifecycle:**
    *   *Creation:* Born when a new category of text is identified (e.g., "Buttons", "Legal Disclaimers").
    *   *Role:* Acts as a classifier. It answers "What kind of text is this?".
    *   *Dependency:* Global. Exists independently of Scopes but is useless until Assigned.

3.  **Assignment Lifecycle:**
    *   *Creation:* The act of linking a **Domain** to a **Scope**.
    *   *Role:* The bridge that allows a Scope to use keys from a Domain.
    *   *Critical Rule:* You cannot create keys in a Scope for a Domain that hasn't been assigned.

4.  **Key Lifecycle:**
    *   *Creation:* Born inside a specific **Scope + Domain** intersection.
    *   *Role:* The unique identifier used by code (e.g., `checkout.buttons.submit`).
    *   *Dependency:* Strictly requires an active Assignment.

5.  **Translation Lifecycle:**
    *   *Creation:* The text value assigned to a Key for a specific **Language**.
    *   *Role:* The end-user visible content.
    *   *Dependency:* Requires an existing Key.

6.  **Coverage Lifecycle:**
    *   *Calculation:* Automatically aggregated percentage of Keys that have valid Translations for a specific Language within a Scope.
    *   *Role:* The primary metric for Release Readiness.

---

## 2️⃣ Full Navigation Decision Tree

Use this decision map to determine your exact path through the system.

### 🟢 Goal: Manage Content (Translations)

*   **Scenario A: I need to translate an entire language.**
    *   **Go to:** `Sidebar -> Languages`
    *   **Click:** Target Language ID (e.g., Arabic).
    *   **Action:** You are now in the global translation list for that language. Use filters to work through keys.

*   **Scenario B: I need to fix a specific typo I saw on a screen.**
    *   **Go to:** `Sidebar -> Languages`
    *   **Click:** Target Language ID.
    *   **Action:** Use "Global Search" to find the text. Edit and Save.

*   **Scenario C: I need to translate a specific feature (Scope).**
    *   **Go to:** `Sidebar -> Settings -> Translations -> Scopes`
    *   **Click:** Scope ID (e.g., "Checkout").
    *   **Check:** "Language Coverage" charts.
    *   **Click:** "View Domains" on the target language row.
    *   **Click:** "Go ->" on the incomplete Domain.
    *   **Action:** You are now filtered to only that Scope, Domain, and Language.

### 🟡 Goal: Manage Structure (Architecture)

*   **Scenario D: I am building a new feature.**
    *   **Step 1 (Check Domains):** Go to `Sidebar -> Settings -> Translations -> Domains`. Does a category exist for your text? If not, Create Domain.
    *   **Step 2 (Create Scope):** Go to `Sidebar -> Settings -> Translations -> Scopes`. Create a new Scope for your feature.
    *   **Step 3 (Connect):** Click the new Scope ID. Scroll to "Assignments". Assign your Domains.
    *   **Step 4 (Create Keys):** Click "Keys" (Purple Button). Create your keys.

*   **Scenario E: I need to add a new Language.**
    *   **Go to:** `Sidebar -> Languages`.
    *   **Click:** "Create Language".
    *   **Config:** Set Name, Code, Direction (LTR/RTL), and Fallback.
    *   **Result:** Coverage for this new language starts at 0% across all Scopes.

### 🔴 Goal: Audit & Governance

*   **Scenario F: I need to verify we are ready for release.**
    *   **Go to:** `Sidebar -> Settings -> Translations -> Scopes`.
    *   **Action:** Scan the list. Any Scope with < 100% coverage for supported languages is a **BLOCKER**.

---

## 3️⃣ Feature Lifecycle Governance

### Policy: When to Create vs. Reuse

| Entity | Create New When... | Reuse Existing When... |
| :--- | :--- | :--- |
| **Scope** | You are building a distinct new page, module, or independent feature set. | You are adding a minor sub-feature to an existing page. |
| **Domain** | You have a new *class* of text (e.g., "Validation Errors") not covered by existing domains. | The text fits an existing category (e.g., "Buttons", "Labels"). |
| **Key** | The context is specific to this feature (e.g., "Pay Now"). | The text is truly global and identical in *all* contexts (e.g., "OK", "Cancel"). |

### Policy: Shared Global Domains
*   **Definition:** Domains like "Global Buttons" or "Common Errors" intended for reuse.
*   **Rule:** **NEVER** change the meaning of a key in a shared domain.
    *   *Bad:* Changing "Submit" to "Send Application" in a global domain. This breaks every other scope using "Submit".
    *   *Correct:* Create a new specific key `submit_application` in the local scope.

---

## 4️⃣ Release Readiness Protocol

**⛔ RELEASE BLOCKER CHECKLIST**

Before any deployment, the **Release Manager** must verify:

1.  [ ] **Coverage Check:** Go to `Sidebar -> Settings -> Translations -> Scopes`. Are all active Scopes at 100% for all active Languages?
2.  [ ] **Unassigned Domain Audit:** Go to `Sidebar -> Settings -> Translations -> Scopes`. Check critical scopes. Are there keys created without assignments? (System prevents this, but check for logical gaps).
3.  [ ] **Fallback Validation:** Go to `Sidebar -> Languages`. Ensure every non-English language has a fallback set (usually English).
4.  [ ] **Active Status:** Ensure the languages intended for release are toggled to "Active".

---

## 5️⃣ Risk & Failure Scenarios

| Scenario | Consequence | Severity | Recovery |
| :--- | :--- | :--- | :--- |
| **Language Deactivated** | Language disappears from user menus. App may default to Fallback (English). | High | Toggle "Active" in Languages list. |
| **Domain Unassigned** | Text belonging to that domain disappears from the screens in that scope. | Critical | Go to Scope Details -> Assign Domain. |
| **Key Renamed** | Code expecting the old key (e.g., `btn_save`) fails to find text. UI shows raw key name. | Critical | Rename back or update code. |
| **Translation Cleared** | UI shows Fallback text (if set) or Key ID (if no fallback). | Medium | Re-enter translation. |
| **Shared Domain Misuse** | Changing a common term breaks context in 50+ other screens. | High | Revert change. Create specific key. |

---

## 6️⃣ Role-Based Operating Boundaries

### 👨‍💻 Developers
*   **MUST:** Create Scopes and Keys.
*   **MUST NOT:** Enter final translations (placeholder text only).
*   **MUST NOT:** Reuse a key just to save time if the *context* is different.

### 👩‍💼 Product Managers
*   **MUST:** Define the *names* of keys to ensure semantic meaning.
*   **MUST:** Audit the "English" (Base) text for tone and voice.
*   **MUST NOT:** Change technical keys (IDs) without developer consultation.

### 🌎 Translators
*   **MUST:** Work strictly within the **Language Translations** view or **Coverage Breakdown** view.
*   **MUST NOT:** touch Scope configurations, Domain assignments, or Key names.

### 🕵️ QA Engineers
*   **MUST:** Validate **Coverage %** is 100% before signing off a release.
*   **MUST:** Spot check "High Risk" shared domains for context errors.

### 🛡️ Administrators
*   **MUST:** Manage Language definitions (Codes, Direction).
*   **MUST:** Manage global Domains.

---

## 7️⃣ Operational Patterns & Best Practices

### The "Translation Sprint" Workflow
*   **Trigger:** 3 days before code freeze.
*   **Action:**
    1.  Project Lead runs **Coverage Audit** (Scope List).
    2.  Exports list of Scopes with < 100% coverage.
    3.  Assigns specific Scopes to specific Translators.
    4.  Translators use **Scope -> Coverage -> View Domains** path to target only missing work.

### The "New Language" Migration
*   **Trigger:** Business decides to support "Spanish".
*   **Action:**
    1.  Create Language "Spanish" (Inactive).
    2.  Set Fallback to "English".
    3.  Coverage is now 0%.
    4.  Translators work through Scopes by priority (e.g., Public Pages first).
    5.  Once Critical Scopes > 90%, toggle Language to "Active" for Beta testing.

---

## 8️⃣ System Behavior Transparency

### How Coverage Recalculates
Coverage is a **Snapshot**. It updates when:
1.  A key is created (Total goes up, % goes down).
2.  A translation is added (Translated goes up, % goes up).
3.  A translation is deleted (Translated goes down, % goes down).

### Why Assignments Matter
The system is **Scope-First**. Even if a "Buttons" domain has 100 keys, a specific Scope (e.g., "Login") can only see and use them if "Buttons" is assigned to "Login". This keeps the payload small and relevant.

### Fallback Logic
If `Language: Spanish` requests key `btn_next` and it is missing:
1.  System checks `Spanish` fallback setting (e.g., `English`).
2.  System serves `English` translation.
3.  User sees English text instead of a blank space or error code.

---

## 9️⃣ Appendices (Operational Tools)

### 🩺 Quick Error Diagnosis

| Symptom | Probable Cause | Fix |
| :--- | :--- | :--- |
| **User sees `btn_save` instead of "Save"** | Missing Translation & Missing Fallback. | Add translation in Language list. |
| **Dropdown for "Domain" is empty in Create Key** | Domain not assigned to Scope. | Go to Scope Details -> Assign Domain. |
| **Translation exists but doesn't show** | Key name mismatch between Code and DB. | Verify Key ID spelling. |
| **Language not visible in App** | Language is "Inactive". | Go to Languages -> Toggle Active. |

### 🚦 Quick Decision Table

| I want to... | Role | Path |
| :--- | :--- | :--- |
| **Add text for a new button** | Dev | Scope -> Keys -> Create Key |
| **Fix a typo in Spanish** | Translator | Languages -> Spanish -> Global Search |
| **Check release status** | QA | Scopes List (Scan Coverage Column) |
| **Add a completely new page** | Lead | Scopes -> Create Scope -> Assign Domains |

---

**End of Enterprise Operations Playbook**
