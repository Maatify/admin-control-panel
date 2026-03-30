<?php include __DIR__ . '/../layouts/header.php'; ?>


<h1>Admin Panel Overview</h1>
<h2>1. What is this system?</h2>
<p>This system is an administrative control panel designed to help you securely manage the platform. It allows you to organize user accounts, assign roles and permissions, configure platform settings, manage translated content across multiple languages, and handle important policies or documents. The platform is built with strong security in mind, ensuring all actions are tracked, verified, and protected against unauthorized access.</p>
<h2>2. Main Areas of the System</h2>
<ul>
<li>
<p><strong>User &amp; Admin Management</strong>
  Allows you to create and manage administrative accounts. You can update their profiles, manage their associated email addresses, configure notification preferences, and oversee their active login sessions.</p>
</li>
<li>
<p><strong>Roles &amp; Permissions</strong>
  Provides a way to control who has access to what. You can create different roles, assign specific permissions to these roles, and then grant them to administrators. You can also assign direct permissions to specific users for more granular control.</p>
</li>
<li>
<p><strong>Languages &amp; Translations</strong>
  Enables you to manage the platform in multiple languages. You can create new languages, set fallback (default) languages, define translation keys, and update the actual translated text for different sections of the application.</p>
</li>
<li>
<p><strong>Content &amp; Documents</strong>
  Allows you to manage important platform documents (like Terms of Service or Privacy Policies). You can create different document types, manage multiple versions, publish new versions, add translations for them, and enforce requirements for users to accept these documents.</p>
</li>
<li>
<p><strong>System Settings</strong>
  Provides tools to manage application-wide settings and configurations. You can view, create, and update metadata or toggle specific platform features on and off.</p>
</li>
<li>
<p><strong>Security &amp; Sessions</strong>
  Offers oversight into how users are accessing the system. You can view a list of active login sessions and revoke them individually or in bulk if needed.</p>
</li>
<li>
<p><strong>Notifications &amp; Activity Logging</strong>
  Keeps you informed of system events. You can review your own notifications, view historical notifications, and access a detailed activity log that records actions taken within the system.</p>
</li>
</ul>
<h2>📂 Documentation Map</h2>
<p>Use the links below to navigate through the complete admin guide.</p>
<h3>👥 Admin Management</h3>
<p>Learn how to create, manage, and monitor the administrators who operate the platform.</p>
<ul>
<li>Admins → <a href="/how-to-use/en/admins.php">admins.md</a></li>
<li>Admin Permissions → <a href="/how-to-use/en/admins-permissions.php">admins-permissions.md</a></li>
<li>Sessions → <a href="/how-to-use/en/sessions.php">sessions.md</a></li>
</ul>
<h3>⚙️ Settings</h3>
<p>Control dynamic application configurations and manage authoritative, version-controlled legal documents.</p>
<ul>
<li>Overview → <a href="/how-to-use/en/settings/overview.php">settings/overview.md</a></li>
<li>Content Documents → <a href="/how-to-use/en/settings/content-documents.php">settings/content-documents.md</a></li>
<li>App Settings → <a href="/how-to-use/en/settings/app-settings.php">settings/app-settings.md</a></li>
</ul>
<h3>🔐 RBAC</h3>
<p>Understand how access control works and how to assign the right capabilities to the right people.</p>
<ul>
<li>Overview → <a href="/how-to-use/en/rbac/overview.php">rbac/overview.md</a></li>
<li>Roles → <a href="/how-to-use/en/rbac/roles.php">rbac/roles.md</a></li>
<li>Permissions → <a href="/how-to-use/en/rbac/permissions.php">rbac/permissions.md</a></li>
</ul>
<h3>🌍 Languages</h3>
<p>Manage the languages supported by the platform.</p>
<ul>
<li>Languages → <a href="/how-to-use/en/languages.php">languages.md</a></li>
</ul>
<h3>🔤 Translations</h3>
<p>Precisely control the translated text displayed to users.</p>
<ul>
<li>Main Guide → <a href="/how-to-use/en/translations.php">translations.md</a></li>
<li>Overview → <a href="/how-to-use/en/translations/overview.php">translations/overview.md</a></li>
<li>Scopes → <a href="/how-to-use/en/translations/scopes.php">translations/scopes.md</a></li>
<li>Domains → <a href="/how-to-use/en/translations/domains.php">translations/domains.md</a></li>
</ul>
<h2>3. Common Tasks</h2>
<h3>Logging In</h3>
<p>To access the system, you must log in using your credentials. After providing your email and password, you will be required to complete a Two-Factor Authentication (2FA) step to verify your identity.</p>
<h3>Managing Admins</h3>
<p>You can view a list of all administrators. When managing a specific admin, you can edit their profile details, add or update their email addresses, and review their notification preferences.</p>
<h3>Assigning Roles</h3>
<p>Once roles are created (such as "Manager" or "Support"), you can assign them to admins. If a role is no longer appropriate, you can unassign it. You can also manage the specific permissions associated with each role to change what that role is allowed to do.</p>
<h3>Editing Translations</h3>
<p>To update text on the platform, navigate to the translations area. You can assign languages to different sections (scopes) of the platform, search for specific translation keys, and update the translated text.</p>
<h3>Managing Documents</h3>
<p>When a new policy needs to be published, you first create a document version. Once drafted, you can add translations for it. When ready, you can publish the version, making it active and archiving older versions if necessary.</p>
<h3>Reviewing Activity Logs</h3>
<p>To see who did what, you can check the Activity Logs. This provides a secure trail of actions performed within the admin panel, helping with accountability and troubleshooting.</p>
<h2>4. Security &amp; Protection</h2>
<ul>
<li>
<p><strong>Login Security (2FA)</strong>
  Accessing sensitive areas of the panel requires Two-Factor Authentication. This means even if someone knows your password, they cannot access the system without your secondary verification method.</p>
</li>
<li>
<p><strong>Session Control</strong>
  Every time you log in, a session is created. You have full visibility into your active sessions and can revoke (log out) any unrecognized or old sessions to keep your account secure.</p>
</li>
</ul>
<h2>5. Important Notes</h2>
<ul>
<li><strong>Some changes require activation:</strong> Creating a new document version or adding a new language doesn't immediately make it visible to end-users. You must explicitly "publish" or "set active" these items before they take effect.</li>
<li><strong>Permissions affect visibility:</strong> The menus and options you see in the admin panel depend entirely on your assigned roles and permissions. If you cannot see a feature, it means your account does not have the required access.</li>
<li><strong>Some actions require verification:</strong> Changing critical account details, like email addresses or passwords, may trigger a verification step (like receiving a confirmation email) before the change is finalized.</li>
</ul>


<?php include __DIR__ . '/../layouts/footer.php'; ?>
