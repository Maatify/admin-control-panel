/**
 * 🛠️ Geo Actions V2
 */

(function() {
    'use strict';

    console.log('🛠️ Geo Actions V2 Loading...');

    if (!window.AdminPageBridge || !window.GeoHelpersV2) {
        console.error('❌ Missing dependencies for geo-actions-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.GeoHelpersV2;
    const reloadGeoTableV2 = function() {
        if (typeof window.reloadGeoTableV2 === 'function') {
            return window.reloadGeoTableV2();
        }
    };

    async function toggleStatus(geoId) {
        const btn = document.querySelector('.toggle-status-btn[data-geo-id="' + geoId + '"]');
        if (!btn) return;

        const isCurrentlyActive = btn.getAttribute('data-current-is-active') === '1' || btn.getAttribute('data-current-is-active') === 'true';
        const payload = {
            id: Bridge.normalizeInt(geoId, 0),
            is_active: !isCurrentlyActive
        };

        return Bridge.API.runMutation({
            operation: 'Toggle Geo Status',
            endpoint: 'geo/countries/set-active',
            method: 'POST',
            payload,
            successMessage: 'Geo status updated successfully',
            reloadHandler: reloadGeoTableV2
        });
    }

    Helpers.setupButtonHandler('.toggle-status-btn', async function(id) {
        await toggleStatus(id);
    });

    window.GeoActionsV2 = {
        toggleStatus
    };

    console.log('✅ Geo Actions V2 loaded');
})();
