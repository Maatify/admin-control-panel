/**
 * 🛠️ Countries Actions V2
 */

(function() {
    'use strict';

    console.log('🛠️ Countries Actions V2 Loading...');

    if (!window.AdminPageBridge || !window.CountriesHelpersV2) {
        console.error('❌ Missing dependencies for countries-actions-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.CountriesHelpersV2;
    const reloadCountriesTableV2 = function() {
        if (typeof window.reloadCountriesTableV2 === 'function') {
            return window.reloadCountriesTableV2();
        }
    };

    async function toggleStatus(countryId) {
        const btn = document.querySelector('.toggle-status-btn[data-country-id="' + countryId + '"]');
        if (!btn) return;

        const isCurrentlyActive = btn.getAttribute('data-current-is-active') === '1' || btn.getAttribute('data-current-is-active') === 'true';
        const payload = {
            id: Bridge.normalizeInt(countryId, 0),
            is_active: !isCurrentlyActive
        };

        return Bridge.API.runMutation({
            operation: 'Toggle Country Status',
            endpoint: 'geo/countries/set-active',
            method: 'POST',
            payload,
            successMessage: 'Country status updated successfully',
            reloadHandler: reloadCountriesTableV2
        });
    }

    Helpers.setupButtonHandler('.toggle-status-btn', async function(id) {
        await toggleStatus(id);
    });

    window.CountriesActionsV2 = {
        toggleStatus
    };

    console.log('✅ Geo Actions V2 loaded');
})();
