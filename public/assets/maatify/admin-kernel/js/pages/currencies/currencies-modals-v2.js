/**
 * 🛠️ Currencies Modals V2
 */

(function() {
    'use strict';

    console.log('🛠️ Currencies Modals V2 Loading...');

    if (!window.AdminPageBridge || !window.CurrenciesHelpersV2) {
        console.error('❌ Missing dependencies for currencies-modals-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.CurrenciesHelpersV2;
    const reloadCurrenciesTableV2 = function() {
        if (typeof window.reloadCurrenciesTableV2 === 'function') {
            return window.reloadCurrenciesTableV2();
        }
    };

    Helpers.setupModalCloseHandlers();

    function collectCurrencyPayload() {
        return Bridge.Form.collect({
            id: { selector: '#currency-id', type: 'int' },
            code: '#currency-code',
            name: '#currency-name',
            symbol: '#currency-symbol',
            is_active: { selector: '#currency-active', type: 'checked' },
            display_order: { selector: '#currency-sort', type: 'int', default: 0 }
        }, { includeEmpty: true });
    }

    function bindSaveAction(handler) {
        const saveBtn = document.getElementById('btn-save-currency');
        if (!saveBtn || !saveBtn.parentNode) return;

        const newBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newBtn, saveBtn);
        newBtn.addEventListener('click', handler);
    }

    function openCreateCurrencyModal() {
        Helpers.clearFormInputs('currency-form');
        const idEl = document.getElementById('currency-id');
        const activeEl = document.getElementById('currency-active');
        const sortEl = document.getElementById('currency-sort');
        const titleEl = document.getElementById('currency-modal-title');

        if (idEl) idEl.value = '';
        if (activeEl) activeEl.checked = true;
        if (sortEl) sortEl.value = '0';
        if (titleEl) titleEl.textContent = 'Create New Currency';

        bindSaveAction(async function() {
            const payload = Bridge.Form.omitEmpty(collectCurrencyPayload());
            delete payload.id;

            await Bridge.API.runMutation({
                operation: 'Create Currency',
                endpoint: 'currencies/create',
                method: 'POST',
                payload,
                successMessage: 'Currency created successfully.',
                modal: '#currency-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadCurrenciesTableV2
            });
        });

        Bridge.Modal.open('#currency-modal');
    }

    function openEditCurrencyModal(id, btn) {
        const titleEl = document.getElementById('currency-modal-title');
        if (titleEl) titleEl.textContent = 'Edit Currency';

        const idEl = document.getElementById('currency-id');
        const codeEl = document.getElementById('currency-code');
        const nameEl = document.getElementById('currency-name');
        const symbolEl = document.getElementById('currency-symbol');
        const sortEl = document.getElementById('currency-sort');
        const activeEl = document.getElementById('currency-active');

        if (idEl) idEl.value = id;
        if (codeEl) codeEl.value = btn.getAttribute('data-current-code') || '';
        if (nameEl) nameEl.value = btn.getAttribute('data-current-name') || '';
        if (symbolEl) symbolEl.value = btn.getAttribute('data-current-symbol') || '';
        if (sortEl) sortEl.value = btn.getAttribute('data-current-display-order') || '1';
        if (activeEl) activeEl.checked = btn.getAttribute('data-current-is-active') === '1';

        bindSaveAction(async function() {
            const payload = collectCurrencyPayload();
            if (payload.display_order === 0) payload.display_order = 1;

            await Bridge.API.runMutation({
                operation: 'Update Currency',
                endpoint: 'currencies/update',
                method: 'POST',
                payload,
                successMessage: 'Currency updated successfully.',
                modal: '#currency-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadCurrenciesTableV2
            });
        });

        Bridge.Modal.open('#currency-modal');
    }

    function openSortModal(id, btn) {
        const idEl = document.getElementById('sort-currency-id');
        const valueEl = document.getElementById('sort-new-value');
        if (idEl) idEl.value = id;
        if (valueEl) valueEl.value = btn.getAttribute('data-current-sort') || '';
        Bridge.Modal.open('#sort-modal');
    }

    const sortSaveBtn = document.getElementById('btn-save-sort');
    if (sortSaveBtn) {
        sortSaveBtn.addEventListener('click', async function() {
            const payload = Bridge.Form.collect({
                id: { selector: '#sort-currency-id', type: 'int' },
                display_order: { selector: '#sort-new-value', type: 'int' }
            });

            await Bridge.API.runMutation({
                operation: 'Update Sort Order',
                endpoint: 'currencies/update-sort',
                method: 'POST',
                payload,
                successMessage: 'Sort order updated successfully.',
                modal: '#sort-modal',
                reloadHandler: reloadCurrenciesTableV2
            });
        });
    }

    Helpers.setupButtonHandler('.edit-currency-btn', function(id, btn) {
        openEditCurrencyModal(id, btn);
    });

    Helpers.setupButtonHandler('.update-sort-btn', function(id, btn) {
        openSortModal(id, btn);
    });

    window.CurrenciesModalsV2 = {
        openCreateCurrencyModal,
        openEditCurrencyModal,
        openSortModal
    };

    window.openCreateCurrencyModalV2 = openCreateCurrencyModal;

    console.log('✅ Currencies Modals V2 loaded');
})();
