# Managing Admins

## Overview

Administrators are the authorized users who manage and operate the platform. Because they have access to sensitive information and controls, their accounts must be strictly managed and secured. This section of the control panel allows you to oversee who has access to the platform, what level of access they have, and how their accounts are secured.

### Quick Actions
* **Add a new user:** `Sidebar > Admins > "Create Admin" button`
* **Change an email:** `Sidebar > Admins > Select Admin > "Emails" tab`
* **Force a logout:** `Sidebar > Admins > Select Admin > "Sessions" tab > "Revoke" button`
* **Change access level:** `Sidebar > Admins > Select Admin > "Roles" tab`

## How to View Admins

To see a list of all administrators currently in the system:
1. Look at the left sidebar navigation menu.
2. Click on the **Admins** link.
3. You will be taken to the main Admins page, which displays a table of all existing administrator accounts, showing their names and primary email addresses.
4. Click on any individual admin's name in the table to open their full profile. This profile view is where you can manage their roles, emails, and active sessions.

## Creating a New Admin

When you need to grant a new staff member access to the platform, you must create an account for them.
1. Go to the **Admins** section from the left sidebar.
2. Click the **Create Admin** button located at the top right of the page.
3. A form will appear. Fill in the required details, such as their full name and a primary email address.
4. Click the **Save** or **Create** button at the bottom of the form.
* **Result:** The new account is immediately created. The new administrator will then need to complete the verification and setup process (like setting a password) to access the panel.

**⚠️ Security Warning:** Only create accounts for individuals who absolutely require access. Do not share accounts between multiple people.

## Editing Admin Profile

You can update the basic information of an existing administrator if it changes.
1. From the Admins list, click on the name of the admin you wish to edit.
2. On their profile page, look for the **Edit Profile** button (often near their name or in a top menu bar) and click it.
3. Update their name or notification preferences in the form that appears.
4. Click **Save Changes**.
* **Result:** The profile is updated instantly across the platform.

## Managing Emails

An administrator can have multiple email addresses associated with their account.
1. Open the specific admin's profile and click on the **Emails** tab.
2. **To add a new email:** Enter the new address in the provided field and click **Add**.
   * **Result:** The system will send a verification code to that new email address. The email will be marked as "Unverified" until they confirm they own it.
3. **To replace or fix an email:** Use the action buttons (like **Replace** or **Restart Verification**) next to the specific email address in the list.
* **Result:** The system will guide you through updating the address and will send a new verification email.

**⚠️ Security Warning:** An administrator cannot use a new email address to log in or receive important system alerts until it has been successfully verified.

## Managing Sessions

A "session" represents an active login on a specific device or browser. To maintain security, you can view and manage where an admin is currently logged in.
1. Open the admin's profile and click on the **Sessions** tab.
2. You will see a list of all devices, browsers, and locations currently logged into this account.
3. If you see a device that is unrecognized, old, or no longer in use, click the **Revoke** button next to that session.
* **Result:** That specific device is immediately logged out. If the person is currently using the panel on that device, they will be kicked back to the login screen and asked to re-enter their credentials and 2FA.

**⚠️ Security Warning:** If you suspect an account has been compromised, immediately revoke all of their active sessions.

## Permissions & Roles

To ensure that administrators only have access to what they need, the system uses Roles and Permissions.
1. In the admin's profile, click on the **Roles** tab (or the **Permissions** tab).
2. **To assign a role:** Click the **Assign Role** button, select a pre-defined role (like "Support Staff" or "Manager") from the dropdown, and save.
   * **Result:** The admin instantly gains all the permissions associated with that role. Their menu options and access levels will change immediately.
3. **To remove a role:** Click the **Unassign** button next to an existing role.
   * **Result:** The admin instantly loses access to any features tied exclusively to that role.
4. **To grant specific access:** You can also use the **Permissions** tab to assign **Direct Permissions** for very specific actions if an admin needs a unique level of access that doesn't fit a standard role.

**⚠️ Security Warning:** Always follow the principle of least privilege. Give admins only the exact roles and permissions they need to perform their jobs, and nothing more.

## Important Notes

* **Security First:** Always ensure that new admins are given the minimum level of access necessary to perform their job.
* **Verification Required:** Changing an email address requires verification. An admin cannot use a new email address to log in until it has been successfully verified.
* **Account Tracking:** Every action taken by an administrator is recorded in the system's Activity Logs. This ensures accountability across the platform and provides an audit trail for all changes.
* **Immediate Revocation:** If an admin leaves the company or their account is compromised, you should immediately revoke all of their active sessions and remove their roles to secure the platform.