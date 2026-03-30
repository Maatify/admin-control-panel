<?php
// Simple entry point for the Help Center
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center | مركز المساعدة</title>
    <style>
        :root {
            --primary: #4338ca;
            --bg: #f9fafb;
            --surface: #ffffff;
            --text: #374151;
            --text-light: #6b7280;
            --border: #e5e7eb;
        }

        .dark {
            --primary: #818cf8;
            --bg: #111827;
            --surface: #1f2937;
            --text: #f3f4f6;
            --text-light: #9ca3af;
            --border: #374151;
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: var(--surface);
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            max-width: 500px;
            width: 90%;
            text-align: center;
            border: 1px solid var(--border);
        }

        h1 {
            font-size: 1.75rem;
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .subtitle {
            color: var(--text-light);
            margin-bottom: 2.5rem;
            font-size: 1.1rem;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 1rem 1.5rem;
            background: var(--surface);
            color: var(--text);
            text-decoration: none;
            border-radius: 0.5rem;
            font-size: 1.125rem;
            font-weight: 500;
            border: 2px solid var(--border);
            transition: all 0.2s;
            cursor: pointer;
        }

        .btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--bg);
        }

        .theme-toggle {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text);
            padding: 0.5rem;
            border-radius: 50%;
        }

        .footer {
            margin-top: 3rem;
            text-align: center;
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .footer a {
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .footer-ar {
            direction: rtl;
            margin-top: 0.5rem;
        }
    </style>
    <script>
        // Check for dark mode preference
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body>
    <button id="themeToggle" class="theme-toggle" aria-label="Toggle dark mode">
        <span class="light-icon">🌙</span>
        <span class="dark-icon" style="display: none;">☀️</span>
    </button>

    <div class="container">
        <h1>Help Center</h1>
        <div class="subtitle">مركز المساعدة</div>

        <div class="btn-group">
            <a href="/how-to-use/en/index.php" class="btn">English</a>
            <a href="/how-to-use/ar/index.php" class="btn" dir="rtl">العربية</a>
        </div>
    </div>

    <footer class="footer">
        <div>&copy; <?php echo date('Y'); ?> <a href="https://maatify.dev" target="_blank">Maatify</a>. All rights reserved.</div>
        <div class="footer-ar">جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?> <a href="https://maatify.dev" target="_blank" dir="ltr" style="display:inline-block;">Maatify</a></div>
    </footer>

    <script>
        const themeToggleBtn = document.getElementById('themeToggle');
        const lightIcon = document.querySelector('.light-icon');
        const darkIcon = document.querySelector('.dark-icon');

        function updateIcons() {
            if (document.documentElement.classList.contains('dark')) {
                lightIcon.style.display = 'none';
                darkIcon.style.display = 'inline';
            } else {
                lightIcon.style.display = 'inline';
                darkIcon.style.display = 'none';
            }
        }

        updateIcons();

        themeToggleBtn.addEventListener('click', function() {
            document.documentElement.classList.toggle('dark');

            if (document.documentElement.classList.contains('dark')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }

            updateIcons();
        });
    </script>
</body>
</html>
