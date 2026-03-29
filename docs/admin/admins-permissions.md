# Managing Permissions

## 1. Overview

This page displays ALL permissions currently affecting a specific administrator. It acts as the final source of truth for access control, combining the permissions granted indirectly through Roles with the permissions granted via Direct Permissions. It reflects the real access behavior and exact security boundaries currently applied to the administrator in the system.

## 2. How to Access

To manage or view an administrator's permissions:
1. Navigate to **Admins** in the left sidebar.
2. Click **View** next to the specific administrator.
3. Click the **Permissions** link/section.

*   You can use the breadcrumb navigation at the top of the page to easily return to the main Admins list.
*   You can click the **Back to Profile** button to return to the administrator's main profile view.

## 3. Tabs Explanation

The Permissions page is divided into three critical tabs to help you understand and manage exactly how access is being granted.

### Effective Tab
*   **What it does:** Shows the final, calculated permissions applied to the admin.
*   **What it includes:** This list combines both Role-based permissions and Direct permissions into one comprehensive view.
*   **Source Column:** Includes a specific "Source" column that tells you exactly where the permission comes from (e.g., whether it was granted by a specific Role or via a Direct Allow override).
*   **Read-only:** This tab is purely for auditing. There is no editing or assigning done from this tab.

### Direct Tab
*   **What it does:** Shows ONLY the permissions that have been assigned directly to this individual administrator, completely ignoring any Roles they hold.
*   **What it includes:** It details the Type (whether the direct permission is an "Allowed" override or a "Denied" override) and the "Granted At" timestamp.
*   **Actions:** From here, you can click the **Assign Permission** button to add a new direct override, or use the **Edit** and **Revoke** action buttons on existing rows. Revoking simply removes the direct assignment; it does not block the permission if the user still holds it via a Role.

### Roles Tab
*   **What it does:** Shows the specific security roles currently assigned to the administrator.
*   **What it includes:** It lists the Role Name and its current Status (Active or Inactive).
*   **Clarification:** Roles indirectly grant permissions. Assigning a role to an administrator grants them all the permissions bundled inside that role, which will then populate in the Effective tab.

## 4. Filters & Search

To help you find specific access rights, the following tools are available across the tabs:

*   **Search inputs:** You can filter the tables by typing into the specific input boxes provided for **Permission ID**, **Permission Name**, and **Group**.
*   **Reset button:** A button is provided to instantly clear all active filters and return the table to its default view.
*   **Table search bar:** A global search bar exists to quickly search across the entire current table.
*   **Export buttons:** You can export the current view of the permissions table using the **CSV**, **Excel**, and **PDF** export buttons.

## 5. Assign Permission Flow

To grant or block a specific permission directly for this administrator, bypassing their roles:

1.  Click the **Assign Permission** button located on the Direct tab.
2.  A large modal opens titled **Assignable Permissions**.

Inside the modal:
*   You will see a paginated, searchable table of every permission available in the system.
*   The columns displayed in this modal are: **ID**, **Name**, **Group**, **Display Name**, **Assigned**, **Type**, and **Expires At**.
*   **Action:** Each row has a toggle or button to assign the permission.
*   **Configuration:** When assigning, you can set the Type to **Allowed** (to grant access) or **Denied** (to explicitly block access, even if a role grants it). You can optionally set an expiration date.
*   **Result:** Once assigned, the permission reflects instantly in the Direct tab and recalculates the administrator's final access in the Effective tab.

## 6. Editing / Revoking

From the **Direct** tab, you can modify existing direct assignments using the buttons in the Actions column:

*   **Edit button:** Allows you to modify the current assignment. You can change its Type (flipping an "Allow" to a "Deny") and update or remove its expiration date.
*   **Revoke button:** Clicking this completely deletes the direct permission assignment.

*Effect:* The changes take effect immediately. If you revoke a "Deny" override, and the admin has a Role that allows that permission, they will instantly regain access.

## 7. Table Columns

The columns displayed in the UI exactly match these structures:

### Effective
*   ID
*   Name
*   Group
*   Display Name
*   Description
*   Source
*   Expires At

### Direct
*   ID
*   Name
*   Group
*   Display Name
*   Type
*   Expires At
*   Granted At
*   Actions

### Roles
*   ID
*   Name
*   Group
*   Display Name
*   Description
*   Status

## 8. Behavior Rules

*   **Effective = final result:** The Effective tab is the absolute truth of what the administrator can and cannot do right now.
*   **Direct overrides role behavior:** If a Role grants a permission, but a Direct Permission denies it, the Direct permission wins.
*   **Deny overrides allow:** An explicit "Deny" will always override an "Allow", regardless of how many roles grant the "Allow".
*   **UI updates immediately:** Toggling, assigning, or revoking permissions refreshes the tables and access rights instantly.
*   **Changes affect admin instantly:** If the administrator is currently logged in, modifying their permissions will instantly show or hide buttons and sidebar links on their screen.

## 9. Real Usage Examples

*   **Adding a direct permission to override a role:** An administrator has the "Support" role which does not allow deleting users. You need them to delete a specific spam account today. You click "Assign Permission", find `users.delete`, and assign it as "Allowed" with an expiration set for tomorrow.
*   **Revoking a permission granted via direct assignment:** The administrator finishes the task early. You go to their Direct tab, find the `users.delete` override, and click "Revoke". They instantly lose the ability to delete users.
*   **Seeing permission source in Effective tab:** You want to know *why* an admin can access the Billing page. You check their Effective tab, search for `billing.view`, and look at the "Source" column to see which specific Role is granting them that access.

## 10. Important Notes

*   **Changes are instant:** Be absolutely certain before clicking Assign or Revoke.
*   **Permissions impact visibility:** Granting or revoking permissions will instantly add or remove sidebar links and action buttons from the administrator's interface.
*   **Direct permissions should be used sparingly:** Rely on Roles for 99% of your access management. Direct permissions should be reserved strictly for temporary exceptions or highly unique administrative requirements.

---