# Admin Panel Overview

## 1. What is this system?

This system is an administrative control panel designed to help you securely manage the platform. It allows you to organize user accounts, assign roles and permissions, configure platform settings, manage translated content across multiple languages, and handle important policies or documents. The platform is built with strong security in mind, ensuring all actions are tracked, verified, and protected against unauthorized access.

## 2. Main Areas of the System

* **User & Admin Management**
  Allows you to create and manage administrative accounts. You can update their profiles, manage their associated email addresses, configure notification preferences, and oversee their active login sessions.

* **Roles & Permissions**
  Provides a way to control who has access to what. You can create different roles, assign specific permissions to these roles, and then grant them to administrators. You can also assign direct permissions to specific users for more granular control.

* **Languages & Translations**
  Enables you to manage the platform in multiple languages. You can create new languages, set fallback (default) languages, define translation keys, and update the actual translated text for different sections of the application.

* **Content & Documents**
  Allows you to manage important platform documents (like Terms of Service or Privacy Policies). You can create different document types, manage multiple versions, publish new versions, add translations for them, and enforce requirements for users to accept these documents.

* **System Settings**
  Provides tools to manage application-wide settings and configurations. You can view, create, and update metadata or toggle specific platform features on and off.

* **Security & Sessions**
  Offers oversight into how users are accessing the system. You can view a list of active login sessions and revoke them individually or in bulk if needed.

* **Notifications & Activity Logging**
  Keeps you informed of system events. You can review your own notifications, view historical notifications, and access a detailed activity log that records actions taken within the system.

## 📂 Documentation Map

Use the links below to navigate through the complete admin guide.

### 👥 Admin Management
Learn how to create, manage, and monitor the administrators who operate the platform.
* Admins → [docs/admin/admins.md](admins.md)
* Admin Permissions → [docs/admin/admins-permissions.md](admins-permissions.md)
* Sessions → [docs/admin/sessions.md](sessions.md)

### ⚙️ Settings
Control dynamic application configurations and manage authoritative, version-controlled legal documents.
* Overview → [docs/admin/settings/overview.md](settings/overview.md)
* Content Documents → [docs/admin/settings/content-documents.md](settings/content-documents.md)
* App Settings → [docs/admin/settings/app-settings.md](settings/app-settings.md)

### 🔐 RBAC
Understand how access control works and how to assign the right capabilities to the right people.
* Overview → [docs/admin/rbac/overview.md](rbac/overview.md)
* Roles → [docs/admin/rbac/roles.md](rbac/roles.md)
* Permissions → [docs/admin/rbac/permissions.md](rbac/permissions.md)

### 🌍 Localization
Manage localized content and regional settings such as currencies.
* Currencies → [docs/admin/localization/currencies.md](localization/currencies.md)

### 🌍 Languages
Manage the languages supported by the platform.
* Languages → [docs/admin/languages.md](languages.md)

### 🔤 Translations
Precisely control the translated text displayed to users.
* Main Guide → [docs/admin/translations.md](translations.md)
* Overview → [docs/admin/translations/overview.md](translations/overview.md)
* Scopes → [docs/admin/translations/scopes.md](translations/scopes.md)
* Domains → [docs/admin/translations/domains.md](translations/domains.md)

## 3. Common Tasks

### Logging In
To access the system, you must log in using your credentials. After providing your email and password, you will be required to complete a Two-Factor Authentication (2FA) step to verify your identity.

### Managing Admins
You can view a list of all administrators. When managing a specific admin, you can edit their profile details, add or update their email addresses, and review their notification preferences.

### Assigning Roles
Once roles are created (such as "Manager" or "Support"), you can assign them to admins. If a role is no longer appropriate, you can unassign it. You can also manage the specific permissions associated with each role to change what that role is allowed to do.

### Editing Translations
To update text on the platform, navigate to the translations area. You can assign languages to different sections (scopes) of the platform, search for specific translation keys, and update the translated text.

### Managing Documents
When a new policy needs to be published, you first create a document version. Once drafted, you can add translations for it. When ready, you can publish the version, making it active and archiving older versions if necessary.

### Reviewing Activity Logs
To see who did what, you can check the Activity Logs. This provides a secure trail of actions performed within the admin panel, helping with accountability and troubleshooting.

## 4. Security & Protection

* **Login Security (2FA)**
  Accessing sensitive areas of the panel requires Two-Factor Authentication. This means even if someone knows your password, they cannot access the system without your secondary verification method.

* **Session Control**
  Every time you log in, a session is created. You have full visibility into your active sessions and can revoke (log out) any unrecognized or old sessions to keep your account secure.




## 5. Important Notes

* **Some changes require activation:** Creating a new document version or adding a new language doesn't immediately make it visible to end-users. You must explicitly "publish" or "set active" these items before they take effect.
* **Permissions affect visibility:** The menus and options you see in the admin panel depend entirely on your assigned roles and permissions. If you cannot see a feature, it means your account does not have the required access.
* **Some actions require verification:** Changing critical account details, like email addresses or passwords, may trigger a verification step (like receiving a confirmation email) before the change is finalized.
