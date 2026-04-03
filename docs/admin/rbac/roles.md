# Managing Roles

## Overview

Roles control access across the platform. A Role groups specific permissions together so you can grant a standard set of access rights to an administrator with a single assignment, rather than individually managing hundreds of specific permissions.

## How to Access Roles

To manage roles:
1. Look at the left sidebar navigation menu.
2. Open the **RBAC** section.
3. Click on **Roles**. This opens the main Roles List page.

## Roles List

When you open the Roles section, you will see a table of all existing roles.
* **Table Columns:** The table displays the name of each Role and its current status.
* **Actions:** You can click the **View** or **Edit** buttons next to an existing role, or click the **Create Role** button to add a new one.
* **Filters and Search:** Above the table, there is a global search bar to instantly find roles by name or group. You can also click the quick-filter badges to view "All", "Active", or "Inactive" roles. Additionally, a dedicated filter form allows you to search specifically by Role ID, Name, or Group, complete with a Reset button to clear active filters.

## Creating a Role (FULL FLOW)

When you need to define a new job function with specific access rights, follow these steps:

1. Click the **Create Role** button.
2. Enter a clear, descriptive **Role Name** (e.g., "Support Agent" or "Translator").
3. Click the **Create Role** button to finalize the creation.
* **Result:** The creation step only creates the role's basic information (its name). It does not fully manage permissions from this step. After creation, the new role will immediately appear in the Roles List.

## Viewing and Managing a Role

The primary way to configure a role's access rights and assignments is through its management screen.

1. From the Roles List, click the **View** button next to a role.
2. This opens the role details page.
3. This page contains two main sections:
   * **Permissions tab:** Where you control what the role allows.
   * **Admins tab:** Where you control who holds the role.

### Permissions Management

Permissions are strictly managed from the **Permissions tab**.
1. Open the **Permissions tab** on the role details page.
2. A list of permissions is shown.
3. Each permission has a toggle to enable or disable it.
4. Clicking a toggle immediately enables or disables that permission for the role.
* **Result:** Changing a toggle instantly updates the role's access rights across the entire platform.

### Admin Assignment from Role Page

You manage who holds this specific role from the **Admins tab**.
1. Open the **Admins tab** on the role details page.
2. The tab shows a list of administrators.
3. Each administrator has a toggle next to their name.
4. Clicking an administrator's toggle instantly assigns or unassigns this role for them.

## Editing a Role

If you only need to change the basic details of a role:

1. From the Roles List, click the **Edit** button next to the role.
2. You can modify its basic information, such as its name.
3. Click the **Save** button to apply the changes.

## What Happens When a Role Changes

Because roles are tied directly to an administrator's session, any modifications take effect immediately.
* **Sidebar updates:** If you add or remove permissions that control entire sections of the platform (like the "Settings" menu), that link will instantly appear or disappear from the left sidebar of any administrator holding that role.
* **Buttons appear/disappear:** If you add or remove specific action permissions (like "Create Admin"), that specific button will instantly become visible or be hidden on the relevant pages.
* If an administrator is actively viewing a page that a role change just revoked access to, the system will block them the next time they click a button or refresh the page.

## Important Notes

* Always ensure roles match actual job responsibilities.
* Avoid overcomplicating roles; keep them broad enough to be useful for multiple staff members with similar duties.
* Prefer assigning Roles over granting Direct Permissions whenever possible, as Roles are much easier to track, audit, and manage at scale.
