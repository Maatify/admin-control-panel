# Managing Admins

## Overview

Administrators are the authorized staff members who manage and operate the platform. Because they have extensive controls and access to sensitive information, their accounts must be strictly governed. This section allows you to oversee who has access to the administrative panel, manage their account details, and monitor their security and active login sessions.

---

## How to Access Admins

To access the administrator management area:
* Look at the left sidebar navigation menu.
* Click on **Admins**. This will open the main Admins List page.

---

## Admins List

When you open the Admins section, you will see the **Admins List**.

* **What is visible:** A table displaying all administrator accounts, showing key details like their name, primary email, and current account status (e.g., Active or Disabled).
* **Filters:** You can filter the list to quickly find specific accounts, such as viewing only active admins or searching by name and email.
* **Actions:** From this list, you can click on an admin to **View** their full profile, or click the **Add Admin** button to create a new account.

---

## Creating a New Admin (FULL FLOW)

When you need to grant a new staff member access, you must follow this exact sequence:

1. **Filling the form:** Click the **Add Admin** button and enter the required details, such as the new admin's full name and their primary email address.
2. **Clicking create:** Submit the form to generate the account.
3. **What happens next:** The account is created immediately, and you are automatically transitioned to a secure credentials screen.
4. **Temporary password screen:** The system will display the new admin's initial password on the screen.
5. **Required actions:** You must securely copy this password. To proceed and leave this screen, you are required to explicitly confirm that you have secured the credentials by acknowledging a prompt (e.g., "I have saved the password").

---

## ⚠️ Temporary Password (CRITICAL SECTION)

When a new administrator is created, the system handles their initial access under strict security rules:

* **The password is auto-generated:** The system creates a highly secure, random temporary password. You cannot choose it.
* **It is displayed ONLY ONCE:** The password appears on the screen immediately after the account creation form is submitted.
* **You must copy it immediately:** You are responsible for copying this password and securely providing it to the new administrator.
* **The system requires confirmation:** You cannot accidentally close or navigate away without acknowledging the prompt stating "I have saved the password".
* **It will NEVER be shown again:** Once you leave that screen, the password is gone forever. If it is lost before the new admin logs in, you will have to completely reset their credentials.

---

## Admin Profile

Clicking on any administrator in the list opens their full **Admin Profile**. The page is structured into distinct areas:

* **Profile info:** At the top, you will see their general information, such as their name and current account status.
* **Sections:** Below the general info, the profile is divided into specific tabs or sections:
  * **Emails:** To manage all email addresses associated with the admin.
  * **Sessions:** To monitor and control where the admin is currently logged in.
  * **Notifications:** To review the history of system alerts and messages sent to this admin.

---

## Managing Emails

An administrator can have multiple email addresses tied to their account.

* **Multiple emails allowed:** You can add backup or secondary emails to an account.
* **Statuses:** Every email address will display one of the following states:
  * **Pending:** The email has been added but the owner has not yet clicked the verification link.
  * **Verified:** The email is confirmed, active, and fully usable.
  * **Failed:** The verification process was unsuccessful or expired.
  * **Replaced:** An older email that has been superseded by a new primary address.
* **Only verified emails can be used:** An admin cannot use an email to log in or receive critical system notifications until its status is **Verified**.
* **Actions:** From the Emails section, you can **Add email** to send a new verification request, **Verify manually** if you have administrative authority to bypass the email check, or **Mark as failed** to invalidate a pending request.

---

## Managing Sessions

A "session" represents an active login on a specific computer, phone, or browser.

* **Sessions list:** This tab shows a detailed log of everywhere the administrator is currently or was previously logged in.
* **Filters:** You can sort the sessions using filters: **All**, **Active** (currently logged in), **Expired** (timed out naturally), or **Revoked** (forced out).
* **Current session indication:** The system clearly highlights your *Current session* so you do not accidentally log yourself out while reviewing the list.
* **Revoke actions:** If you see an unrecognized or old device, you can click **Revoke** next to it to instantly log that device out. You also have access to a **bulk revoke** action to instantly log the admin out of all active sessions at once.

---

## Editing Admin

If an administrator's details change or they leave the organization, you can edit their core profile.

* **Editing name and status:** You can update their full name and change their account status (e.g., from Active to Disabled).
* **Status affects access:** Disabling an account immediately prevents that admin from logging into the platform or taking any further actions.
* **Changes are audited:** Every modification made to an admin's profile is strictly recorded in the system's audit logs, detailing who made the change and when.

---

## Security Notes

* **Email must be verified:** No new email address grants platform access until it has passed the strict verification process.
* **Temporary password is one-time only:** Always be prepared to copy the temporary password during account creation, as it is never displayed twice.
* **Sessions should be revoked if suspicious:** If an admin reports a lost device or you notice an unusual login location, use the Revoke tools immediately to protect the platform.
* **Actions are tracked:** Every action, from creating an account to revoking a session, is permanently tracked in the system's audit logs for complete accountability.