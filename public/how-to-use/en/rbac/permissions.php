<?php include __DIR__ . '/../../layouts/header.php'; ?>


<h1>Managing Permissions</h1>
<h2>Overview</h2>
<p>Permissions are the specific access rights that control exactly what an administrator can see and do on the platform. Rather than dealing with broad concepts, permissions determine the practical reality of the system: they dictate exactly which buttons appear on a page, which links show up in the navigation menu, and which pages successfully load.</p>
<h2>Where Permissions Are Managed</h2>
<p>Permissions are not created or managed from a separate, standalone page. Instead, they are managed directly from inside an existing Role.</p>
<p>To manage permissions, follow this exact flow:
1. In the left sidebar, go to <strong>RBAC</strong> → <strong>Roles</strong>.
2. Click the <strong>View</strong> button next to the role you want to manage.
3. On the role details page, click the <strong>Permissions tab</strong>.</p>
<h2>Permissions List</h2>
<p>When you open the Permissions tab, you will see a list of access rights available in the system.</p>
<ul>
<li><strong>Table Columns:</strong></li>
<li><strong>Permission name:</strong> The specific title of the access right.</li>
<li><strong>Group:</strong> The category the permission belongs to.</li>
<li><strong>Description:</strong> An explanation of what the permission allows the user to do.</li>
<li><strong>Assigned toggle:</strong> A switch used to enable or disable the permission.</li>
<li><strong>Filters and Search:</strong> Above the table, you will find a global search bar to quickly find permissions by typing any keyword (it automatically searches after a brief delay). Additionally, a filter form allows you to search specifically by Permission ID, Name, or Group. A Reset button clears all active filters.</li>
</ul>
<h2>Enabling or Disabling a Permission</h2>
<p>To grant or remove a specific access right for a role, follow these steps:</p>
<ol>
<li>Open a role using the <strong>View</strong> button from the Roles list.</li>
<li>Go to the <strong>Permissions tab</strong>.</li>
<li>Locate the specific permission you want to modify.</li>
<li>Click the <strong>Assigned toggle</strong> next to the permission to turn it ON (enabled) or OFF (disabled).</li>
</ol>
<p><strong>What happens immediately after toggle:</strong>
The change is applied to the system the exact moment you click the toggle. There is no "Save" button required on this tab. Any administrator currently holding this role will instantly have their access updated across the platform.</p>
<h2>How Permissions Affect the System</h2>
<p>When a permission is toggled on or off, it directly changes the system's interface and security boundaries:
* <strong>Sidebar sections appear/disappear:</strong> If a permission controls access to an entire section, the corresponding link in the left sidebar will instantly appear or vanish.
* <strong>Buttons appear/disappear:</strong> If a permission controls a specific action (like deleting a record), the "Delete" button will instantly become visible or hide itself on the relevant page.
* <strong>Access to pages is blocked:</strong> If an administrator attempts to visit a page for which they just had the permission disabled, the system will instantly block them and display an error.</p>
<h2>Permission Groups</h2>
<p>Permissions in the list are organized logically by a "Group" classification (e.g., separating user management permissions from system settings permissions). This is reflected in the "Group" column of the data table, allowing you to easily sort, filter, and identify related access rights visually.</p>
<h2>Real Usage Examples</h2>
<ul>
<li><strong>Enabling "Create Admin":</strong> If you toggle this permission ON for a role, administrators with that role will instantly see the "Add Admin" button appear on the Admins list page.</li>
<li><strong>Enabling "View Activity Logs":</strong> If you toggle this permission ON, the "Activity Logs" link will instantly appear in the administrator's left sidebar, allowing them to click it and view the page.</li>
<li><strong>Disabling access to "Settings":</strong> If you toggle the Settings permissions OFF, the "Settings" link will instantly disappear from the sidebar. If the administrator tries to manually type the web address to reach the Settings page, the system will actively block their access.</li>
</ul>
<h2>Important Notes</h2>
<ul>
<li><strong>Changes apply immediately:</strong> The moment you click a toggle, the access rights are updated. There is no delay.</li>
<li><strong>Permissions should be carefully managed:</strong> Only enable toggles for actions that the specific role absolutely requires to perform their job.</li>
<li><strong>Removing a permission can instantly block access:</strong> If an administrator is currently working on a page and you toggle their permission OFF, they will be blocked the next time they click a button or refresh their screen.</li>
</ul>


<?php include __DIR__ . '/../../layouts/footer.php'; ?>
