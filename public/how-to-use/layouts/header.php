<?php
$lang = strpos($_SERVER['REQUEST_URI'], '/how-to-use/ar/') !== false ? 'ar' : 'en';
$dir = $lang === 'ar' ? 'rtl' : 'ltr';
$other_lang = $lang === 'en' ? 'ar' : 'en';
$other_lang_text = $lang === 'en' ? 'العربية' : 'English';
$title_text = $lang === 'ar' ? 'مركز المساعدة' : 'Help Center';

// Build current URL for language switcher
$current_url = $_SERVER['REQUEST_URI'];
$switch_url = str_replace('/how-to-use/' . $lang . '/', '/how-to-use/' . $other_lang . '/', $current_url);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title_text; ?></title>
    <style>
        :root {
            --primary: #4338ca;
            --bg: #f9fafb;
            --surface: #ffffff;
            --text: #374151;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --active-bg: #e0e7ff;
            --link: #2563eb;
            --sidebar-width: 260px;
            --header-height: 70px;
            --content-max-width: 1100px;
        }

        .dark {
            --primary: #818cf8;
            --bg: #111827;
            --surface: #1f2937;
            --text: #f3f4f6;
            --text-light: #9ca3af;
            --border: #374151;
            --active-bg: #374151;
            --link: #60a5fa;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: var(--bg);
            color: var(--text);
            transition: background-color 0.3s, color 0.3s;
            overflow: hidden; /* Prevent double scroll */
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR (FIXED) */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--surface);
            border-right: 1px solid var(--border);
            padding: 1.5rem;
            overflow-y: auto;
            transition: background-color 0.3s, border-color 0.3s;
            z-index: 1000;
        }

        [dir="rtl"] .sidebar {
            left: auto;
            right: 0;
            border-left: 1px solid var(--border);
            border-right: none;
        }

        .sidebar a { display: block; padding: 0.5rem 0.75rem; color: var(--text); text-decoration: none; border-radius: 0.375rem; margin-bottom: 0.25rem; font-size: 0.95rem; transition: background 0.2s, color 0.3s; }
        .sidebar a:hover { background: var(--bg); color: var(--primary); }
        .sidebar a.active { background: var(--active-bg); color: var(--primary); font-weight: 600; }
        .sidebar-section { margin-bottom: 1.5rem; }
        .sidebar-title { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: var(--text-light); margin-bottom: 0.5rem; padding-left: 0.75rem; }
        [dir="rtl"] .sidebar-title { padding-left: 0; padding-right: 0.75rem; }
        .sub-nav { margin-left: 0.5rem; border-left: 1px solid var(--border); padding-left: 0.5rem; }
        [dir="rtl"] .sub-nav { margin-left: 0; margin-right: 0.5rem; border-left: none; border-right: 1px solid var(--border); padding-left: 0; padding-right: 0.5rem; }

        /* MAIN AREA (OFFSET) */
        .main-wrapper {
            display: flex;
            flex-direction: column;
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
        }

        [dir="rtl"] .main-wrapper {
            margin-left: 0;
            margin-right: var(--sidebar-width);
        }

        /* HEADER */
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            height: var(--header-height);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.3s, border-color 0.3s;
            position: sticky;
            top: 0;
            z-index: 900;
            width: 100%;
        }
        .header-title { font-weight: 600; font-size: 1.25rem; color: var(--text); }
        .header-actions { display: flex; gap: 1rem; align-items: center; }

        /* CONTENT SCROLL AREA */
        .main-content {
            height: calc(100vh - var(--header-height));
            overflow-y: auto;
            overflow-x: hidden;
            width: 100%;
            display: flex;
            justify-content: center;
            padding: 2rem;
        }

        .content-wrapper {
            width: 100%;
            max-width: var(--content-max-width);
            display: flex;
            flex-direction: column;
        }

        /* BREADCRUMB */
        .breadcrumb {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding: 0 0.5rem; /* Aligns visually with the content card inside */
        }
        .breadcrumb a { color: var(--text-light); text-decoration: none; }
        .breadcrumb a:hover { color: var(--text); text-decoration: underline; }
        .breadcrumb span.separator { color: var(--border); }
        .breadcrumb span.current { color: var(--text); font-weight: 500; }

        /* PAGE CONTENT BLOCK */
        .page-content {
            background: var(--surface);
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s, box-shadow 0.3s;
            max-width: 100%;
        }

        h1 { font-size: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-top: 0; color: var(--text); }
        h2, h3, h4 { color: var(--text); }
        a { color: var(--link); text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* PREVENT OVERFLOW IN CONTENT */
        code { background: var(--bg); padding: 0.2em 0.4em; border-radius: 3px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.85em; border: 1px solid var(--border); color: var(--text); word-wrap: break-word; }
        pre { background: #1f2937; color: #f9fafb; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; border: 1px solid var(--border); max-width: 100%; }
        pre code { background: none; padding: 0; border: none; color: inherit; white-space: pre; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; max-width: 100%; overflow-x: auto; display: block; }
        th, td { border: 1px solid var(--border); padding: 0.75rem; text-align: left; }
        th { background: var(--bg); font-weight: 600; color: var(--text); }
        [dir="rtl"] th, [dir="rtl"] td { text-align: right; }

        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.375rem 0.75rem; border: 1px solid var(--border); border-radius: 0.375rem; text-decoration: none; color: var(--text); background: var(--surface); font-size: 0.875rem; font-weight: 500; transition: all 0.2s; cursor: pointer; }
        .btn:hover { background: var(--bg); }

        blockquote { border-left: 4px solid var(--border); margin: 0; padding-left: 1rem; color: var(--text-light); }
        [dir="rtl"] blockquote { border-left: none; border-right: 4px solid var(--border); padding-left: 0; padding-right: 1rem; }


        .footer {
            margin-top: 40px;
            padding: 20px 0;
            text-align: center;
            color: var(--text-light);
            font-size: 0.9rem;
            border-top: 1px solid var(--border);
        }
        [dir="rtl"] .footer {
            direction: rtl;
        }
        .footer a {
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            body { overflow-y: auto; }
            .sidebar { position: static; width: 100%; height: auto; border-right: none; border-bottom: 1px solid var(--border); padding: 1rem; z-index: auto; }
            [dir="rtl"] .sidebar { border-left: none; }
            .main-wrapper { margin-left: 0; margin-right: 0; width: 100%; }
            [dir="rtl"] .main-wrapper { margin-left: 0; margin-right: 0; }
            .main-content { height: auto; overflow: visible; padding: 1rem; }
            .page-content { border-radius: 0; box-shadow: none; padding: 1.5rem; }
        }
    </style>
    <script>
        // Check for dark mode preference
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main-wrapper">
        <header class="header">
            <div class="header-title"><?php echo $title_text; ?></div>
            <div class="header-actions">
                <button id="themeToggle" class="btn">
                    <span class="light-icon">🌙</span>
                    <span class="dark-icon" style="display: none;">☀️</span>
                </button>
                <a href="<?php echo $switch_url; ?>" class="btn"><?php echo $other_lang_text; ?></a>
            </div>
        </header>
        <main class="main-content">
            <div class="content-wrapper">
                <?php
            // Generate breadcrumbs
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $parts = explode('/', trim($path, '/'));

            // Remove 'how-to-use' and language from parts
            $start_idx = array_search('how-to-use', $parts);
            if ($start_idx !== false && isset($parts[$start_idx + 1])) {
                $lang_part = $parts[$start_idx + 1];
                $content_parts = array_slice($parts, $start_idx + 2);

                $home_text = $lang === 'ar' ? 'الرئيسية' : 'Home';
                echo '<div class="breadcrumb">';
                echo '<a href="/how-to-use/' . $lang . '/overview.php">' . $home_text . '</a>';

                $current_path = '/how-to-use/' . $lang;
                $separator = $lang === 'ar' ? '←' : '→';

                for ($i = 0; $i < count($content_parts); $i++) {
                    $part = $content_parts[$i];
                    $current_path .= '/' . $part;
                    echo '<span class="separator"> ' . $separator . ' </span>';

                    // Format the text
                    $text = str_replace(['.php', '-'], ['', ' '], $part);
                    $text = ucwords($text);

                    // Translate common terms if Arabic
                    if ($lang === 'ar') {
                        $translations = [
                            'Overview' => 'ملخص',
                            'Admins' => 'المشرفين',
                            'Admins Permissions' => 'صلاحيات المشرفين',
                            'Sessions' => 'الجلسات',
                            'Languages' => 'اللغات',
                            'Settings' => 'الإعدادات',
                            'App Settings' => 'إعدادات التطبيق',
                            'Content Documents' => 'مستندات المحتوى',
                            'Rbac' => 'التحكم في الوصول',
                            'Roles' => 'الأدوار',
                            'Permissions' => 'الصلاحيات',
                            'Translations' => 'الترجمات',
                            'Scopes' => 'النطاقات',
                            'Domains' => 'المجالات'
                        ];
                        if (isset($translations[$text])) {
                            $text = $translations[$text];
                        }
                    }

                    if ($i === count($content_parts) - 1) {
                        echo '<span class="current">' . htmlspecialchars($text) . '</span>';
                    } else {
                        // For intermediate directories, link to their overview.php if it exists, otherwise just text
                        // We'll just link to overview.php for simplicity since that's our structure
                        echo '<a href="' . $current_path . '/overview.php">' . htmlspecialchars($text) . '</a>';
                    }
                }
                echo '</div>';
            }
            ?>
                <div class="page-content">
