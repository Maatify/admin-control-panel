<?php include __DIR__ . '/../layouts/header.php'; ?>


<h1>خريطة الوثائق</h1>
<p>مرحبًا بك في مركز المساعدة. يوجد أدناه خريطة بجميع أقسام الوثائق المتاحة.</p>

<h2>إدارة المشرفين</h2>
<ul>
    <li><a href="overview.php">ملخص</a></li>
    <li><a href="admins.php">المشرفين</a></li>
    <li><a href="admins-permissions.php">صلاحيات المشرفين</a></li>
    <li><a href="sessions.php">الجلسات</a></li>
</ul>

<h2>الإعدادات</h2>
<ul>
    <li><a href="settings/overview.php">نظرة عامة على الإعدادات</a></li>
    <li><a href="settings/content-documents.php">مستندات المحتوى</a></li>
    <li><a href="settings/app-settings.php">إعدادات التطبيق</a></li>
    <li><a href="languages.php">اللغات</a></li>
    <li><a href="currencies.php">العملات</a></li>
</ul>

<h2>التحكم في الوصول (RBAC)</h2>
<ul>
    <li><a href="rbac/overview.php">نظرة عامة على RBAC</a></li>
    <li><a href="rbac/roles.php">الأدوار</a></li>
    <li><a href="rbac/permissions.php">الصلاحيات</a></li>
</ul>

<h2>الترجمات</h2>
<ul>
    <li><a href="translations/overview.php">نظرة عامة على الترجمات</a></li>
    <li><a href="translations/scopes.php">النطاقات</a></li>
    <li><a href="translations/domains.php">المجالات</a></li>
    <li><a href="translations.php">الترجمات (القديمة)</a></li>
</ul>


<?php include __DIR__ . '/../layouts/footer.php'; ?>
