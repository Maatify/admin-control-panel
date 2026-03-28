# Access Control (Roles & Permissions)

## Why Access Control Exists

Not all administrators require the ability to modify every part of the platform. For example, a support agent needs to view user accounts but should not be allowed to change global application settings or delete system translation languages. Access control restricts visibility and actions. It ensures that staff members can only see the specific sections of the left sidebar navigation menu—and click the specific buttons—that they actually need to perform their jobs.

## Roles

A Role functions as a pre-packaged set of access rights designed for a specific job title, such as a "Translator" or "Content Manager". Instead of manually clicking and selecting dozens of individual access boxes every time a new staff member joins, you create a Role, check the necessary access boxes once, and then simply assign that Role to the administrator's profile.

## Permissions

A Permission is the smallest, most specific unit of access. It dictates exactly which buttons appear and which pages load for an administrator. For example, one permission determines if the "View" button appears next to a translation, while a completely separate permission determines if the "Edit" or "Delete" buttons appear on that same page.

## How Roles and Permissions Work Together

Roles are the primary way to manage access in the system. When you assign a Role to an administrator, they instantly receive all the underlying Permissions attached to that Role, immediately updating their navigation sidebar.

You can also assign Permissions directly to an administrator's profile. Direct permissions are used only for exceptional cases. For example, if a staff member needs a single, unique capability (like clicking "Publish" on a specific legal document) but does not need a completely new Role, you can grant them that specific right directly.

## What Happens When Access Changes

When an administrator's assigned Roles or Permissions are modified, the effects happen instantly across their active sessions.
* Entire menu items (like "Admins", "Sessions", or "Settings") in the left sidebar navigation will instantly appear or disappear based on what they are newly allowed to view.
* Specific action buttons (such as "Create Admin," "Edit Profile," or "Revoke Session") within a page will instantly become visible or be hidden.
* If an administrator attempts to refresh or navigate to a page they no longer have permission to view, the system will instantly block them and display an error.

## Real Usage Scenario

If you create a "Translation Specialist" role and check only the permissions to view and edit Translations:
* When an administrator with this role logs in, they will only see the "Languages" and "Translations" links in their left sidebar.
* They will be able to click on "Scopes" and "Domains" and use the "Edit" buttons to update localized text.
* The "Admins," "Sessions," and "Settings" links will be completely missing from their sidebar. Even if they try to guess the web address to reach those pages, the system will completely block them from accessing or modifying system documents or other administrators' profiles.

## Important Notes

* Always limit access to the absolute minimum required for an administrator to complete their tasks.
* Avoid granting broad or full access unless absolutely necessary.
* Changes to Roles and Permissions take effect immediately across the platform. If you remove a role while an administrator is actively clicking through the panel, they will instantly lose access to those features and buttons.