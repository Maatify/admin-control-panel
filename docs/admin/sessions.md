# Managing Sessions

## Overview

A "session" represents a specific instance of an administrator being logged into the platform on a particular computer, phone, or web browser. Because administrators have access to sensitive controls, tracking these sessions helps you see exactly where and how accounts are being used. If a device is lost or an account is compromised, you can use the Sessions management tools to instantly cut off access.

## How to Access Sessions

There are two primary ways to view and manage active login sessions across the platform.

### Option 1: Global Sessions Page
To view a list of all active sessions across all administrators:
1. Look at the left sidebar navigation menu.
2. Click on **Sessions**. This opens the global Sessions list.

### Option 2: Per Admin Sessions
To view only the sessions belonging to a specific administrator:
1. In the left sidebar, click on **Admins**.
2. Click the **View** button next to the specific administrator you want to inspect.
3. On their profile page, click the **Sessions tab**.

## Sessions List

Whether you are looking at the global list or a specific admin's profile, the Sessions table provides details about each active login.

* **What is visible in the table:**
  * **Status:** Whether the session is currently Active, Expired, or Revoked.
  * **Current session indication:** The system clearly highlights your *Current session* so you do not accidentally log yourself out.
  * **Device, browser, IP, or time:** UNCLEAR.
* **Filters/search:** You can sort the sessions using filters such as **All**, **Active**, **Expired**, and **Revoked**. Any additional search functionality is UNCLEAR.

## Revoking a Session

If you need to force an administrator to log out of a specific device, you must revoke their session.

1. Locate the specific session in the list (either on the global Sessions page or the admin's profile).
2. Click the **Revoke** button next to that session.
* *Note on confirmation behavior:* UNCLEAR (It is unclear if the system asks "Are you sure?" before proceeding).
* *Note on partial revocation:* UNCLEAR (It is unclear if you can revoke specific permissions within a session; assume revocation applies to the entire session).

If you need to log an administrator out of *every* device at once, you can use the **bulk revoke** action available on the admin's profile.

## What Happens After Revocation

* **The session is immediately terminated:** The moment you click Revoke, the system invalidates that specific login.
* **The user is forced out:** If the administrator is currently using the panel on that revoked device, they will be instantly kicked back to the login screen the next time they click a link or refresh the page.
* **Access is blocked until re-login:** The device cannot access the admin panel again until the administrator re-enters their email, password, and completes their Two-Factor Authentication (2FA) verification.

## Security Use Cases

* **Unknown device detected:** If you or another administrator notice an active session from an unfamiliar location or browser, you can instantly revoke it to secure the account.
* **Admin left the company:** When an administrator resigns or is terminated, you should immediately bulk revoke all of their active sessions and disable their account to ensure they cannot access the platform from a personal device.
* **Shared device cleanup:** If an administrator accidentally leaves themselves logged in on a public or shared computer, they (or you) can remotely revoke that specific session without affecting their access on their primary work computer.

## Important Notes

* **Revoking is immediate:** There is no delay. The device loses access the exact second the Revoke button is pressed.
* **Current session safety:** Always pay attention to the *Current session* indicator to ensure you do not accidentally log yourself out of the panel.