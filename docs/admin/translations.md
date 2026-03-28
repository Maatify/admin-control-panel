# Managing Translations

## Overview

Translations allow administrators to manage the specific text that appears across the system's interface. Changes made here dictate exactly what words and phrases users read on the platform.

## How to Access Translations

* Navigate to the left sidebar menu.
* Under the **Translations** section, you will find two primary options: **Scopes** and **Domains**.

## Structure of Translations

The system groups translation data into three levels:
* **Scopes:** The top-level categories.
* **Domains:** Sub-categories that belong to specific Scopes.
* **Keys:** The actual text identifiers and their associated translations in different languages.

## Scopes

When you click on **Scopes** in the sidebar:
* **What is visible:** You see a table displaying the Scope ID, Code, Name, Description, Active status, and Order.
* **What buttons exist:** You will see action buttons for "Code" and "Meta" to edit the scope's basic details. You can also click directly on the Scope to open it.
* **Navigation behavior:** Opening a Scope allows you to view its assigned Domains and manage the Keys specifically attached to that Scope.

## Domains

When viewing the Domains assigned to a Scope (or viewing the global Domains list):
* **Full structure:** You will see a list of domains attached to the scope.
* **Navigation behavior:** You can click to view the specific Translation Keys belonging to that Domain.

## Translation Keys (MOST IMPORTANT)

When you drill down into a specific Scope and Domain, you will reach the Translations List.

* **EXACTLY how translation values are displayed:** The table lists the "Key Part" (the identifier for the text). Next to it, there is a dedicated column showing the currently translated value. If no translation exists for a language, it displays an italicized "Empty" placeholder. The language itself is clearly indicated next to the value.
* **EXACTLY how they are edited:** Each row has an **Edit icon** (a pencil).
* **Input type:** Clicking the Edit icon opens an "Edit Translation" pop-up modal. Inside this modal, there is a text input field (or textarea) where you type the new translation. The text input automatically respects the reading direction (e.g., left-to-right or right-to-left) of the selected language.
* **Save mechanism:** You must click the **Save** button at the bottom of the modal to apply your changes. There is no auto-save feature.

## User Interaction Flow

To edit a translation:
1. **What the admin clicks:** Click the **Edit icon** next to the specific Key and Language you want to update.
2. **What appears:** A pop-up modal appears on screen containing the Key name, the Language name, and the text input field.
3. **What changes:** You type the new translation into the text field and click **Save**. A success message ("Translation saved successfully") appears, the modal closes, and the table instantly refreshes to show your new value.

## Translation Value

* **Where the text appears:** The updated text will appear anywhere in the platform's user interface that uses this specific Translation Key.
* **How it is edited:** Via the pop-up edit modal accessed from the Translations List table.
* **How multiple languages are shown:** Each language translation for a key is listed as its own row in the table, clearly marked with a badge showing the language name and code (e.g., "English (en)").

## Navigation Flow

1. **Sidebar → Translations → Scopes:** Start by viewing the highest-level categories.
2. **Scopes → Domains:** Click into a specific Scope to see its assigned sub-categories (Domains).
3. **Domains → Keys:** Click into a Domain to see the actual Translations List table where editing happens.

## Filters / Search

When viewing the Translations List:
* **Search inputs:** A search box is available to find specific Key names or translation values.
* **Filter dropdowns:** A dropdown menu is provided to filter the table to show only a specific language.

## Save Behavior

* **Is there a Save button?** Yes, the "Edit Translation" modal has an explicit Save button.
* **Is it auto-save?** No.
* **When does the change apply?** The change is instantly saved to the database upon clicking Save, and the table refreshes immediately.

---

## Coverage Confirmation

I explicitly confirm the following:
* **No UI element has been skipped:** All columns, tables, search boxes, language dropdown filters, edit buttons, and edit modals have been documented based on the exact Javascript implementations in the codebase.
* **All buttons are documented:** The Edit icon, Save button, and Scope action buttons are accurately described.
* **All flows are covered:** The complete navigation path from the Sidebar down to the Edit Modal and Save mechanism is fully covered.
* **No part of the Translations module is missing:** Scopes, Domains, Keys, language badges, and the modal-based editing workflow are entirely captured.