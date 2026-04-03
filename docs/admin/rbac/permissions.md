# Managing Permissions

## Overview

Permissions are the specific access rights that control exactly what an administrator can see and do on the platform. Rather than dealing with broad concepts, permissions determine the practical reality of the system: they dictate exactly which buttons appear on a page, which links show up in the navigation menu, and which pages successfully load.

## Where Permissions Are Managed

Permissions are not created or managed from a separate, standalone page. Instead, they are managed directly from inside an existing Role.

To manage permissions, follow this exact flow:
1. In the left sidebar, go to **RBAC** → **Roles**.
2. Click the **View** button next to the role you want to manage.
3. On the role details page, click the **Permissions tab**.

## Permissions List

When you open the Permissions tab, you will see a list of access rights available in the system.

* **Table Columns:**
  * **Permission name:** The specific title of the access right.
  * **Group:** The category the permission belongs to.
  * **Description:** An explanation of what the permission allows the user to do.
  * **Assigned toggle:** A switch used to enable or disable the permission.
* **Filters and Search:** Above the table, you will find a global search bar to quickly find permissions by typing any keyword (it automatically searches after a brief delay). Additionally, a filter form allows you to search specifically by Permission ID, Name, or Group. A Reset button clears all active filters.

## Enabling or Disabling a Permission

To grant or remove a specific access right for a role, follow these steps:

1. Open a role using the **View** button from the Roles list.
2. Go to the **Permissions tab**.
3. Locate the specific permission you want to modify.
4. Click the **Assigned toggle** next to the permission to turn it ON (enabled) or OFF (disabled).

**What happens immediately after toggle:**
The change is applied to the system the exact moment you click the toggle. There is no "Save" button required on this tab. Any administrator currently holding this role will instantly have their access updated across the platform.

## How Permissions Affect the System

When a permission is toggled on or off, it directly changes the system's interface and security boundaries:
* **Sidebar sections appear/disappear:** If a permission controls access to an entire section, the corresponding link in the left sidebar will instantly appear or vanish.
* **Buttons appear/disappear:** If a permission controls a specific action (like deleting a record), the "Delete" button will instantly become visible or hide itself on the relevant page.
* **Access to pages is blocked:** If an administrator attempts to visit a page for which they just had the permission disabled, the system will instantly block them and display an error.

## Permission Groups

Permissions in the list are organized logically by a "Group" classification (e.g., separating user management permissions from system settings permissions). This is reflected in the "Group" column of the data table, allowing you to easily sort, filter, and identify related access rights visually.

## Real Usage Examples

* **Enabling "Create Admin":** If you toggle this permission ON for a role, administrators with that role will instantly see the "Add Admin" button appear on the Admins list page.
* **Enabling "View Activity Logs":** If you toggle this permission ON, the "Activity Logs" link will instantly appear in the administrator's left sidebar, allowing them to click it and view the page.
* **Disabling access to "Settings":** If you toggle the Settings permissions OFF, the "Settings" link will instantly disappear from the sidebar. If the administrator tries to manually type the web address to reach the Settings page, the system will actively block their access.

## Important Notes

* **Changes apply immediately:** The moment you click a toggle, the access rights are updated. There is no delay.
* **Permissions should be carefully managed:** Only enable toggles for actions that the specific role absolutely requires to perform their job.
* **Removing a permission can instantly block access:** If an administrator is currently working on a page and you toggle their permission OFF, they will be blocked the next time they click a button or refresh their screen.
