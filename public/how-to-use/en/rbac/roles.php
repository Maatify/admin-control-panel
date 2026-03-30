<?php include __DIR__ . '/../../layouts/header.php'; ?>

<main class="main-content">
<h1>Managing Roles</h1>
<h2>Overview</h2>
<p>Roles control access across the platform. A Role groups specific permissions together so you can grant a standard set of access rights to an administrator with a single assignment, rather than individually managing hundreds of specific permissions.</p>
<h2>How to Access Roles</h2>
<p>To manage roles:
1. Look at the left sidebar navigation menu.
2. Open the <strong>RBAC</strong> section.
3. Click on <strong>Roles</strong>. This opens the main Roles List page.</p>
<h2>Roles List</h2>
<p>When you open the Roles section, you will see a table of all existing roles.
* <strong>Table Columns:</strong> The table displays the name of each Role and its current status.
* <strong>Actions:</strong> You can click the <strong>View</strong> or <strong>Edit</strong> buttons next to an existing role, or click the <strong>Create Role</strong> button to add a new one.
* <strong>Filters and Search:</strong> Above the table, there is a global search bar to instantly find roles by name or group. You can also click the quick-filter badges to view "All", "Active", or "Inactive" roles. Additionally, a dedicated filter form allows you to search specifically by Role ID, Name, or Group, complete with a Reset button to clear active filters.</p>
<h2>Creating a Role (FULL FLOW)</h2>
<p>When you need to define a new job function with specific access rights, follow these steps:</p>
<ol>
<li>Click the <strong>Create Role</strong> button.</li>
<li>Enter a clear, descriptive <strong>Role Name</strong> (e.g., "Support Agent" or "Translator").</li>
<li>Click the <strong>Create Role</strong> button to finalize the creation.</li>
<li><strong>Result:</strong> The creation step only creates the role's basic information (its name). It does not fully manage permissions from this step. After creation, the new role will immediately appear in the Roles List.</li>
</ol>
<h2>Viewing and Managing a Role</h2>
<p>The primary way to configure a role's access rights and assignments is through its management screen.</p>
<ol>
<li>From the Roles List, click the <strong>View</strong> button next to a role.</li>
<li>This opens the role details page.</li>
<li>This page contains two main sections:</li>
<li><strong>Permissions tab:</strong> Where you control what the role allows.</li>
<li><strong>Admins tab:</strong> Where you control who holds the role.</li>
</ol>
<h3>Permissions Management</h3>
<p>Permissions are strictly managed from the <strong>Permissions tab</strong>.
1. Open the <strong>Permissions tab</strong> on the role details page.
2. A list of permissions is shown.
3. Each permission has a toggle to enable or disable it.
4. Clicking a toggle immediately enables or disables that permission for the role.
* <strong>Result:</strong> Changing a toggle instantly updates the role's access rights across the entire platform.</p>
<h3>Admin Assignment from Role Page</h3>
<p>You manage who holds this specific role from the <strong>Admins tab</strong>.
1. Open the <strong>Admins tab</strong> on the role details page.
2. The tab shows a list of administrators.
3. Each administrator has a toggle next to their name.
4. Clicking an administrator's toggle instantly assigns or unassigns this role for them.</p>
<h2>Editing a Role</h2>
<p>If you only need to change the basic details of a role:</p>
<ol>
<li>From the Roles List, click the <strong>Edit</strong> button next to the role.</li>
<li>You can modify its basic information, such as its name.</li>
<li>Click the <strong>Save</strong> button to apply the changes.</li>
</ol>
<h2>What Happens When a Role Changes</h2>
<p>Because roles are tied directly to an administrator's session, any modifications take effect immediately.
* <strong>Sidebar updates:</strong> If you add or remove permissions that control entire sections of the platform (like the "Settings" menu), that link will instantly appear or disappear from the left sidebar of any administrator holding that role.
* <strong>Buttons appear/disappear:</strong> If you add or remove specific action permissions (like "Create Admin"), that specific button will instantly become visible or be hidden on the relevant pages.
* If an administrator is actively viewing a page that a role change just revoked access to, the system will block them the next time they click a button or refresh the page.</p>
<h2>Important Notes</h2>
<ul>
<li>Always ensure roles match actual job responsibilities.</li>
<li>Avoid overcomplicating roles; keep them broad enough to be useful for multiple staff members with similar duties.</li>
<li>Prefer assigning Roles over granting Direct Permissions whenever possible, as Roles are much easier to track, audit, and manage at scale.</li>
</ul>
</main>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
