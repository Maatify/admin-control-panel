/**
 * 🛠️ Exchange Rates Actions V2
 */

(function() {
    'use strict';

    console.log('🛠️ Rates Actions V2 Loading...');

    if (!window.AdminPageBridge || !window.RatesHelpersV2) {
        console.error('❌ Missing dependencies for rates-actions-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.RatesHelpersV2;
    const reloadRatesTable = function() {
        if (typeof window.reloadRatesTableV2 === 'function') {
            return window.reloadRatesTableV2();
        }
    };

    async function toggleStatus(rateId) {
        const btn = document.querySelector('.toggle-status-btn[data-rate-id="' + rateId + '"]');
        if (!btn) return;

        const isCurrentlyActive = btn.getAttribute('data-current-is-active') === '1' || btn.getAttribute('data-current-is-active') === 'true';
        const payload = {
            id: Bridge.normalizeInt(rateId, 0),
            is_active: !isCurrentlyActive
        };

        return Bridge.API.runMutation({
            operation: 'Toggle Rate Status',
            endpoint: 'exchange-rates/rates/set-active',
            method: 'POST',
            payload,
            successMessage: 'Rate status updated successfully.',
            reloadHandler: reloadRatesTable
        });
    }

    async function deleteRate(rateId) {
        return Bridge.API.runMutation({
            operation: 'Delete Rate',
            endpoint: 'exchange-rates/rates/delete',
            method: 'POST',
            payload: { id: Bridge.normalizeInt(rateId, 0) },
            confirmMessage: 'Are you sure you want to delete this exchange rate? This action cannot be undone.',
            successMessage: 'Exchange rate deleted successfully.',
            reloadHandler: reloadRatesTable
        });
    }

    Helpers.setupButtonHandler('.toggle-status-btn', async function(id) {
        await toggleStatus(id);
    });

    Helpers.setupButtonHandler('.delete-rate-btn', async function(id) {
        await deleteRate(id);
    });

    Helpers.setupButtonHandler('.view-history-btn', function(id, btn) {
        const link = btn.getAttribute('data-link');
        if (link) {
            window.location.href = link;
        } else {
            window.location.href = '/exchange-rates/rates/' + id + '/history';
        }
    });

    const createBtn = document.getElementById('btn-create-rate');
    if (createBtn) {
        createBtn.addEventListener('click', function(event) {
            event.preventDefault();
            if (window.RatesModalsV2 && typeof window.RatesModalsV2.openCreateRateModal === 'function') {
                window.RatesModalsV2.openCreateRateModal();
            }
        });
    }

    window.RatesActionsV2 = {
        toggleStatus,
        deleteRate
    };

    console.log('✅ Rates Actions V2 loaded');
})();
