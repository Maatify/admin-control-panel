<?php include __DIR__ . '/../layouts/header.php'; ?>


<h1>Managing Admins</h1>
<h2>Overview</h2>
<p>Administrators are the authorized staff members who manage and operate the platform. Because they have extensive controls and access to sensitive information, their accounts must be strictly governed. This section allows you to oversee who has access to the administrative panel, manage their account details, and monitor their security and active login sessions.</p>
<hr/>
<h2>How to Access Admins</h2>
<p>To access the administrator management area:
* Look at the left sidebar navigation menu.
* Click on <strong>Admins</strong>. This will open the main Admins List page.</p>
<hr/>
<h2>Admins List</h2>
<p>When you open the Admins section, you will see the <strong>Admins List</strong>.</p>
<ul>
<li><strong>Table Columns:</strong> A table displaying all administrator accounts, showing key details like their name, primary email, and current account status (e.g., Active or Disabled).</li>
<li><strong>Filters and Search:</strong> You can filter the list to quickly find specific accounts, such as viewing only active admins or searching by name and email.</li>
<li><strong>Actions:</strong> From this list, you can click <strong>View</strong> next to an admin to open their full profile, or click the <strong>Add Admin</strong> button to create a new account.</li>
</ul>
<hr/>
<h2>Creating a New Admin (FULL FLOW)</h2>
<p>When you need to grant a new staff member access, you must follow this exact sequence:</p>
<ol>
<li><strong>Filling the form:</strong> Click the <strong>Add Admin</strong> button and enter the required details, such as the new admin's full name and their primary email address.</li>
<li><strong>Clicking create:</strong> Submit the form to generate the account.</li>
<li><strong>What happens next:</strong> The account is created immediately, and you are automatically transitioned to a secure credentials screen.</li>
<li><strong>Temporary password screen:</strong> The system will display the new admin's initial password on the screen.</li>
<li><strong>Required actions:</strong> You must securely copy this password. You can simply click on the password field to copy it to your clipboard. To proceed and leave this screen, you are strictly required to explicitly confirm that you have secured the credentials by acknowledging the prompt ("I have saved the password"). You CANNOT proceed until this confirmation is made.</li>
</ol>
<hr/>
<h2>⚠️ Temporary Password (CRITICAL SECTION)</h2>
<p>When a new administrator is created, the system handles their initial access under strict security rules:</p>
<ul>
<li><strong>The password is auto-generated:</strong> The system creates a highly secure, random temporary password. You cannot choose it.</li>
<li><strong>It is displayed ONLY ONCE:</strong> The password appears on the screen immediately after the account creation form is submitted.</li>
<li><strong>You must copy it immediately:</strong> You are responsible for copying this password (by clicking the password field) and securely providing it to the new administrator.</li>
<li><strong>The system requires confirmation:</strong> You cannot accidentally close or navigate away without clicking to confirm "I have saved the password". The system blocks all navigation until this is done.</li>
<li><strong>It will NEVER be shown again:</strong> Once you leave that screen, the password is gone forever. If it is lost before the new admin logs in, you will have to completely reset their credentials.</li>
</ul>
<hr/>
<h2>Admin Profile</h2>
<p>Clicking <strong>View</strong> next to an administrator in the list opens their full <strong>Admin Profile</strong>. The page is structured into distinct areas:</p>
<ul>
<li><strong>Profile info:</strong> At the top, you will see their general information, such as their name and current account status.</li>
<li><strong>Sections:</strong> Below the general info, the profile is divided into specific sections:</li>
<li><strong>Emails:</strong> To manage all email addresses associated with the admin.</li>
<li><strong>Sessions:</strong> To monitor and control where the admin is currently logged in.</li>
<li><strong>Notifications:</strong> To review the history of system alerts and messages sent to this admin.</li>
</ul>
<hr/>
<h2>Managing Emails</h2>
<p>An administrator can have multiple email addresses tied to their account.</p>
<ul>
<li><strong>Multiple emails allowed:</strong> You can add backup or secondary emails to an account.</li>
<li><strong>Statuses:</strong> Every email address will display one of the following states:</li>
<li><strong>Pending:</strong> The email has been added but the owner has not yet clicked the verification link. <em>If an email is "Pending", the system displays a "Pending" badge. You will see action buttons to "Verify manually" or "Mark as failed".</em></li>
<li><strong>Verified:</strong> The email is confirmed, active, and fully usable.</li>
<li><strong>Failed:</strong> The verification process was unsuccessful or expired.</li>
<li><strong>Replaced:</strong> An older email that has been superseded by a new primary address.</li>
<li><strong>Only verified emails can be used:</strong> An admin cannot use an email to log in or receive critical system notifications until its status is <strong>Verified</strong>.</li>
<li><strong>Actions:</strong> From the Emails section, you can <strong>Add email</strong> to send a new verification request, <strong>Verify manually</strong> if you have administrative authority to bypass the email check, or <strong>Mark as failed</strong> to invalidate a pending request.</li>
</ul>
<hr/>
<h2>Managing Sessions</h2>
<p>A "session" represents an active login on a specific computer, phone, or browser.</p>
<ul>
<li><strong>Sessions list:</strong> This section shows a detailed log of everywhere the administrator is currently or was previously logged in.</li>
<li><strong>Filters and Search:</strong> You can sort the sessions using filters: <strong>All</strong>, <strong>Active</strong> (currently logged in), <strong>Expired</strong> (timed out naturally), or <strong>Revoked</strong> (forced out).</li>
<li><strong>Current session indication:</strong> The system clearly highlights your <em>Current session</em> so you do not accidentally log yourself out while reviewing the list.</li>
<li><strong>Revoke actions:</strong> If you see an unrecognized or old device, you can click <strong>Revoke</strong> next to it to instantly log that device out. You also have access to a <strong>bulk revoke</strong> action to instantly log the admin out of all active sessions at once.</li>
</ul>
<hr/>
<h2>Editing Admin</h2>
<p>If an administrator's details change or they leave the organization, you can edit their core profile.</p>
<ul>
<li><strong>Editing name and status:</strong> You can update their full name and change their account status (e.g., from Active to Disabled).</li>
<li><strong>Status affects access:</strong> Disabling an account immediately prevents that admin from logging into the platform or taking any further actions.</li>
<li><strong>Changes are audited:</strong> Every modification made to an admin's profile is strictly recorded in the system's audit logs, detailing who made the change and when.</li>
</ul>
<hr/>
<h2>Security Notes</h2>
<ul>
<li><strong>Email must be verified:</strong> No new email address grants platform access until it has passed the strict verification process.</li>
<li><strong>Temporary password is one-time only:</strong> Always be prepared to copy the temporary password during account creation, as it is never displayed twice.</li>
<li><strong>Sessions should be revoked if suspicious:</strong> If an admin reports a lost device or you notice an unusual login location, use the Revoke tools immediately to protect the platform.</li>
<li><strong>Actions are tracked:</strong> Every action, from creating an account to revoking a session, is permanently tracked in the system's audit logs for complete accountability.</li>
</ul>


<?php include __DIR__ . '/../layouts/footer.php'; ?>
