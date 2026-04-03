# Managing Application Settings

## 1. Overview

Application Settings are configurations that control the behavior of the platform. This section allows administrators to toggle features, adjust limits, and configure integration logic directly from the control panel.

## 2. Admin Interaction Flow

Administrators manage these configurations through the App Settings list interface.

### Search & Filters
At the top of the page, you can use filters to quickly find specific settings:
*   **ID:** Search by the exact setting ID.
*   **Group:** Filter by category (e.g., `system`, `security`).
*   **Key:** Search by the specific setting name.
*   **Status:** Filter by Active or Inactive settings.
*   **Search Button:** Applies the entered filters.
*   **Reset Button:** Clears all column filters and reloads the default list.

### Global Search
Below the filters is a quick search bar:
*   **Quick Search:** Type to search across group, key, or value simultaneously.
*   **Clear Button:** Removes the global search text.

### Settings List (Table)
The table displays all available settings with the following columns:
*   **ID:** The unique identifier for the setting.
*   **Group:** The category the setting belongs to.
*   **Key:** The name of the setting.
*   **Value:** The current configured value.
*   **Type:** Indicates the format of the value.
*   **Status:** Indicates if the setting is currently Active or Inactive.
*   **Actions:** Contains buttons to interact with the setting.

### Visual Indicators
*   **Protected Settings (🔒 Lock Icon):** Some settings are critical to the system and cannot be edited. These appear with a lock icon next to their key.
*   **Orphaned Settings (⚠️ Warning Icon):** Settings that are no longer recognized by the system appear with a warning icon. These can only be deactivated.

## 3. How to Edit a Setting

To modify an existing setting:
1.  Locate the setting in the list using the search or filters.
2.  Click the **Edit** button in the Actions column for that row.
3.  An **Edit App Setting** modal will open on your screen.
4.  Modify the **Value** in the provided input field.
5.  If necessary, change the **Type** from the dropdown menu.
6.  Click the **Save Changes** button inside the modal.
7.  A success message will appear, the modal will close, and the table will automatically refresh to show the updated value.

## 4. How to Create a Setting

If your account has the necessary permissions, you can create new settings:
1.  Click the **Create Setting** button located near the search filters.
2.  A creation modal will open on your screen.
3.  Fill in the required input fields (Group, Key, Value, and Type).
4.  Click the **Save** button inside the modal.
5.  A success message will appear, the modal will close, and the table will automatically refresh to show the newly created setting.

## 5. Usage Guidance

*   **Active vs. Inactive:** You can change a setting's status by clicking the toggle button in the Actions column for that row. A success message is shown and the table refreshes to display the new status.
*   **Locked Settings:** You cannot modify settings marked with the 🔒 icon. The interface restricts editing for these configurations.
*   **Immediate Updates:** Any changes made and saved in this interface will appear immediately in the table.
