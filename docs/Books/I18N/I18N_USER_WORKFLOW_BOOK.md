# I18N OPERATIONAL CONTROL MANUAL — ENTERPRISE HARD MODE

**Status:** Official Single Source of Truth
**Target Audience:** Translators, QA, Product Managers, Non-technical Admins
**Objective:** Complete Operational Control of the I18n System.

---

## 1️⃣ COMPLETE REACHABILITY MAP

This map defines the hierarchy of every reachable screen.

*   **Dashboard** (Home)
    *   *Terminal: No*
*   **Languages Screen** (Registry)
    *   *Reached By:* Sidebar -> Languages
    *   *Leads To:* Language Translations
    *   *Terminal: No*
*   **Language Translations** (Editor)
    *   *Reached By:* Languages Screen -> Click ID
    *   *Leads To:* None
    *   *Terminal: Yes*
*   **Scopes Screen** (Registry)
    *   *Reached By:* Sidebar -> Settings -> Translations -> Scopes
    *   *Leads To:* Scope Details
    *   *Terminal: No*
*   **Scope Details** (Hub)
    *   *Reached By:* Scopes Screen -> Click ID
    *   *Leads To:* Scope Keys, Domain Translations, Coverage Breakdown
    *   *Terminal: No*
*   **Scope Keys** (Manager)
    *   *Reached By:* Scope Details -> "Keys" Button
    *   *Leads To:* Create Key Modal
    *   *Terminal: Yes*
*   **Coverage Breakdown** (Audit)
    *   *Reached By:* Scope Details -> "View Domains" Link
    *   *Leads To:* Domain Translations (Filtered)
    *   *Terminal: No*
*   **Domain Translations** (Context Editor)
    *   *Reached By:* Scope Details -> "Translations" Button OR Coverage Breakdown -> "Go" Link
    *   *Leads To:* None
    *   *Terminal: Yes*
*   **Domains Screen** (Catalog)
    *   *Reached By:* Sidebar -> Settings -> Translations -> Domains
    *   *Leads To:* None (Edit Modal only)
    *   *Terminal: Yes*

---

## 2️⃣ FULL ENTRY CONDITIONS MATRIX

This table defines exactly what state is required to enter a screen.

| Screen | Requires Scope? | Requires Domain? | Requires Assignment? | Requires Language? | Failure Behavior |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Languages Screen** | No | No | No | No | N/A |
| **Language Translations** | No | No | No | **Yes** (ID) | 404 Error / Invalid ID |
| **Scopes Screen** | No | No | No | No | N/A |
| **Scope Details** | **Yes** (ID) | No | No | No | 404 Error / Invalid ID |
| **Scope Keys** | **Yes** (ID) | No (View) | **Yes** (Create) | No | "Create" button hidden/disabled if no domains assigned |
| **Domain Translations** | **Yes** (ID) | **Yes** (ID) | **YES** (Strict) | No | Exception / Access Denied Screen |
| **Coverage Breakdown** | **Yes** (ID) | No | No | **Yes** (ID) | 404 Error / Invalid ID |
| **Domains Screen** | No | No | No | No | N/A |

---

## 3️⃣ DEPENDENCY MATRIX (DEEP)

This matrix defines the operational risk and requirements for every action.

| Action | Requires Scope | Requires Domain | Requires Assignment | Requires Language | Changes Coverage | Risk Level |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Create Language** | No | No | No | No | Yes (0% all) | Low |
| **Create Scope** | No | No | No | No | No | Low |
| **Create Domain** | No | No | No | No | No | Low |
| **Assign Domain** | Yes | Yes | N/A | No | No | Medium |
| **Unassign Domain** | Yes | Yes | Yes | No | **YES** (Hide) | **CRITICAL** |
| **Create Key** | Yes | Yes | Yes | No | **YES** (Drop) | Medium |
| **Rename Key** | Yes | Yes | Yes | No | No | **CRITICAL** |
| **Upsert Translation** | No | No | No | Yes | **YES** (Rise) | Low |
| **Clear Translation** | No | No | No | Yes | **YES** (Drop) | Medium |

---

## 4️⃣ FULL OPERATIONAL FLOWS (ALL BRANCHES)

### A. Language-First Workflow
*   **Start:** Languages Screen.
*   **Step 1:** Click Language ID.
*   **Step 2:** Filter by "Empty Values" (if applicable) or Search.
*   **Step 3:** Click Edit on a row.
*   **Step 4:** Enter text -> Save.
*   **Result:** Translation updated globally for that key.

### B. Scope-First Workflow
*   **Start:** Scopes Screen.
*   **Step 1:** Click Scope ID.
*   **Step 2:** Locate "Domain Assignments" table.
*   **Step 3:** Click "Translations" (Eye Icon) on specific Domain.
*   **Step 4:** Translate keys in that specific context.
*   **Result:** Translation updated.

### C. Coverage-First Workflow
*   **Start:** Scope Details Screen.
*   **Step 1:** Check "Language Coverage" table.
*   **Step 2:** Click "View Domains" on incomplete language.
*   **Step 3:** Identify Domain with < 100%.
*   **Step 4:** Click "Go ->".
*   **Step 5:** Translate missing keys.
*   **Result:** Coverage percentage increases immediately.

### D. Domain Creation
*   **Start:** Domains Screen.
*   **Step 1:** Click "Create Domain".
*   **Step 2:** Enter Name and Code.
*   **Result:** Domain exists in catalog but is unused (Orphaned).

### E. Scope Creation & Setup
*   **Start:** Scopes Screen.
*   **Step 1:** Click "Create Scope". Enter Name.
*   **Step 2:** Click new Scope ID.
*   **Step 3:** Scroll to Assignments -> Assign Domain.
*   **Result:** Scope is ready to receive keys.

