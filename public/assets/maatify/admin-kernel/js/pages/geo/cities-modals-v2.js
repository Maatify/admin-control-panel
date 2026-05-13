/**
 * 🛠️ Cities Modals V2
 */

(function() {
    'use strict';

    console.log('🛠️ Cities Modals V2 Loading...');

    if (!window.AdminPageBridge || !window.CitiesHelpersV2) {
        console.error('❌ Missing dependencies for cities-modals-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.CitiesHelpersV2;
    const reloadCitiesTableV2 = function() {
        if (typeof window.reloadCitiesTableV2 === 'function') {
            return window.reloadCitiesTableV2();
        }
    };

    Helpers.setupModalCloseHandlers();

    function collectCityPayload() {
        const payload = Bridge.Form.collect({
            id: { selector: '#geo-id', type: 'int' },
            code: '#geo-code',
            name: '#geo-name',
            symbol: '#geo-symbol',
            is_active: { selector: '#geo-active', type: 'checked' },
        }, { includeEmpty: true });

        // Explicitly inject country_id from context
        const countryId = window.geoCitiesContext?.country_id;
        if (countryId) {
            payload.country_id = Bridge.normalizeInt(countryId, 0);
        }

        return payload;
    }

    function bindSaveAction(handler) {
        const saveBtn = document.getElementById('btn-save-geo');
        if (!saveBtn || !saveBtn.parentNode) return;

        const newBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newBtn, saveBtn);
        newBtn.addEventListener('click', handler);
    }

    function openCreateCityModal() {
        Helpers.clearFormInputs('geo-form');
        const idEl = document.getElementById('geo-id');
        const activeEl = document.getElementById('geo-active');
        const titleEl = document.getElementById('geo-modal-title');

        if (idEl) idEl.value = '';
        if (activeEl) activeEl.checked = true;
        if (titleEl) titleEl.textContent = 'Create New City';

        bindSaveAction(async function() {
            const payload = Bridge.Form.omitEmpty(collectCityPayload());
            delete payload.id;

            await Bridge.API.runMutation({
                operation: 'Create City',
                endpoint: 'geo/cities/create',
                method: 'POST',
                payload,
                successMessage: 'City created successfully.',
                modal: '#geo-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadCitiesTableV2
            });
        });

        Bridge.Modal.open('#geo-modal');
    }

    function openEditCityModal(id, btn) {
        const titleEl = document.getElementById('geo-modal-title');
        if (titleEl) titleEl.textContent = 'Edit City';

        const idEl = document.getElementById('geo-id');
        const codeEl = document.getElementById('geo-code');
        const nameEl = document.getElementById('geo-name');
        const symbolEl = document.getElementById('geo-symbol');
        const activeEl = document.getElementById('geo-active');

        if (idEl) idEl.value = id;
        if (codeEl) codeEl.value = btn.getAttribute('data-current-code') || '';
        if (nameEl) nameEl.value = btn.getAttribute('data-current-name') || '';
        if (symbolEl) symbolEl.value = btn.getAttribute('data-current-symbol') || '';
        if (activeEl) activeEl.checked = btn.getAttribute('data-current-is-active') === '1';

        bindSaveAction(async function() {
            const payload = collectCityPayload();

            await Bridge.API.runMutation({
                operation: 'Update City',
                endpoint: 'geo/cities/update',
                method: 'POST',
                payload,
                successMessage: 'City updated successfully.',
                modal: '#geo-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadCitiesTableV2
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
                endpoint: 'geo/cities/update-sort',
                method: 'POST',
                payload,
                successMessage: 'Sort order updated successfully.',
                modal: '#sort-modal',
                reloadHandler: reloadCitiesTableV2
            });
        });
    }

    Helpers.setupButtonHandler('.edit-city-btn', function(id, btn) {
        openEditCityModal(id, btn);
    });

    Helpers.setupButtonHandler('.update-sort-btn', function(id, btn) {
        openSortModal(id, btn);
    });

    window.CitiesModalsV2 = {
        openCreateCityModal,
        openEditCityModal,
        openSortModal
    };

    window.openCreateCityModalV2 = openCreateCityModal;

    console.log('✅ Cities Modals V2 loaded');
})();
