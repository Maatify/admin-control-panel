                        </div>
            </div>
        </main>
    </div>

    <script>
        // Dark mode toggle functionality
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

        // Initial icon state
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