### F. Key Creation
*   **Start:** Scope Details -> Keys.
*   **Step 1:** Click "Create Key".
*   **Step 2:** Select Domain (Must be assigned).
*   **Step 3:** Enter Key Name -> Save.
*   **Result:** Key exists. Coverage drops for all languages (new untranslated item).

### G. New Language
*   **Start:** Languages Screen.
*   **Step 1:** Create Language -> Set Code -> Set Fallback.
*   **Result:** Language exists (Inactive). Coverage 0%.

### H. Release Audit
*   **Start:** Scopes Screen.
*   **Step 1:** Scan "Coverage" column.
*   **Step 2:** If < 100%, block release.
*   **Step 3:** Go to Languages Screen. Verify target languages are "Active".

### I. Fix Single Typo
*   **Start:** Languages Screen.
*   **Step 1:** Click Language ID.
*   **Step 2:** Global Search for the wrong word.
*   **Step 3:** Edit -> Fix -> Save.

### J. Unassign Domain
*   **Start:** Scope Details.
*   **Step 1:** Assignments Table.
*   **Step 2:** Click "Unassign" (if permitted).
*   **Result:** All keys/translations for that domain **vanish** from the Scope in the app.

### K. Rename Key
*   **Start:** Scope Keys.
*   **Step 1:** Click "Rename".
*   **Result:** Database updates key. Application code **breaks** if not updated simultaneously.

### L. Clear Translation
*   **Start:** Translation Screen.
*   **Step 1:** Click "Clear" (Delete).
*   **Result:** Value removed. App shows Fallback or Key ID. Coverage drops.

---

## 5️⃣ SYSTEM STATE TRANSITIONS

*   **Key Created:** Total Key Count (+1). Coverage % (Drops) for ALL languages.
*   **Translation Added:** Translated Count (+1). Coverage % (Rises) for THAT language.
*   **Translation Cleared:** Translated Count (-1). Coverage % (Drops). Fallback logic activates.
*   **Domain Assigned:** Scope gains access to create keys for that domain. No immediate coverage change (0 keys initially).
*   **Domain Unassigned:** Scope loses access to keys. Keys remain in DB but are operationally invisible to Scope.
*   **Language Deactivated:** Language hidden from system responses. Configuration remains. Coverage calculations persist.
*   **Fallback Changed:** No data change. App runtime behavior changes (different backup text shown).

---

## 6️⃣ FAILURE & RISK SCENARIOS (EXPANDED)

| Action | Immediate Effect | Hidden Effect | User Impact | Recovery Path |
| :--- | :--- | :--- | :--- | :--- |
| **Unassign Active Domain** | Domain disappears from Scope list. | Keys become orphaned from Scope. | Text disappears from App. | Re-assign Domain immediately. |
| **Rename Live Key** | Key ID changes in DB. | App code still looks for old Key ID. | User sees raw key (`btn_x`) or Fallback. | Rename back OR deploy code fix. |
| **Deactivate Language** | Status -> Inactive. | Language removed from language list. | Users cannot select language. | Reactivate Language. |
| **Reuse Shared Domain Incorrectly** | Translation changes. | Affects ALL scopes using that domain. | Context error in unrelated screens. | Revert text. Create specific key. |
| **Clear English (Fallback)** | Translation removed. | No fallback available. | User sees raw key ID (`btn_submit`). | Re-enter English text. |

---

## 7️⃣ GOVERNANCE LAYER

*   **Who translates?** Translators / Content Team.
*   **Who creates structure?** Developers / Architects.
*   **Who assigns domains?** Developers / Product Owners.
*   **Who manages languages?** Admins.
*   **Who audits?** QA / Release Managers.

**Red-Line Rules:**
1.  **NEVER** unassign a domain without verifying it is empty or unused.
2.  **NEVER** rename a key in the panel without a matching code deployment.
3.  **NEVER** create a key in a "Shared" domain for a specific/unique context.
4.  **BLOCK** release if Critical Scope Coverage < 100%.

---

## 8️⃣ REVERSE NAVIGATION AWARENESS

*   **At Domain Translations Screen:**
    *   *From:* Scope Details (Specific intent).
    *   *Intent:* Fix specific feature text.
    *   *Next:* Return to Scope Details.
*   **At Scope Details Screen:**
    *   *From:* Scopes List (General management).
    *   *Intent:* Audit feature or configure structure.
    *   *Next:* Keys or Coverage.
*   **At Language Translations Screen:**
    *   *From:* Languages List (Bulk work).
    *   *Intent:* Mass translation or global fix.
    *   *Next:* Search/Filter.

---

## 9️⃣ STRUCTURAL SAFETY RULES

*   **Break the App:** Unassigning Domains, Renaming Keys. (Code/Data mismatch).
*   **Affects Visibility:** Deactivating Languages. (Data exists, access denied).
*   **Affects Content:** Editing Translations. (Immediate text change).
*   **Affects Metrics:** Creating Keys (lowers coverage), Clearing Translations (lowers coverage).

---

## 🔟 MENTAL MODEL (PROFESSIONAL)

**System Entity Relationship Model**

1.  **Scope** (Container)
    *   *Contains:* Assignments
2.  **Domain** (Category)
    *   * Assigned To:* Scope
3.  **Key** (Identifier)
    *   *Belongs To:* Scope + Domain (Intersection)
4.  **Language** (Locale)
    *   *Applies To:* Translation
5.  **Translation** (Value)
    *   *Belongs To:* Key + Language
6.  **Coverage** (Metric)
    *   *Calculated From:* (Translations / Keys) per Language per Scope

---

**End of Enterprise Operational Control Manual**
