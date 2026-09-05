/**
 * Role Details – Tabs Module
 * Manages tab switching between Permissions and Admins.
 * Triggers the first data-load for whichever tab is active on mount.
 */

(function () {
    'use strict';

    console.log('📑 Role Details Tabs – Initializing');

    const capabilities = window.roleDetailsCapabilities || {};
    const tabs    = document.querySelectorAll('.role-tab-btn');
    const panels  = document.querySelectorAll('.role-tab-content');

    // Track which tabs have been loaded at least once (lazy load)
    const loaded = new Set();

    // ====================================================================
    // Tab Switch
    // ====================================================================
    function switchTab(targetTab) {
        console.log('🔀 Switching to tab:', targetTab);

        tabs.forEach(btn => {
            const isActive = btn.dataset.tab === targetTab;
            btn.classList.toggle('active',            isActive);
            btn.classList.toggle('border-blue-600',   isActive);
            btn.classList.toggle('text-blue-600',     isActive);
            btn.classList.toggle('border-transparent', !isActive);
            btn.classList.toggle('text-gray-500',     !isActive);
        });

        panels.forEach(panel => {
            const isActive = panel.id === `tab-${targetTab}`;
            panel.classList.toggle('hidden', !isActive);
        });

        // First-time load for this tab
        if (!loaded.has(targetTab)) {
            loaded.add(targetTab);
            fireTabLoaded(targetTab);
        }
    }

    // ====================================================================
    // Custom Event – lets child modules know a tab just became visible
    // ====================================================================
    function fireTabLoaded(tabName) {
        console.log('📢 Firing tabLoaded event for:', tabName);
        document.dispatchEvent(new CustomEvent('roleTabLoaded', {
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
    // Initial Activation – fire the event for the tab that starts visible
    // ====================================================================
    const initialTab = document.querySelector('.role-tab-btn.active');
    if (initialTab) {
        loaded.add(initialTab.dataset.tab);
        // Small delay so child modules have finished their IIFE setup
        setTimeout(() => fireTabLoaded(initialTab.dataset.tab), 50);
        console.log('✅ Initial active tab:', initialTab.dataset.tab);
    }

    // ====================================================================
    // Public API (for programmatic tab switching from other modules)
    // ====================================================================
    window.RoleDetailsTabs = { switchTab };

    console.log('✅ Role Details Tabs – Ready');
})();
