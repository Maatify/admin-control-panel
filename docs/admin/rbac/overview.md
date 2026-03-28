# Access Control (Roles & Permissions)

## Why Access Control Exists

Not all administrators require the ability to modify every part of the platform. For example, a support agent needs to view user accounts but should not be allowed to change global application settings or delete system translation languages. Access control exists to ensure that staff members can only see and interact with the specific sections of the admin panel they need to perform their jobs, preventing accidental or unauthorized changes to critical system configurations.

## Roles

A Role represents a collection of access rights designed for a specific job function, such as a "Translator" or "Content Manager". Instead of manually selecting dozens of individual access rights every time a new staff member joins, you create a Role, attach the necessary rights to it once, and then simply assign that Role to the administrator's profile.

## Permissions

A Permission is the smallest, most specific unit of access. It dictates exactly what action an administrator is allowed to take on a specific feature. For example, one permission might allow an administrator to view the list of translations, while a completely separate permission is required to actually edit or delete a translation.

## How Roles and Permissions Work Together

When you assign a Role to an administrator, they instantly receive all the underlying Permissions attached to that Role.

You can also assign Permissions directly to an administrator's profile. This is useful if a staff member needs a unique capability (like publishing a specific legal document) that does not belong in their standard, assigned Role.

## What Happens When Access Changes

When an administrator's assigned Roles or Permissions are modified, the effects are immediate.
* Entire sections of the left sidebar navigation menu will appear or disappear based on what they are allowed to view.
* Specific buttons (such as "Create," "Edit," or "Delete") within a page will become visible or hidden.
* If an administrator attempts to access a page they no longer have permission to view, the system will block them instantly.

## Real Usage Scenario

If you create a "Translation Specialist" role and assign it only the permissions to view and edit Translations:
* When an administrator with this role logs in, they will see the "Translations" section in their sidebar.
* They will be able to click on "Scopes" and "Domains" to update localized text.
* They will **not** see the "Admins," "Sessions," or "Settings" sections in their sidebar, and they will be completely blocked from accessing or modifying system documents or other administrators' profiles.

## Important Notes

* Always limit access to the absolute minimum required for an administrator to complete their tasks.
* Avoid granting broad or full access unless absolutely necessary.
* Changes to Roles and Permissions take effect immediately across the platform. If you remove a role while an administrator is actively working, they will instantly lose access to those features.