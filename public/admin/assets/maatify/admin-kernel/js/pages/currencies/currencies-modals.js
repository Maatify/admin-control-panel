/**
 * 🛠️ Currencies Management - Modals Module
 * ===================================================
 * Handles modal logic for Creation, Editing, and Sort updates.
 */

(function() {
    'use strict';

    console.log('🛠️ Currencies Modals Module Loading...');

    if (!window.CurrenciesHelpers) {
        console.error('❌ CurrenciesHelpers not found!');
        return;
    }

    const { setupButtonHandler, openModal, closeModal, setupModalCloseHandlers, clearFormInputs } = window.CurrenciesHelpers;

    // Modal Close Handlers initialization
    setupModalCloseHandlers();

    // ========================================================================
    // Logic: Create
    // ========================================================================

    window.openCreateCurrencyModal = function() {
        clearFormInputs('currency-form');
        document.getElementById('currency-id').value = '';
        document.getElementById('currency-active').checked = true;
        document.getElementById('currency-sort').value = '0';
        document.getElementById('currency-modal-title').textContent = 'Create New Currency';

        // Unbind previous save events to avoid duplicate submissions
        const saveBtn = document.getElementById('btn-save-currency');
        const newSaveBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newSaveBtn, saveBtn);

        newSaveBtn.addEventListener('click', async () => {
            const payload = {
                code: document.getElementById('currency-code').value.trim(),
                name: document.getElementById('currency-name').value.trim(),
                symbol: document.getElementById('currency-symbol').value.trim(),
                is_active: document.getElementById('currency-active').checked,
                display_order: parseInt(document.getElementById('currency-sort').value, 10) || 0
            };

            const result = await ApiHandler.call('currencies/create', payload, 'Create Currency');
            if (result.success) {
                ApiHandler.showAlert('success', 'Currency created successfully.');
                closeModal('currency-modal');
                if (typeof window.reloadCurrenciesTable === 'function') {
                    window.reloadCurrenciesTable();
                }
            }
        });

        openModal('currency-modal');
    };

    // ========================================================================
    // Logic: Edit
    // ========================================================================

    function openEditCurrencyModal(id, btn) {
        document.getElementById('currency-id').value = id;
        document.getElementById('currency-code').value = btn.getAttribute('data-current-code');
        document.getElementById('currency-name').value = btn.getAttribute('data-current-name');
        document.getElementById('currency-symbol').value = btn.getAttribute('data-current-symbol');
        document.getElementById('currency-sort').value = btn.getAttribute('data-current-display-order');
        document.getElementById('currency-active').checked = btn.getAttribute('data-current-is-active') === '1';

        document.getElementById('currency-modal-title').textContent = 'Edit Currency';

        // Unbind previous save events to avoid duplicate submissions
        const saveBtn = document.getElementById('btn-save-currency');
        const newSaveBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newSaveBtn, saveBtn);

        newSaveBtn.addEventListener('click', async () => {
            const idVal = document.getElementById('currency-id').value;
            let displayOrder = parseInt(document.getElementById('currency-sort').value, 10) || 0;
            if (displayOrder === 0) displayOrder = 1; // 0 not allowed in update

            const payload = {
                id: parseInt(idVal, 10),
                code: document.getElementById('currency-code').value.trim(),
                name: document.getElementById('currency-name').value.trim(),
                symbol: document.getElementById('currency-symbol').value.trim(),
                is_active: document.getElementById('currency-active').checked,
                display_order: displayOrder
            };

            const result = await ApiHandler.call('currencies/update', payload, 'Update Currency');
            if (result.success) {
                ApiHandler.showAlert('success', 'Currency updated successfully.');
                closeModal('currency-modal');
                if (typeof window.reloadCurrenciesTable === 'function') {
                    window.reloadCurrenciesTable();
                }
            }
        });

        openModal('currency-modal');
    }

    setupButtonHandler('.edit-currency-btn', (id, btn) => {
        openEditCurrencyModal(id, btn);
    });

    // ========================================================================
    // Logic: Sort Order
    // ========================================================================

    function openSortModal(id, btn) {
        document.getElementById('sort-currency-id').value = id;
        document.getElementById('sort-new-value').value = btn.getAttribute('data-current-sort');
        openModal('sort-modal');
    }

    document.getElementById('btn-save-sort').addEventListener('click', async () => {
        const id = document.getElementById('sort-currency-id').value;
        const newSort = document.getElementById('sort-new-value').value;

        const payload = {
            id: parseInt(id, 10),
            display_order: parseInt(newSort, 10)
        };

        const result = await ApiHandler.call('currencies/update-sort', payload, 'Update Sort Order');
        if (result.success) {
            ApiHandler.showAlert('success', 'Sort order updated successfully.');
            closeModal('sort-modal');
            if (typeof window.reloadCurrenciesTable === 'function') {
                window.reloadCurrenciesTable();
            }
        }
    });

    setupButtonHandler('.update-sort-btn', (id, btn) => {
        openSortModal(id, btn);
    });

    console.log('✅ Currencies Modals Module loaded');

})();
