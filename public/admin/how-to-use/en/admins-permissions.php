<?php include __DIR__ . '/../layouts/header.php'; ?>


<h1>Managing Permissions</h1>
<h2>1. Overview</h2>
<p>This page displays ALL permissions currently affecting a specific administrator. It acts as the final source of truth for access control, combining the permissions granted indirectly through Roles with the permissions granted via Direct Permissions. It reflects the real access behavior and exact security boundaries currently applied to the administrator in the system.</p>
<h2>2. How to Access</h2>
<p>To manage or view an administrator's permissions:
1. Navigate to <strong>Admins</strong> in the left sidebar.
2. Click <strong>View</strong> next to the specific administrator.
3. Click the <strong>Permissions</strong> link/section.</p>
<ul>
<li>You can use the breadcrumb navigation at the top of the page to easily return to the main Admins list.</li>
<li>You can click the <strong>Back to Profile</strong> button to return to the administrator's main profile view.</li>
</ul>
<h2>3. Tabs Explanation</h2>
<p>The Permissions page is divided into three critical tabs to help you understand and manage exactly how access is being granted.</p>
<h3>Effective Tab</h3>
<ul>
<li><strong>What it does:</strong> Shows the final, calculated permissions applied to the admin.</li>
<li><strong>What it includes:</strong> This list combines both Role-based permissions and Direct permissions into one comprehensive view.</li>
<li><strong>Source Column:</strong> Includes a specific "Source" column that tells you exactly where the permission comes from (e.g., whether it was granted by a specific Role or via a Direct Allow override).</li>
<li><strong>Read-only:</strong> This tab is purely for auditing. There is no editing or assigning done from this tab.</li>
</ul>
<h3>Direct Tab</h3>
<ul>
<li><strong>What it does:</strong> Shows ONLY the permissions that have been assigned directly to this individual administrator, completely ignoring any Roles they hold.</li>
<li><strong>What it includes:</strong> It details the Type (whether the direct permission is an "Allowed" override or a "Denied" override) and the "Granted At" timestamp.</li>
<li><strong>Actions:</strong> From here, you can click the <strong>Assign Permission</strong> button to add a new direct override, or use the <strong>Edit</strong> and <strong>Revoke</strong> action buttons on existing rows. Revoking simply removes the direct assignment; it does not block the permission if the user still holds it via a Role.</li>
</ul>
<h3>Roles Tab</h3>
<ul>
<li><strong>What it does:</strong> Shows the specific security roles currently assigned to the administrator.</li>
<li><strong>What it includes:</strong> It lists the Role Name and its current Status (Active or Inactive).</li>
<li><strong>Clarification:</strong> Roles indirectly grant permissions. Assigning a role to an administrator grants them all the permissions bundled inside that role, which will then populate in the Effective tab.</li>
</ul>
<h2>4. Filters &amp; Search</h2>
<p>To help you find specific access rights, the following tools are available across the tabs:</p>
<ul>
<li><strong>Search inputs:</strong> You can filter the tables by typing into the specific input boxes provided for <strong>Permission ID</strong>, <strong>Permission Name</strong>, and <strong>Group</strong>.</li>
<li><strong>Reset button:</strong> A button is provided to instantly clear all active filters and return the table to its default view.</li>
<li><strong>Table search bar:</strong> A global search bar exists to quickly search across the entire current table.</li>
<li><strong>Export buttons:</strong> You can export the current view of the permissions table using the <strong>CSV</strong>, <strong>Excel</strong>, and <strong>PDF</strong> export buttons.</li>
</ul>
<h2>5. Assign Permission Flow</h2>
<p>To grant or block a specific permission directly for this administrator, bypassing their roles:</p>
<ol>
<li>Click the <strong>Assign Permission</strong> button located on the Direct tab.</li>
<li>A large modal opens titled <strong>Assignable Permissions</strong>.</li>
</ol>
<p>Inside the modal:</p>
<ul>
<li>You will see a paginated, searchable table of every permission available in the system.</li>
<li>The columns displayed in this modal are: <strong>ID</strong>, <strong>Name</strong>, <strong>Group</strong>, <strong>Display Name</strong>, <strong>Assigned</strong>, <strong>Type</strong>, and <strong>Expires At</strong>.</li>
<li><strong>Action:</strong> Each row has a toggle or button to assign the permission.</li>
<li><strong>Configuration:</strong> When assigning, you can set the Type to <strong>Allowed</strong> (to grant access) or <strong>Denied</strong> (to explicitly block access, even if a role grants it). You can optionally set an expiration date.</li>
<li><strong>Result:</strong> Once assigned, the permission reflects instantly in the Direct tab and recalculates the administrator's final access in the Effective tab.</li>
</ul>
<h2>6. Editing / Revoking</h2>
<p>From the <strong>Direct</strong> tab, you can modify existing direct assignments using the buttons in the Actions column:</p>
<ul>
<li><strong>Edit button:</strong> Allows you to modify the current assignment. You can change its Type (flipping an "Allow" to a "Deny") and update or remove its expiration date.</li>
<li><strong>Revoke button:</strong> Clicking this completely deletes the direct permission assignment.</li>
</ul>
<p><em>Effect:</em> The changes take effect immediately. If you revoke a "Deny" override, and the admin has a Role that allows that permission, they will instantly regain access.</p>
<h2>7. Table Columns</h2>
<p>The columns displayed in the UI exactly match these structures:</p>
<h3>Effective</h3>
<ul>
<li>ID</li>
<li>Name</li>
<li>Group</li>
<li>Display Name</li>
<li>Description</li>
<li>Source</li>
<li>Expires At</li>
</ul>
<h3>Direct</h3>
<ul>
<li>ID</li>
<li>Name</li>
<li>Group</li>
<li>Display Name</li>
<li>Type</li>
<li>Expires At</li>
<li>Granted At</li>
<li>Actions</li>
</ul>
<h3>Roles</h3>
<ul>
<li>ID</li>
<li>Name</li>
<li>Group</li>
<li>Display Name</li>
<li>Description</li>
<li>Status</li>
</ul>
<h2>8. Behavior Rules</h2>
<ul>
<li><strong>Effective = final result:</strong> The Effective tab is the absolute truth of what the administrator can and cannot do right now.</li>
<li><strong>Direct overrides role behavior:</strong> If a Role grants a permission, but a Direct Permission denies it, the Direct permission wins.</li>
<li><strong>Deny overrides allow:</strong> An explicit "Deny" will always override an "Allow", regardless of how many roles grant the "Allow".</li>
<li><strong>UI updates immediately:</strong> Toggling, assigning, or revoking permissions refreshes the tables and access rights instantly.</li>
<li><strong>Changes affect admin instantly:</strong> If the administrator is currently logged in, modifying their permissions will instantly show or hide buttons and sidebar links on their screen.</li>
</ul>
<h2>9. Real Usage Examples</h2>
<ul>
<li><strong>Adding a direct permission to override a role:</strong> An administrator has the "Support" role which does not allow deleting users. You need them to delete a specific spam account today. You click "Assign Permission", find <code>users.delete</code>, and assign it as "Allowed" with an expiration set for tomorrow.</li>
<li><strong>Revoking a permission granted via direct assignment:</strong> The administrator finishes the task early. You go to their Direct tab, find the <code>users.delete</code> override, and click "Revoke". They instantly lose the ability to delete users.</li>
<li><strong>Seeing permission source in Effective tab:</strong> You want to know <em>why</em> an admin can access the Billing page. You check their Effective tab, search for <code>billing.view</code>, and look at the "Source" column to see which specific Role is granting them that access.</li>
</ul>
<h2>10. Important Notes</h2>
<ul>
<li><strong>Changes are instant:</strong> Be absolutely certain before clicking Assign or Revoke.</li>
<li><strong>Permissions impact visibility:</strong> Granting or revoking permissions will instantly add or remove sidebar links and action buttons from the administrator's interface.</li>
<li><strong>Direct permissions should be used sparingly:</strong> Rely on Roles for 99% of your access management. Direct permissions should be reserved strictly for temporary exceptions or highly unique administrative requirements.</li>
</ul>
<hr/>


<?php include __DIR__ . '/../layouts/footer.php'; ?>
