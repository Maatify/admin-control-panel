/**
 * 🛠️ Categories Actions V2
 */

(function() {
    'use strict';

    console.log('🛠️ Categories Actions V2 Loading...');

    if (!window.AdminPageBridge || !window.CategoriesHelpersV2) {
        console.error('❌ Missing dependencies for categories-actions-v2');
        return;
    }

    const Bridge  = window.AdminPageBridge;
    const Helpers = window.CategoriesHelpersV2;
    const reload  = function() {
        if (typeof window.reloadCategoriesTableV2 === 'function') {
            return window.reloadCategoriesTableV2();
        }
    };

    async function toggleStatus(categoryId) {
        const btn = document.querySelector('.toggle-status-btn[data-category-id="' + categoryId + '"]');
        if (!btn) return;

        const isCurrentlyActive =
            btn.getAttribute('data-current-is-active') === '1' ||
            btn.getAttribute('data-current-is-active') === 'true';

        const payload = {
            id:        Bridge.normalizeInt(categoryId, 0),
            is_active: !isCurrentlyActive
        };

        return Bridge.API.runMutation({
            operation: 'Toggle Category Status',
            endpoint: 'categories/set-active',
            method: 'POST',
            payload,
            successMessage: 'Category status updated successfully.',
            reloadHandler: reload
        });
    }

    Helpers.setupButtonHandler('.toggle-status-btn', async function(id) {
        await toggleStatus(id);
    });

    window.CategoriesActionsV2 = { toggleStatus };

    console.log('✅ Categories Actions V2 loaded');
})();

