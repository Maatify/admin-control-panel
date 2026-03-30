<?php
$current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$lang = strpos($current_path, '/how-to-use/ar/') !== false ? 'ar' : 'en';
$base_url = '/how-to-use/' . $lang;
$sidebar_class = $lang === 'ar' ? 'sidebar rtl' : 'sidebar';

function isActive($path, $current) {
    return strpos($current, $path) !== false ? 'active' : '';
}

function tr($en_text, $lang) {
    if ($lang !== 'ar') return $en_text;
    $translations = [
        'Overview' => 'ملخص',
        'Admins' => 'المشرفين',
        'Admin Permissions' => 'صلاحيات المشرفين',
        'Sessions' => 'الجلسات',
        'Languages' => 'اللغات',
        'Settings' => 'الإعدادات',
        'App Settings' => 'إعدادات التطبيق',
        'Content Documents' => 'مستندات المحتوى',
        'RBAC' => 'التحكم في الوصول',
        'Roles' => 'الأدوار',
        'Permissions' => 'الصلاحيات',
        'Translations' => 'الترجمات',
        'Scopes' => 'النطاقات',
        'Domains' => 'المجالات'
    ];
    return isset($translations[$en_text]) ? $translations[$en_text] : $en_text;
}
?>
<aside class="<?php echo $sidebar_class; ?>">
    <nav>
        <div class="sidebar-section" style="margin-bottom: 0.5rem;">
            <a href="<?php echo $base_url; ?>/overview.php" class="<?php echo isActive('/overview.php', $current_path); ?>"><?php echo tr('Overview', $lang); ?></a>
        </div>
        <div class="sidebar-section" style="margin-bottom: 0.5rem;">
            <a href="<?php echo $base_url; ?>/admins.php" class="<?php echo isActive('/admins.php', $current_path); ?>"><?php echo tr('Admins', $lang); ?></a>
        </div>
        <div class="sidebar-section" style="margin-bottom: 0.5rem;">
            <a href="<?php echo $base_url; ?>/admins-permissions.php" class="<?php echo isActive('/admins-permissions.php', $current_path); ?>"><?php echo tr('Admin Permissions', $lang); ?></a>
        </div>
        <div class="sidebar-section" style="margin-bottom: 0.5rem;">
            <a href="<?php echo $base_url; ?>/sessions.php" class="<?php echo isActive('/sessions.php', $current_path); ?>"><?php echo tr('Sessions', $lang); ?></a>
        </div>
        <div class="sidebar-section" style="margin-bottom: 0.5rem;">
            <a href="<?php echo $base_url; ?>/languages.php" class="<?php echo isActive('/languages.php', $current_path); ?>"><?php echo tr('Languages', $lang); ?></a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-title"><?php echo tr('Settings', $lang); ?></div>
            <div class="sub-nav">
                <a href="<?php echo $base_url; ?>/settings/overview.php" class="<?php echo isActive('/settings/overview.php', $current_path); ?>"><?php echo tr('Overview', $lang); ?></a>
                <a href="<?php echo $base_url; ?>/settings/content-documents.php" class="<?php echo isActive('/settings/content-documents.php', $current_path); ?>"><?php echo tr('Content Documents', $lang); ?></a>
                <a href="<?php echo $base_url; ?>/settings/app-settings.php" class="<?php echo isActive('/settings/app-settings.php', $current_path); ?>"><?php echo tr('App Settings', $lang); ?></a>
            </div>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-title"><?php echo tr('RBAC', $lang); ?></div>
            <div class="sub-nav">
                <a href="<?php echo $base_url; ?>/rbac/overview.php" class="<?php echo isActive('/rbac/overview.php', $current_path); ?>"><?php echo tr('Overview', $lang); ?></a>
                <a href="<?php echo $base_url; ?>/rbac/roles.php" class="<?php echo isActive('/rbac/roles.php', $current_path); ?>"><?php echo tr('Roles', $lang); ?></a>
                <a href="<?php echo $base_url; ?>/rbac/permissions.php" class="<?php echo isActive('/rbac/permissions.php', $current_path); ?>"><?php echo tr('Permissions', $lang); ?></a>
            </div>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-title"><?php echo tr('Translations', $lang); ?></div>
            <div class="sub-nav">
                <a href="<?php echo $base_url; ?>/translations/overview.php" class="<?php echo isActive('/translations/overview.php', $current_path); ?>"><?php echo tr('Overview', $lang); ?></a>
                <a href="<?php echo $base_url; ?>/translations/scopes.php" class="<?php echo isActive('/translations/scopes.php', $current_path); ?>"><?php echo tr('Scopes', $lang); ?></a>
                <a href="<?php echo $base_url; ?>/translations/domains.php" class="<?php echo isActive('/translations/domains.php', $current_path); ?>"><?php echo tr('Domains', $lang); ?></a>
            </div>
        </div>

        <div class="sidebar-section" style="margin-bottom: 0.5rem;">
            <a href="<?php echo $base_url; ?>/translations.php" class="<?php echo isActive('/translations.php', $current_path); ?>"><?php echo tr('Translations', $lang); ?></a>
        </div>
    </nav>
</aside>
