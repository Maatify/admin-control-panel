/**
 * Admin Permissions — Tabs Module
 * Manages tab switching between Effective, Direct, and Roles.
 *
 * Key design: data_table.js uses document-scoped getElementById("pagination")
 * and querySelector(".form-group-select"). If two tabs have rendered tables
 * in the DOM simultaneously, these resolve to the wrong (first) element.
 * Solution: on hide, clear each tab's table container innerHTML so only the
 * active tab's table elements exist. Fire adminPermTabLoaded on EVERY switch
 * (not just first) so child modules re-render after their container was cleared.
 */

(function () {
    'use strict';

    console.log('🔑 Admin Permissions Tabs — Initializing');

    const tabs   = document.querySelectorAll('.admin-perms-tab-btn');
    const panels = document.querySelectorAll('.admin-perms-tab-content');

    // ====================================================================
    // Tab Switch
    // ====================================================================
    // Map tab name → the table container id inside that tab panel.
    // Clearing these on hide removes duplicate #pagination / .form-group-select
    // that would confuse data_table.js's document-scoped getElementById queries.
    const tableContainerIds = {
        effective: 'effective-table-container',
        direct:    'direct-table-container',
        roles:     'roles-table-container'
    };

    function switchTab(targetTab) {
        console.log('🔀 Switching to tab:', targetTab);

        tabs.forEach(btn => {
            const isActive = btn.dataset.tab === targetTab;
            btn.classList.toggle('active',             isActive);
            btn.classList.toggle('border-blue-600',    isActive);
            btn.classList.toggle('text-blue-600',      isActive);
            btn.classList.toggle('border-transparent', !isActive);
            btn.classList.toggle('text-gray-500',      !isActive);
        });

        panels.forEach(panel => {
            const isActive = panel.id === `tab-${targetTab}`;
            panel.classList.toggle('hidden', !isActive);

            // When hiding a panel, clear its table container so its rendered
            // #pagination and .form-group-select don't stay in the DOM and
            // collide with the newly-active tab's copies.
            if (!isActive) {
                const tabName   = panel.id.replace('tab-', '');
                const containerId = tableContainerIds[tabName];
                if (containerId) {
                    const tc = document.getElementById(containerId);
                    if (tc) tc.innerHTML = '';
                }
            }
        });

        // Fire every time (not just first load) — the table was cleared on hide,
        // so the child module must re-render on every activation.
        fireTabLoaded(targetTab);
    }

    // ====================================================================
    // Custom Event — lets child modules know a tab just became visible
    // ====================================================================
    function fireTabLoaded(tabName) {
        console.log('📢 Firing adminPermTabLoaded event for:', tabName);
        document.dispatchEvent(new CustomEvent('adminPermTabLoaded', {
            detail: { tab: tabName }
        }));
    }

    // ====================================================================
    // Event Listeners
    // ====================================================================
    tabs.forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });

    // ====================================================================
    // Initial Activation — fire the event for the tab that starts visible
    // ====================================================================
    const initialTab = document.querySelector('.admin-perms-tab-btn.active');
    if (initialTab) {
        // Small delay so child modules have finished their IIFE setup
        setTimeout(() => fireTabLoaded(initialTab.dataset.tab), 50);
        console.log('✅ Initial active tab:', initialTab.dataset.tab);
    }

    // ====================================================================
    // Public API (for programmatic tab switching from other modules)
    // ====================================================================
    window.AdminPermissionsTabs = { switchTab };

    console.log('✅ Admin Permissions Tabs — Ready');
})();
