/**
 * 🛠️ Currencies Management - Actions Module
 * ===================================================
 * Handles quick actions like toggling status
 */

(function() {
    'use strict';

    console.log('🛠️ Currencies Actions Module Loading...');

    // Dependency check
    if (!window.CurrenciesHelpers) {
        console.error('❌ CurrenciesHelpers not found!');
        return;
    }

    const { setupButtonHandler } = window.CurrenciesHelpers;

    // ========================================================================
    // Status Toggle
    // ========================================================================

    async function toggleStatus(currencyId) {
        // Toggle uses the API mapped to currencies.set_active.api
        // We first need to check the current state from the DOM because the payload expects `is_active` boolean
        const btn = document.querySelector(`.toggle-status-btn[data-currency-id="${currencyId}"]`);
        if (!btn) return;

        // Determine current status explicitly using data attributes
        const isCurrentlyActive = btn.getAttribute('data-current-is-active') === '1' || btn.getAttribute('data-current-is-active') === 'true';
        const newStatus = !isCurrentlyActive;

        const payload = {
            id: parseInt(currencyId, 10),
            is_active: newStatus
        };

        const result = await ApiHandler.call('currencies/set-active', payload, 'Toggle Currency Status');

        if (result.success) {
            ApiHandler.showAlert('success', 'Currency status updated successfully');
            if (typeof window.reloadCurrenciesTable === 'function') {
                window.reloadCurrenciesTable();
            }
        }
    }

    // ========================================================================
    // Event Delegation Registration
    // ========================================================================

    setupButtonHandler('.toggle-status-btn', async (id) => {
        await toggleStatus(id);
    });

    console.log('✅ Currencies Actions Module loaded');

})();
