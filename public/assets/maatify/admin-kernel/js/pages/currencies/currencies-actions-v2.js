/**
 * 🛠️ Currencies Actions V2
 */

(function() {
    'use strict';

    console.log('🛠️ Currencies Actions V2 Loading...');

    if (!window.AdminPageBridge || !window.CurrenciesHelpersV2) {
        console.error('❌ Missing dependencies for currencies-actions-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.CurrenciesHelpersV2;

    async function toggleStatus(currencyId) {
        const btn = document.querySelector('.toggle-status-btn[data-currency-id="' + currencyId + '"]');
        if (!btn) return;

        const isCurrentlyActive = btn.getAttribute('data-current-is-active') === '1' || btn.getAttribute('data-current-is-active') === 'true';
        const payload = {
            id: Bridge.normalizeInt(currencyId, 0),
            is_active: !isCurrentlyActive
        };

        return Bridge.API.runMutation({
            operation: 'Toggle Currency Status',
            endpoint: 'currencies/set-active',
            method: 'POST',
            payload,
            successMessage: 'Currency status updated successfully',
            reloadHandler: window.reloadCurrenciesTable || 'reloadCurrenciesTable'
        });
    }

    Helpers.setupButtonHandler('.toggle-status-btn', async function(id) {
        await toggleStatus(id);
    });

    window.CurrenciesActionsV2 = {
        toggleStatus
    };

    console.log('✅ Currencies Actions V2 loaded');
})();
