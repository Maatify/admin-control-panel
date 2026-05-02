/**
 * 🛠️ Exchange Rate Providers Modals V2
 */

(function() {
    'use strict';

    console.log('🛠️ Providers Modals V2 Loading...');

    if (!window.AdminPageBridge || !window.ProvidersHelpersV2) {
        console.error('❌ Missing dependencies for providers-modals-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.ProvidersHelpersV2;
    const reloadProvidersTable = function() {
        if (typeof window.reloadProvidersTableV2 === 'function') {
            return window.reloadProvidersTableV2();
        }
    };

    Helpers.setupModalCloseHandlers();

    function collectProviderPayload() {
        return Bridge.Form.collect({
            id: { selector: '#provider-id', type: 'int' },
            code: '#provider-code',
            name: '#provider-name',
            description: '#provider-description'
        }, { includeEmpty: true });
    }

    function bindSaveAction(handler) {
        const saveBtn = document.getElementById('btn-save-provider');
        if (!saveBtn || !saveBtn.parentNode) return;

        const newBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newBtn, saveBtn);
        newBtn.addEventListener('click', handler);
    }

    function openCreateProviderModal() {
        Helpers.clearFormInputs('provider-form');
        const idEl = document.getElementById('provider-id');
        const codeEl = document.getElementById('provider-code');
        const titleEl = document.getElementById('provider-modal-title');

        if (idEl) idEl.value = '';
        if (codeEl) codeEl.disabled = false;
        if (titleEl) titleEl.textContent = 'Create New Provider';

        bindSaveAction(async function() {
            const payload = Bridge.Form.omitEmpty(collectProviderPayload());
            delete payload.id;

            await Bridge.API.runMutation({
                operation: 'Create Provider',
                endpoint: 'exchange-rates/providers/create',
                method: 'POST',
                payload,
                successMessage: 'Provider created successfully.',
                modal: '#provider-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadProvidersTable
            });
        });

        Bridge.Modal.open('#provider-modal');
    }

    function openEditProviderModal(id, btn) {
        const titleEl = document.getElementById('provider-modal-title');
        if (titleEl) titleEl.textContent = 'Edit Provider';

        const idEl = document.getElementById('provider-id');
        const codeEl = document.getElementById('provider-code');
        const nameEl = document.getElementById('provider-name');
        const descEl = document.getElementById('provider-description');

        if (idEl) idEl.value = id;
        if (codeEl) {
            codeEl.value = btn.getAttribute('data-current-code') || '';
            codeEl.disabled = true; // Code is immutable after creation
        }
        if (nameEl) nameEl.value = btn.getAttribute('data-current-name') || '';
        if (descEl) descEl.value = btn.getAttribute('data-current-description') || '';

        bindSaveAction(async function() {
            const payload = collectProviderPayload();

            await Bridge.API.runMutation({
                operation: 'Update Provider',
                endpoint: 'exchange-rates/providers/update',
                method: 'POST',
                payload,
                successMessage: 'Provider updated successfully.',
                modal: '#provider-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadProvidersTable
            });
        });

        Bridge.Modal.open('#provider-modal');
    }

    function openSortModal(id, btn) {
        const idEl = document.getElementById('sort-provider-id');
        const valueEl = document.getElementById('sort-new-value');
        if (idEl) idEl.value = id;
        if (valueEl) valueEl.value = btn.getAttribute('data-current-sort') || '';
        Bridge.Modal.open('#sort-modal');
    }

    const sortSaveBtn = document.getElementById('btn-save-sort');
    if (sortSaveBtn) {
        sortSaveBtn.addEventListener('click', async function() {
            const payload = Bridge.Form.collect({
                id: { selector: '#sort-provider-id', type: 'int' },
                display_order: { selector: '#sort-new-value', type: 'int' }
            });

            await Bridge.API.runMutation({
                operation: 'Update Sort Order',
                endpoint: 'exchange-rates/providers/update-sort',
                method: 'POST',
                payload,
                successMessage: 'Sort order updated successfully.',
                modal: '#sort-modal',
                reloadHandler: reloadProvidersTable
            });
        });
    }

    Helpers.setupButtonHandler('.edit-provider-btn', function(id, btn) {
        openEditProviderModal(id, btn);
    });

    Helpers.setupButtonHandler('.update-sort-btn', function(id, btn) {
        openSortModal(id, btn);
    });

    window.ProvidersModalsV2 = {
        openCreateProviderModal,
        openEditProviderModal,
        openSortModal
    };

    window.openCreateProviderModalV2 = openCreateProviderModal;

    console.log('✅ Providers Modals V2 loaded');
})();
