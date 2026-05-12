/**
 * 🛠️ Geo Modals V2
 */

(function() {
    'use strict';

    console.log('🛠️ Geo Modals V2 Loading...');

    if (!window.AdminPageBridge || !window.GeoHelpersV2) {
        console.error('❌ Missing dependencies for geo-modals-v2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.GeoHelpersV2;
    const reloadGeoTableV2 = function() {
        if (typeof window.reloadGeoTableV2 === 'function') {
            return window.reloadGeoTableV2();
        }
    };

    Helpers.setupModalCloseHandlers();

    function collectGeoPayload() {
        return Bridge.Form.collect({
            id: { selector: '#geo-id', type: 'int' },
            code: '#geo-code',
            name: '#geo-name',
            symbol: '#geo-symbol',
            is_active: { selector: '#geo-active', type: 'checked' },
            // display_order: { selector: '#geo-sort', type: 'int', default: 0 }
        }, { includeEmpty: true });
    }

    function bindSaveAction(handler) {
        const saveBtn = document.getElementById('btn-save-geo');
        if (!saveBtn || !saveBtn.parentNode) return;

        const newBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newBtn, saveBtn);
        newBtn.addEventListener('click', handler);
    }

    function openCreateGeoModal() {
        Helpers.clearFormInputs('geo-form');
        const idEl = document.getElementById('geo-id');
        const activeEl = document.getElementById('geo-active');
        const sortEl = document.getElementById('geo-sort');
        const titleEl = document.getElementById('geo-modal-title');

        if (idEl) idEl.value = '';
        if (activeEl) activeEl.checked = true;
        if (sortEl) sortEl.value = '0';
        if (titleEl) titleEl.textContent = 'Create New Geo';

        bindSaveAction(async function() {
            const payload = Bridge.Form.omitEmpty(collectGeoPayload());
            delete payload.id;

            await Bridge.API.runMutation({
                operation: 'Create Geo',
                endpoint: 'geo/countries/create',
                method: 'POST',
                payload,
                successMessage: 'Geo created successfully.',
                modal: '#geo-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadGeoTableV2
            });
        });

        Bridge.Modal.open('#geo-modal');
    }

    function openEditGeoModal(id, btn) {
        const titleEl = document.getElementById('geo-modal-title');
        if (titleEl) titleEl.textContent = 'Edit Geo';

        const idEl = document.getElementById('geo-id');
        const codeEl = document.getElementById('geo-code');
        const nameEl = document.getElementById('geo-name');
        const symbolEl = document.getElementById('geo-symbol');
        // const sortEl = document.getElementById('geo-sort');
        const activeEl = document.getElementById('geo-active');

        if (idEl) idEl.value = id;
        if (codeEl) codeEl.value = btn.getAttribute('data-current-code') || '';
        if (nameEl) nameEl.value = btn.getAttribute('data-current-name') || '';
        if (symbolEl) symbolEl.value = btn.getAttribute('data-current-symbol') || '';
        // if (sortEl) sortEl.value = btn.getAttribute('data-current-display-order') || '1';
        if (activeEl) activeEl.checked = btn.getAttribute('data-current-is-active') === '1';

        bindSaveAction(async function() {
            const payload = collectGeoPayload();
            // if (payload.display_order === 0) payload.display_order = 1;

            await Bridge.API.runMutation({
                operation: 'Update Geo',
                endpoint: 'geo/countries/update',
                method: 'POST',
                payload,
                successMessage: 'Geo updated successfully.',
                modal: '#geo-modal',
                modalOptions: { resetForm: true },
                reloadHandler: reloadGeoTableV2
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
                reloadHandler: reloadGeoTableV2
            });
        });
    }

    Helpers.setupButtonHandler('.edit-geo-btn', function(id, btn) {
        openEditGeoModal(id, btn);
    });

    Helpers.setupButtonHandler('.update-sort-btn', function(id, btn) {
        openSortModal(id, btn);
    });

    window.GeoModalsV2 = {
        openCreateGeoModal,
        openEditGeoModal,
        openSortModal
    };

    window.openCreateGeoModalV2 = openCreateGeoModal;

    console.log('✅ Geo Modals V2 loaded');
})();
