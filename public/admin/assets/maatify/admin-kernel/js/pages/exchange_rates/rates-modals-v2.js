/**
 * 🛠️ Exchange Rates Modals V2
 */

(function() {
    'use strict';

    console.log('🛠️ Rates Modals V2 Loading...');

    if (!window.AdminPageBridge || !window.RatesHelpersV2) {
        console.error('❌ Missing dependencies for rates-modals-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.RatesHelpersV2;
    const reloadRatesTable = function() {
        if (typeof window.reloadRatesTableV2 === 'function') {
            return window.reloadRatesTableV2();
        }
    };

    Helpers.setupModalCloseHandlers();

    function collectRatePayload() {
        return Bridge.Form.collect({
            id: { selector: '#rate-id', type: 'int' },
            provider_id: { selector: '#rate-provider', type: 'int' },
            base_currency_code: '#rate-base',
            target_currency_code: '#rate-target',
            rate: '#rate-value'
        }, { includeEmpty: true });
    }

    function bindSaveAction(handler) {
        const saveBtn = document.getElementById('btn-save-rate');
        if (!saveBtn || !saveBtn.parentNode) return;

        const newBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newBtn, saveBtn);
        newBtn.addEventListener('click', handler);
    }

    async function loadProvidersDropdown(selectedId) {
        const selectEl = document.getElementById('rate-provider');
        if (!selectEl) return;

        const result = await Bridge.API.execute({
            endpoint: 'exchange-rates/providers/dropdown',
            payload: {},
            operation: 'Load Providers Dropdown',
            method: 'POST',
            showErrorMessage: false
        });

        if (result.success && result.data && result.data.data) {
            selectEl.innerHTML = result.data.data.map(function(p) {
                return '<option value="' + p.id + '">' + Bridge.Text.escapeHtml(p.name) + ' (' + Bridge.Text.escapeHtml(p.code) + ')</option>';
            }).join('');

            if (selectedId) {
                selectEl.value = selectedId;
            }
        } else {
            selectEl.innerHTML = '<option value="">— No providers —</option>';
        }
    }

    function openCreateRateModal() {
        Helpers.clearFormInputs('rate-form');
        const idEl = document.getElementById('rate-id');
        const providerEl = document.getElementById('rate-provider');
        const baseEl = document.getElementById('rate-base');
        const targetEl = document.getElementById('rate-target');
        const titleEl = document.getElementById('rate-modal-title');

        if (idEl) idEl.value = '';
        if (providerEl) providerEl.disabled = false;
        if (baseEl) baseEl.disabled = false;
        if (targetEl) targetEl.disabled = false;
        if (titleEl) titleEl.textContent = 'Create New Exchange Rate';

        loadProvidersDropdown();

        bindSaveAction(async function() {
            const payload = collectRatePayload();
            delete payload.id;
            if (payload.base_currency_code) payload.base_currency_code = payload.base_currency_code.toUpperCase();
            if (payload.target_currency_code) payload.target_currency_code = payload.target_currency_code.toUpperCase();

            await Bridge.API.runMutation({
                operation: 'Create Exchange Rate',
                endpoint: 'exchange-rates/rates/create',
                method: 'POST',
                payload,
                successMessage: 'Exchange rate created successfully.',
                modal: '#rate-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadRatesTable
            });
        });

        Bridge.Modal.open('#rate-modal');
    }

    function openEditRateModal(id, btn) {
        const titleEl = document.getElementById('rate-modal-title');
        if (titleEl) titleEl.textContent = 'Edit Exchange Rate';

        const idEl = document.getElementById('rate-id');
        const providerEl = document.getElementById('rate-provider');
        const baseEl = document.getElementById('rate-base');
        const targetEl = document.getElementById('rate-target');
        const rateEl = document.getElementById('rate-value');

        if (idEl) idEl.value = id;

        // Provider, base, target are immutable after creation
        const currentProviderId = btn.getAttribute('data-current-provider-id') || '';
        loadProvidersDropdown(currentProviderId);
        if (providerEl) providerEl.disabled = true;

        if (baseEl) {
            baseEl.value = btn.getAttribute('data-current-base') || '';
            baseEl.disabled = true;
        }
        if (targetEl) {
            targetEl.value = btn.getAttribute('data-current-target') || '';
            targetEl.disabled = true;
        }
        if (rateEl) rateEl.value = btn.getAttribute('data-current-rate') || '';

        bindSaveAction(async function() {
            const payload = {
                id: Bridge.normalizeInt(id, 0),
                rate: Bridge.DOM.value('#rate-value', '')
            };

            await Bridge.API.runMutation({
                operation: 'Update Exchange Rate',
                endpoint: 'exchange-rates/rates/update',
                method: 'POST',
                payload,
                successMessage: 'Exchange rate updated successfully.',
                modal: '#rate-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadRatesTable
            });
        });

        Bridge.Modal.open('#rate-modal');
    }

    function openSortModal(id, btn) {
        const idEl = document.getElementById('sort-rate-id');
        const valueEl = document.getElementById('sort-new-value');
        if (idEl) idEl.value = id;
        if (valueEl) valueEl.value = btn.getAttribute('data-current-sort') || '';
        Bridge.Modal.open('#sort-modal');
    }

    const sortSaveBtn = document.getElementById('btn-save-sort');
    if (sortSaveBtn) {
        sortSaveBtn.addEventListener('click', async function() {
            const payload = Bridge.Form.collect({
                id: { selector: '#sort-rate-id', type: 'int' },
                display_order: { selector: '#sort-new-value', type: 'int' }
            });

            await Bridge.API.runMutation({
                operation: 'Update Rate Sort Order',
                endpoint: 'exchange-rates/rates/update-sort',
                method: 'POST',
                payload,
                successMessage: 'Sort order updated successfully.',
                modal: '#sort-modal',
                reloadHandler: reloadRatesTable
            });
        });
    }

    Helpers.setupButtonHandler('.edit-rate-btn', function(id, btn) {
        openEditRateModal(id, btn);
    });

    Helpers.setupButtonHandler('.update-sort-btn', function(id, btn) {
        openSortModal(id, btn);
    });

    window.RatesModalsV2 = {
        openCreateRateModal,
        openEditRateModal,
        openSortModal
    };

    window.openCreateRateModalV2 = openCreateRateModal;

    console.log('✅ Rates Modals V2 loaded');
})();
