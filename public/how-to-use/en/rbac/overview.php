<?php include __DIR__ . '/../../layouts/header.php'; ?>


<h1>Access Control (Roles &amp; Permissions)</h1>
<h2>Why Access Control Exists</h2>
<p>Not all administrators require the ability to modify every part of the platform. For example, a support agent needs to view user accounts but should not be allowed to change global application settings or delete system translation languages. Access control restricts visibility and actions. It ensures that staff members can only see the specific sections of the left sidebar navigation menu—and click the specific buttons—that they actually need to perform their jobs.</p>
<h2>Roles</h2>
<p>A Role functions as a pre-packaged set of access rights designed for a specific job title, such as a "Translator" or "Content Manager". Instead of manually clicking and selecting dozens of individual access boxes every time a new staff member joins, you create a Role, check the necessary access boxes once, and then simply assign that Role to the administrator's profile.</p>
<h2>Permissions</h2>
<p>A Permission is the smallest, most specific unit of access. It dictates exactly which buttons appear and which pages load for an administrator. For example, one permission determines if the "View" button appears next to a translation, while a completely separate permission determines if the "Edit" or "Delete" buttons appear on that same page.</p>
<h2>How Roles and Permissions Work Together</h2>
<p>Roles are the primary way to manage access in the system. When you assign a Role to an administrator, they instantly receive all the underlying Permissions attached to that Role, immediately updating their navigation sidebar.</p>
<p>You can also assign Permissions directly to an administrator's profile. Direct permissions are used only for exceptional cases. For example, if a staff member needs a single, unique capability (like clicking "Publish" on a specific legal document) but does not need a completely new Role, you can grant them that specific right directly.</p>
<h2>How to Use Roles</h2>
<p>Using roles correctly saves you time and ensures consistent security. Here is the standard step-by-step process:</p>
<ol>
<li><strong>Create the Role:</strong> Go to the Roles section and create a new role based on a job function (e.g., "Customer Support"). At this stage, you are just defining the name.</li>
<li><strong>Assign Permissions to the Role:</strong> Open the newly created role, go to its Permissions section, and toggle ON all the specific actions that someone in this job would need to perform.</li>
<li><strong>Assign the Role to an Admin:</strong> Go to the specific administrator's profile, open their Roles section, and assign the "Customer Support" role to them. They will instantly gain all the access rights you previously toggled on.</li>
</ol>
<h2>How to Use Direct Permissions</h2>
<p>While Roles group permissions together, <strong>Direct Permissions</strong> are assigned directly to an individual administrator's profile, completely bypassing any Role.</p>
<ul>
<li><strong>When to use them:</strong> Use direct permissions for temporary exceptions or highly specialized tasks. For example, if an administrator normally has a standard "Editor" role but you need them to have a one-time ability to delete a specific system log, you can grant them that single "Delete Log" permission directly.</li>
<li><strong>When NOT to use them:</strong> Do not use direct permissions as your primary way of managing access. If you find yourself assigning the same 10 direct permissions to multiple people, you should create a Role instead.</li>
</ul>
<h2>What Happens When Access Changes</h2>
<p>When an administrator's assigned Roles or Permissions are modified, the effects happen instantly across their active sessions.
*   Entire menu items (like "Admins", "Sessions", or "Settings") in the left sidebar navigation will instantly appear or disappear based on what they are newly allowed to view.
*   Specific action buttons (such as "Create Admin," "Edit Profile," or "Revoke Session") within a page will instantly become visible or be hidden.</p>
<h2>What Happens If Access is Denied</h2>
<p>From the perspective of an administrator, the system is designed to be invisible unless they have the proper access. If an administrator does not have permission to perform an action:</p>
<ul>
<li><strong>Buttons will not appear:</strong> The "Delete" or "Edit" buttons will simply be missing from their screen.</li>
<li><strong>Pages will not load:</strong> If they try to manually type the web address of a page they shouldn't see, the page will not load. Instead, the system will actively block the request and display an "Access Denied" error message.</li>
<li><strong>Actions are blocked:</strong> Even if they somehow clicked a button before their access was revoked, the system will block the underlying action from completing.</li>
</ul>
<h2>Real Usage Scenario</h2>
<p>Imagine you create a "Translation Specialist" role and assign it only the permissions to view and edit Translations.</p>
<ul>
<li><strong>What the user sees:</strong> When an administrator with this role logs in, they will only see the "Languages" and "Translations" links in their left sidebar. Inside the Translations page, they will see the "Edit" buttons allowing them to update localized text.</li>
<li><strong>What the user cannot see:</strong> The "Admins," "Sessions," and "Settings" links will be completely missing from their sidebar.</li>
<li><strong>What happens if access is removed:</strong> If you suddenly remove the "Translation Specialist" role from their profile while they are working, the "Translations" link will vanish from their screen. If they try to click "Edit" on a translation they were just looking at, the system will instantly block them and show an error.</li>
</ul>
<h2>Common Mistakes</h2>
<p>When managing access, administrators often fall into these traps:</p>
<ul>
<li><strong>Giving too many permissions:</strong> Assigning a broad "Manager" role to someone who only needs to view logs violates security. Always start with zero access and add only what is necessary.</li>
<li><strong>Forgetting direct permissions:</strong> Because direct permissions bypass Roles, it is easy to forget someone has them. Always review an administrator's direct permissions periodically to ensure they don't have leftover access from a past project.</li>
<li><strong>Confusion between Roles and Permissions:</strong> Remember that a Role does nothing on its own; it is simply a container. You must toggle the Permissions <em>inside</em> the Role for it to have any effect.</li>
</ul>
<h2>Best Practices</h2>
<ul>
<li><strong>Always use Roles first:</strong> Group permissions logically by job title. This keeps your system organized and easy to audit.</li>
<li><strong>Keep permissions minimal:</strong> Follow the principle of least privilege. Only grant the exact permissions required for the job.</li>
<li><strong>Use direct permissions only when necessary:</strong> Reserve direct permissions for rare, highly specific exceptions to keep your overall access strategy clean and manageable.</li>
</ul>
<hr/>


<?php include __DIR__ . '/../../layouts/footer.php'; ?>
