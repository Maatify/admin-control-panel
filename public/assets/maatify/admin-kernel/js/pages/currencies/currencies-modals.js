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

    // ========================================================================
    // Create/Edit Modal HTML Injection (Dynamic)
    // ========================================================================

    const modalHtml = `
    <!-- Create/Edit Currency Modal -->
    <div id="currency-modal" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 id="currency-modal-title" class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                    Currency Details
                </h3>
                <button type="button" class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-6 h-6 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6">
                <form id="currency-form" class="space-y-4">
                    <input type="hidden" id="currency-id">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Code (ISO 4217)</label>
                        <input type="text" id="currency-code" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 uppercase" maxlength="3" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <input type="text" id="currency-name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Symbol</label>
                        <input type="text" id="currency-symbol" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Display Order</label>
                        <input type="number" id="currency-sort" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" min="0" value="0">
                        <p class="text-xs text-gray-500 mt-1">0 = append to end.</p>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" id="currency-active" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                        <label for="currency-active" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                            Is Active
                        </label>
                    </div>

                </form>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <button type="button" class="close-modal px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">Cancel</button>
                <button type="button" id="btn-save-currency" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">Save</button>
            </div>
        </div>
    </div>

    <!-- Update Sort Modal -->
    <div id="sort-modal" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Update Sort Order</h3>
                <button type="button" class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-6 h-6 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-6">
                <input type="hidden" id="sort-currency-id">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Display Order</label>
                <input type="number" id="sort-new-value" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" min="1" required>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <button type="button" class="close-modal px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">Cancel</button>
                <button type="button" id="btn-save-sort" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">Save</button>
            </div>
        </div>
    </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    setupModalCloseHandlers();

    // ========================================================================
    // Logic: Create & Edit
    // ========================================================================

    window.openCreateCurrencyModal = function() {
        clearFormInputs('currency-form');
        document.getElementById('currency-id').value = '';
        document.getElementById('currency-active').checked = true;
        document.getElementById('currency-sort').value = '0';
        document.getElementById('currency-modal-title').textContent = 'Create New Currency';
        openModal('currency-modal');
    };

    function openEditCurrencyModal(id, btn) {
        document.getElementById('currency-id').value = id;
        document.getElementById('currency-code').value = btn.getAttribute('data-current-code');
        document.getElementById('currency-name').value = btn.getAttribute('data-current-name');
        document.getElementById('currency-symbol').value = btn.getAttribute('data-current-symbol');
        document.getElementById('currency-sort').value = btn.getAttribute('data-current-display-order');
        document.getElementById('currency-active').checked = btn.getAttribute('data-current-is-active') === '1';

        document.getElementById('currency-modal-title').textContent = 'Edit Currency';
        openModal('currency-modal');
    }

    document.getElementById('btn-save-currency').addEventListener('click', async () => {
        const idVal = document.getElementById('currency-id').value;
        const isEdit = idVal !== '';

        const payload = {
            code: document.getElementById('currency-code').value.trim(),
            name: document.getElementById('currency-name').value.trim(),
            symbol: document.getElementById('currency-symbol').value.trim(),
            is_active: document.getElementById('currency-active').checked,
            display_order: parseInt(document.getElementById('currency-sort').value, 10) || 0
        };

        if (isEdit) {
            payload.id = parseInt(idVal, 10);
            if (payload.display_order === 0) payload.display_order = 1; // 0 not allowed in update
        }

        const endpoint = isEdit ? '/api/currencies/update' : '/api/currencies/create';
        const label = isEdit ? 'Update Currency' : 'Create Currency';

        const result = await ApiHandler.call(endpoint, payload, label);
        if (result.success) {
            ApiHandler.showAlert('success', `Currency ${isEdit ? 'updated' : 'created'} successfully.`);
            closeModal('currency-modal');
            if (typeof window.reloadCurrenciesTable === 'function') {
                window.reloadCurrenciesTable();
            }
        }
    });

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

        const result = await ApiHandler.call('/api/currencies/update-sort', payload, 'Update Sort Order');
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
