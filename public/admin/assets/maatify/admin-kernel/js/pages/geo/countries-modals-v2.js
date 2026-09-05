/**
 * 🛠️ Countries Modals V2
 */

(function() {
    'use strict';

    console.log('🛠️ Countries Modals V2 Loading...');

    if (!window.AdminPageBridge || !window.CountriesHelpersV2) {
        console.error('❌ Missing dependencies for countries-modals-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.CountriesHelpersV2;
    const reloadCountriesTableV2 = function() {
        if (typeof window.reloadCountriesTableV2 === 'function') {
            return window.reloadCountriesTableV2();
        }
    };

    Helpers.setupModalCloseHandlers();

    function collectCountryPayload() {
        return Bridge.Form.collect({
            id: { selector: '#geo-id', type: 'int' },
            code: '#geo-code',
            name: '#geo-name',
            currency: '#geo-currency',
            phone_code: '#geo-phone-code',
            is_active: { selector: '#geo-active', type: 'checked' },
        }, { includeEmpty: true });
    }

    function bindSaveAction(handler) {
        const saveBtn = document.getElementById('btn-save-geo');
        if (!saveBtn || !saveBtn.parentNode) return;

        const newBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newBtn, saveBtn);
        newBtn.addEventListener('click', handler);
    }

    function openCreateCountryModal() {
        Helpers.clearFormInputs('geo-form');
        const idEl = document.getElementById('geo-id');
        const activeEl = document.getElementById('geo-active');
        const titleEl = document.getElementById('geo-modal-title');

        if (idEl) idEl.value = '';
        if (activeEl) activeEl.checked = true;
        if (titleEl) titleEl.textContent = 'Create New Country';

        bindSaveAction(async function() {
            const payload = Bridge.Form.omitEmpty(collectCountryPayload());
            delete payload.id;

            await Bridge.API.runMutation({
                operation: 'Create Country',
                endpoint: 'geo/countries/create',
                method: 'POST',
                payload,
                successMessage: 'Country created successfully.',
                modal: '#geo-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadCountriesTableV2
            });
        });

        Bridge.Modal.open('#geo-modal');
    }

    function openEditCountryModal(id, btn) {
        const titleEl = document.getElementById('geo-modal-title');
        if (titleEl) titleEl.textContent = 'Edit Country';
console.log(btn.attributes)
        const idEl = document.getElementById('geo-id');
        const codeEl = document.getElementById('geo-code');
        const nameEl = document.getElementById('geo-name');
        const currencyEl = document.getElementById('geo-currency');
        const phoneCodeEl = document.getElementById('geo-phone-code');
        const activeEl = document.getElementById('geo-active');

        if (idEl) idEl.value = id;
        if (codeEl) codeEl.value = btn.getAttribute('data-current-code') || '';
        if (nameEl) nameEl.value = btn.getAttribute('data-current-name') || '';
        if (currencyEl) currencyEl.value = btn.getAttribute('data-current-currency') || '';
        if (phoneCodeEl) phoneCodeEl.value = btn.getAttribute('data-current-phone-code') || '';
        if (activeEl) activeEl.checked = btn.getAttribute('data-current-is-active') === '1';

        bindSaveAction(async function() {
            const payload = collectCountryPayload();

            await Bridge.API.runMutation({
                operation: 'Update Country',
                endpoint: 'geo/countries/update',
                method: 'POST',
                payload,
                successMessage: 'Country updated successfully.',
                modal: '#geo-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadCountriesTableV2
            });
        });

        Bridge.Modal.open('#geo-modal');
    }

    function openSortModal(id, btn) {
        const idEl = document.getElementById('sort-geo-id');
        const valueEl = document.getElementById('sort-new-value');
        if (idEl) idEl.value = id;
        if (valueEl) valueEl.value = btn.getAttribute('data-current-sort') || '';
        Bridge.Modal.open('#sort-modal');
    }

    const sortSaveBtn = document.getElementById('btn-save-sort');
    if (sortSaveBtn) {
        sortSaveBtn.addEventListener('click', async function() {
            const payload = Bridge.Form.collect({
                id: { selector: '#sort-geo-id', type: 'int' },
                display_order: { selector: '#sort-new-value', type: 'int' }
            });

            await Bridge.API.runMutation({
                operation: 'Update Sort Order',
                endpoint: 'geo/countries/update-sort',
                method: 'POST',
                payload,
                successMessage: 'Sort order updated successfully.',
                modal: '#sort-modal',
                reloadHandler: reloadCountriesTableV2
            });
        });
    }

    Helpers.setupButtonHandler('.edit-country-btn', function(id, btn) {
        openEditCountryModal(id, btn);
    });

    Helpers.setupButtonHandler('.update-sort-btn', function(id, btn) {
        openSortModal(id, btn);
    });

    window.CountriesModalsV2 = {
        openCreateCountryModal,
        openEditCountryModal,
        openSortModal
    };

    window.openCreateCountryModalV2 = openCreateCountryModal;

    console.log('✅ Countries Modals V2 loaded');
})();
