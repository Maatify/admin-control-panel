/**
 * 🛠️ Exchange Rate Providers Actions V2
 */

(function() {
    'use strict';

    console.log('🛠️ Providers Actions V2 Loading...');

    if (!window.AdminPageBridge || !window.ProvidersHelpersV2) {
        console.error('❌ Missing dependencies for providers-actions-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.ProvidersHelpersV2;
    const reloadProvidersTable = function() {
        if (typeof window.reloadProvidersTableV2 === 'function') {
            return window.reloadProvidersTableV2();
        }
    };

    async function toggleStatus(providerId) {
        const btn = document.querySelector('.toggle-status-btn[data-provider-id="' + providerId + '"]');
        if (!btn) return;

        const isCurrentlyActive = btn.getAttribute('data-current-is-active') === '1' || btn.getAttribute('data-current-is-active') === 'true';
        const payload = {
            id: Bridge.normalizeInt(providerId, 0),
            is_active: !isCurrentlyActive
        };

        return Bridge.API.runMutation({
            operation: 'Toggle Provider Status',
            endpoint: 'exchange-rates/providers/set-active',
            method: 'POST',
            payload,
            successMessage: 'Provider status updated successfully.',
            reloadHandler: reloadProvidersTable
        });
    }

    async function deleteProvider(providerId) {
        return Bridge.API.runMutation({
            operation: 'Delete Provider',
            endpoint: 'exchange-rates/providers/delete',
            method: 'POST',
            payload: { id: Bridge.normalizeInt(providerId, 0) },
            confirmMessage: 'Are you sure you want to delete this provider? This action cannot be undone.',
            successMessage: 'Provider deleted successfully.',
            reloadHandler: reloadProvidersTable
        });
    }

    Helpers.setupButtonHandler('.toggle-status-btn', async function(id) {
        await toggleStatus(id);
    });

    Helpers.setupButtonHandler('.delete-provider-btn', async function(id) {
        await deleteProvider(id);
    });

    const createBtn = document.getElementById('btn-create-provider');
    if (createBtn) {
        createBtn.addEventListener('click', function(event) {
            event.preventDefault();
            if (window.ProvidersModalsV2 && typeof window.ProvidersModalsV2.openCreateProviderModal === 'function') {
                window.ProvidersModalsV2.openCreateProviderModal();
            }
        });
    }

    window.ProvidersActionsV2 = {
        toggleStatus,
        deleteProvider
    };

    console.log('✅ Providers Actions V2 loaded');
})();
