/**
 * 🛠️ Cities Actions V2
 */

(function() {
    'use strict';

    console.log('🛠️ Cities Actions V2 Loading...');

    if (!window.AdminPageBridge || !window.CitiesHelpersV2) {
        console.error('❌ Missing dependencies for cities-actions-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.CitiesHelpersV2;
    const reloadCitiesTableV2 = function() {
        if (typeof window.reloadCitiesTableV2 === 'function') {
            return window.reloadCitiesTableV2();
        }
    };

    async function toggleStatus(cityId) {
        const btn = document.querySelector('.toggle-status-btn[data-city-id="' + cityId + '"]');
        if (!btn) return;

        const isCurrentlyActive = btn.getAttribute('data-current-is-active') === '1' || btn.getAttribute('data-current-is-active') === 'true';
        const payload = {
            id: Bridge.normalizeInt(cityId, 0),
            is_active: !isCurrentlyActive
        };

        return Bridge.API.runMutation({
            operation: 'Toggle City Status',
            endpoint: 'geo/cities/set-active',
            method: 'POST',
            payload,
            successMessage: 'City status updated successfully',
            reloadHandler: reloadCitiesTableV2
        });
    }

    Helpers.setupButtonHandler('.toggle-status-btn', async function(id) {
        await toggleStatus(id);
    });

    window.CitiesActionsV2 = {
        toggleStatus
    };

    console.log('✅ Cities Actions V2 loaded');
})();
