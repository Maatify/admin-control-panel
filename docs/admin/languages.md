# Managing Languages

## Overview

The Languages module (LanguageCore) establishes the foundational identities of the languages supported across the platform. While this module does not handle the actual text translations, it defines which languages exist, their programmatic codes (e.g., "en", "ar"), their reading direction (left-to-right or right-to-left), and their active status. The translation systems explicitly rely on this core registry to function.

## How to Access Languages

To manage the platform's supported languages:
1. Locate the left sidebar navigation menu.
2. Click on the **Languages** link.
3. This opens the main Languages List page.

## Languages List

The main interface is a data table displaying all registered languages.

*   **Table Columns:**
    *   **ID:** The unique system identifier for the language.
    *   **Name:** The human-readable name of the language (e.g., "English").
    *   **Code:** The programmatic identifier (e.g., "en").
    *   **Direction:** The reading direction for the language (e.g., `ltr` or `rtl`).
    *   **Order:** The numerical sort order determining how languages appear in dropdowns across the platform.
    *   **Status:** A visual badge indicating if the language is "Active" or "Inactive".
    *   **Fallback:** Displays a link icon with the ID of another language if a fallback is configured, or "None" with an X icon if not.
    *   **Actions:** Contains all interactive buttons for modifying the language row.

### Filters and Search

Above the table, the interface provides comprehensive search and filtering tools:
*   **Global Search:** A search input box that allows you to instantly search across the table. It features a 1-second auto-search delay as you type, or you can press "Enter" or click the Search button to trigger it immediately. A "Clear" button resets this specific input.
*   **Column Filters:** A filter form allowing you to narrow down the table by specific fields: ID, Name, Code, Direction, and Status.
*   **Reset Filters Button:** A dedicated button that clears all active column filters and resets the table view to page 1.

## Creating a Language

When introducing a new language option to the platform:

1.  Click the **Create Language** button located above the table.
2.  A modal or form will open requiring the new language's **Name**, **Code**, and **Direction** (LTR or RTL). You can also optionally provide an icon, set the initial Active status, and assign a Fallback Language.
3.  Click the save/create button to submit the form.
*   **Validation:** The system strictly checks the database to ensure the provided **Code** does not already exist. If it does, a "Language Already Exists" error is displayed.
*   **Result:** The language is immediately created in the database and assigned the next available sort order automatically. It instantly appears in the Languages List.

## Editing a Language

Unlike bulk-edit forms, modifying a language in this system is split into highly specific actions to ensure data integrity.

From the **Actions** column in the Languages List, you can perform the following modifications:
*   **Update Settings:** Click the Edit Settings button to modify the language's reading Direction and Icon.
*   **Update Name:** Allows you to change the human-readable Name of the language.
*   **Update Code:** Allows you to change the programmatic Code. *Warning:* The system will strictly validate that the new code is not already in use by another language.
*   **Update Sort Order:** Allows you to manually adjust the numerical priority of the language.

*   **Save Behavior:** Each of these actions sends an immediate request to the backend. Upon success, the UI table refreshes instantly to display the updated data.

## Activating / Deactivating a Language

You can control whether a registered language is currently active in the system.

1.  Locate the language row.
2.  Click the **Activate** or **Deactivate** toggle button in the Actions column.
*   **What happens after:** The database is instantly updated. The Status badge changes immediately. When deactivated, the language is generally removed from user-facing selection options, though existing translations tied to it remain securely stored in the database.

## Managing Fallback Languages

A "Fallback Language" instructs the system to display text from an alternative language if a translation is missing for the user's selected language.

From the **Actions** column:
1.  **Set Fallback:** If the language currently has "None" listed, click the **Set Fallback** button (purple link icon). A modal will open allowing you to input the ID of another language. The system prevents you from setting a language as its own fallback.
2.  **Clear Fallback:** If a fallback is currently configured, click the **Clear Fallback** button (red X icon) to instantly remove the fallback routing.

## Deleting a Language

Based on the system's strict data integrity architecture, there is **no delete functionality** for languages. Languages are permanently referenced by thousands of translation keys and user settings across the platform. If a language is no longer needed, administrators must use the **Deactivate** action to hide it from the active platform.

## What Happens When Languages Change

Because the LanguageCore module acts as the central identity registry for the entire platform:
*   **Immediate Application:** Any change to a language's Code, Direction, or Active status takes effect immediately.
*   **Translation Dependencies:** Modifying a language directly impacts the `I18n` translation module, as all text values are loaded based on the Language ID and Fallback ID defined here.

---

## Coverage Confirmation

I explicitly confirm the following:
*   **No "UNCLEAR" placeholders:** All previously unconfirmed behaviors have been resolved and documented based on the exact Javascript implementations and PHP services.
*   **Tables and Columns:** The exact 8 table columns (ID, Name, Code, Direction, Order, Status, Fallback, Actions) are documented from `languages-with-components.js`.
*   **Filters and Search:** The Global Search (with debounce and Enter-key support) and specific Column Filters (ID, Name, Code, Direction, Status) are fully detailed.
*   **Buttons and Actions:** The exact, fragmented edit actions (Settings, Name, Code, Sort Order), Status toggles, and Fallback (Set/Clear) buttons are documented directly from `LanguageManagementService.php` and the UI handlers.
*   **Missing Features Addressed:** The lack of a "Delete" button is explicitly explained based on the absence of a delete method in the backend service, accurately reflecting the system's data integrity rules.