<?php include __DIR__ . '/../layouts/header.php'; ?>


<h1>Managing Sessions</h1>
<h2>Overview</h2>
<p>A "session" represents a specific instance of an administrator being logged into the platform on a particular computer, phone, or web browser. Because administrators have access to sensitive controls, tracking these sessions helps you see exactly where and how accounts are being used. If a device is lost or an account is compromised, you can use the Sessions management tools to instantly cut off access.</p>
<h2>How to Access Sessions</h2>
<p>There are two primary ways to view and manage active login sessions across the platform.</p>
<h3>Option 1: Global Sessions Page</h3>
<p>To view a list of all active sessions across all administrators:
1. Look at the left sidebar navigation menu.
2. Click on <strong>Sessions</strong>. This opens the global Sessions list.</p>
<h3>Option 2: Per Admin Sessions</h3>
<p>To view only the sessions belonging to a specific administrator:
1. In the left sidebar, click on <strong>Admins</strong>.
2. Click the <strong>View</strong> button next to the specific administrator you want to inspect.
3. On their profile page, click the <strong>Sessions tab</strong>.</p>
<h2>Sessions List</h2>
<p>Whether you are looking at the global list or a specific admin's profile, the Sessions table provides details about each active login.</p>
<ul>
<li><strong>Table Columns:</strong></li>
<li><strong>User ID:</strong> The identifier of the administrator the session belongs to.</li>
<li><strong>Session ID:</strong> The unique identifier for the specific login instance.</li>
<li><strong>Status:</strong> Whether the session is currently Active, Expired, or Revoked.</li>
<li><strong>Expires At:</strong> The exact date and time the session will naturally time out.</li>
<li><strong>Current session indication:</strong> The system clearly highlights your <em>Current session</em> so you do not accidentally log yourself out.</li>
<li><strong>Filters and Search:</strong> Above the table, you can click quick-filter badges to instantly view <strong>All</strong>, <strong>Active</strong>, <strong>Expired</strong>, or <strong>Revoked</strong> sessions. Additionally, a search form allows you to filter specifically by <strong>Session ID</strong>, <strong>Admin ID</strong>, and <strong>Status</strong>. A Reset button clears all active filters.</li>
</ul>
<h2>Revoking a Session</h2>
<p>If you need to force an administrator to log out of a specific device, you must revoke their session.</p>
<ol>
<li>Locate the specific session in the list (either on the global Sessions page or the admin's profile).</li>
<li>Click the <strong>Revoke</strong> action button next to that session.</li>
<li><em>Note on confirmation behavior:</em> When performing a bulk revoke of multiple selected sessions, the system explicitly prompts you with a confirmation dialog ("Revoke [X] session(s)?").</li>
<li><em>Note on partial revocation:</em> You cannot partially revoke a session. Clicking Revoke immediately terminates the entire login session.</li>
</ol>
<p>If you need to log an administrator out of <em>every</em> device at once, you can use the <strong>bulk revoke</strong> action available on the admin's profile.</p>
<h2>What Happens After Revocation</h2>
<ul>
<li><strong>The session is immediately terminated:</strong> The moment you click Revoke, the system invalidates that specific login.</li>
<li><strong>The user is forced out:</strong> If the administrator is currently using the panel on that revoked device, they will be instantly kicked back to the login screen the next time they click a link or refresh the page.</li>
<li><strong>Access is blocked until re-login:</strong> The device cannot access the admin panel again until the administrator re-enters their email, password, and completes their Two-Factor Authentication (2FA) verification.</li>
</ul>
<h2>Security Use Cases</h2>
<ul>
<li><strong>Unknown device detected:</strong> If you or another administrator notice an active session from an unfamiliar location or browser, you can instantly revoke it to secure the account.</li>
<li><strong>Admin left the company:</strong> When an administrator resigns or is terminated, you should immediately bulk revoke all of their active sessions and disable their account to ensure they cannot access the platform from a personal device.</li>
<li><strong>Shared device cleanup:</strong> If an administrator accidentally leaves themselves logged in on a public or shared computer, they (or you) can remotely revoke that specific session without affecting their access on their primary work computer.</li>
</ul>
<h2>Important Notes</h2>
<ul>
<li><strong>Revoking is immediate:</strong> There is no delay. The device loses access the exact second the Revoke button is pressed.</li>
<li><strong>Current session safety:</strong> Always pay attention to the <em>Current session</em> indicator to ensure you do not accidentally log yourself out of the panel.</li>
</ul>


<?php include __DIR__ . '/../layouts/footer.php'; ?>
