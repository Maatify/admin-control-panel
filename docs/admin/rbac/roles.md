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
* **What is visible:** The table displays the name of each Role and its current status.
* **Actions available:** You can click the **View** or **Edit** buttons next to an existing role, or click the **Create Role** button to add a new one.
* **Filters:** UNCLEAR.

## Creating a Role (FULL FLOW)

When you need to define a new job function with specific access rights, follow these steps:

1. Click the **Create Role** button.
2. Enter a clear, descriptive **Role Name** (e.g., "Support Agent" or "Translator").
3. Select the permissions you want this role to have. You will be presented with a list of system permissions to choose from.
   * *How permissions appear in the UI (e.g., grouped or checkboxes):* UNCLEAR.
4. Click the **Create Role** button to finalize the creation. The new role will immediately appear in the Roles List and become available to assign to administrators.

## Editing a Role

If a job function changes and requires more or less access, you must update its role:

1. From the Roles List, click the **Edit** button next to the role you want to change.
2. You can rename the role or modify its assigned permissions.
3. Click the **Save** button.
* **Result:** The changes are saved immediately. Any administrator currently assigned this role will instantly have their access updated across the platform.

## Assigning Roles to Admins

Roles are assigned directly from an administrator's profile, not from the Roles list.
1. Navigate to the **Admins** section in the left sidebar.
2. Click **View** next to the specific administrator.
3. Open the **Roles** tab on their profile.
4. Click **Assign Role**, select the role from the dropdown menu, and click **Save**.

## What Happens When a Role Changes

Because roles are tied directly to an administrator's session, any modifications take effect immediately.
* **Sidebar updates:** If you add or remove permissions that control entire sections of the platform (like the "Settings" menu), that link will instantly appear or disappear from the left sidebar of any administrator holding that role.
* **Buttons appear/disappear:** If you add or remove specific action permissions (like "Create Admin"), that specific button will instantly become visible or be hidden on the relevant pages.
* If an administrator is actively viewing a page that a role change just revoked access to, the system will block them the next time they click a button or refresh the page.

## Important Notes

* Always ensure roles match actual job responsibilities.
* Avoid overcomplicating roles; keep them broad enough to be useful for multiple staff members with similar duties.
* Prefer assigning Roles over granting Direct Permissions whenever possible, as Roles are much easier to track, audit, and manage at scale.