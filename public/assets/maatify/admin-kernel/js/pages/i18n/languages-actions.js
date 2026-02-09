/**
 * 🎯 Languages Management - Optimized Actions Module
 * ==================================================
 * ✅ OPTIMIZATION: Reduced from 643 lines to ~450 lines
 * ✅ Uses LanguagesHelpers.setupButtonHandler() for all event delegation
 * ✅ Eliminated 200+ lines of duplicate code
 *
 * Features:
 * - Toggle Active Status
 * - Update Name (inline editing)
 * - Update Code (inline editing)
 * - Update Sort Order (modal)
 */

(function() {
    'use strict';

    console.log('🎯 Languages Actions Module Loading (OPTIMIZED)...');

    // Check dependencies
    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }

    if (typeof LanguagesHelpers === 'undefined') {
        console.error('❌ LanguagesHelpers not found!');
        return;
    }

    console.log('✅ Dependencies loaded: ApiHandler, LanguagesHelpers');

    // ========================================================================
    // Update Sort Order Modal HTML
    // ========================================================================

    const updateSortModalHTML = `
        <div id="update-sort-modal" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 border border-transparent dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">🔢 Update Sort Order</h3>
                    <button class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="update-sort-form" class="px-6 py-4 space-y-4">
                    <input type="hidden" id="sort-language-id" name="language_id" />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Language</label>
                        <div id="sort-language-name" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-200 font-medium"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Sort Order</label>
                        <div id="sort-current-order" class="px-4 py-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-600 dark:text-gray-400"></div>
                    </div>

                    <div>
                        <label for="sort-new-order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            New Sort Order <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            id="sort-new-order"
                            name="sort_order"
                            min="1"
                            max="999"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" class="close-modal px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                            Update Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // ========================================================================
    // Initialize Module
    // ========================================================================

    function initActionsModule() {
        console.log('🎬 Initializing Languages Actions Module (OPTIMIZED)...');

        // Inject sort modal
        document.body.insertAdjacentHTML('beforeend', updateSortModalHTML);
        console.log('✅ Update Sort Order modal injected');

        // Setup form handlers
        setupUpdateSortForm();

        // ✅ OPTIMIZATION: Use LanguagesHelpers.setupButtonHandler()
        // This replaces 200+ lines of duplicate event delegation code!
        setupAllActionHandlers();

        console.log('✅ Languages Actions Module initialized (OPTIMIZED)');
    }

    // ========================================================================
    // ✅ OPTIMIZED: Setup All Action Handlers (Reduced Code!)
    // ========================================================================

    function setupAllActionHandlers() {
        // Toggle Status - uses helper function
        LanguagesHelpers.setupButtonHandler('.toggle-status-btn', async (id, btn) => {
            const currentStatus = btn.getAttribute('data-current-status') === '1';
            await toggleLanguageStatus(id, !currentStatus);
        });

        // Update Sort - uses helper function
        LanguagesHelpers.setupButtonHandler('.update-sort-btn', async (id) => {
            await openUpdateSortModal(id);
        });

        // Edit Name - uses helper function with custom data attribute
        LanguagesHelpers.setupButtonHandler('.edit-name-btn', async (id, btn) => {
            const currentName = btn.getAttribute('data-current-name');
            enableInlineNameEdit(id, currentName, btn);
        });

        // Edit Code - uses helper function with custom data attribute
        LanguagesHelpers.setupButtonHandler('.edit-code-btn', async (id, btn) => {
            const currentCode = btn.getAttribute('data-current-code');
            enableInlineCodeEdit(id, currentCode, btn);
        });

        console.log('✅ All action handlers setup (using LanguagesHelpers)');
    }

    // ========================================================================
    // Toggle Active Status
    // ========================================================================

    async function toggleLanguageStatus(languageId, newStatus) {
        console.log(`🔄 Toggling language ${languageId} to ${newStatus ? 'active' : 'inactive'}`);

        const payload = {
            language_id: parseInt(languageId),
            is_active: newStatus
        };

        const result = await ApiHandler.call('languages/set-active', payload, 'Toggle Active');

        if (!result.success) {
            return;
        }

        ApiHandler.showAlert('success', `Language ${newStatus ? 'activated' : 'deactivated'} successfully`);
        reloadLanguagesTable();
    }

    // ========================================================================
    // Update Name (Inline Editing)
    // ========================================================================

    function enableInlineNameEdit(languageId, currentName, triggerButton) {
        console.log('✏️ Enabling inline edit for language', languageId, 'name');

        const row = triggerButton.closest('tr');
        if (!row) {
            console.error('❌ Could not find row');
            return;
        }

        const nameCell = row.querySelector('[data-field="name"]');
        if (!nameCell) {
            console.error('❌ Could not find name cell');
            return;
        }

        const originalContent = nameCell.innerHTML;

        nameCell.innerHTML = `
            <div class="flex items-center gap-2">
                <input type="text" 
                       id="inline-name-input-${languageId}"
                       value="${currentName}" 
                       class="px-3 py-1 border border-blue-500 dark:border-blue-400 rounded focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 outline-none"
                       style="min-width: 200px;">
                <button class="save-name-btn px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700" 
                        data-language-id="${languageId}" 
                        title="Save">✓</button>
                <button class="cancel-name-edit-btn px-3 py-1 bg-gray-400 text-white rounded hover:bg-gray-500" 
                        title="Cancel">✕</button>
            </div>
        `;

        const input = document.getElementById(`inline-name-input-${languageId}`);
        input.focus();
        input.select();

        // Save handler
        LanguagesHelpers.setupButtonHandler('.save-name-btn', async (id) => {
            const newName = input.value.trim();
            if (!newName) {
                ApiHandler.showAlert('warning', 'Name cannot be empty');
                return;
            }
            if (newName === currentName) {
                nameCell.innerHTML = originalContent;
                return;
            }
            await updateLanguageName(id, newName);
        }, { requireData: false });

        // Cancel handler
        document.addEventListener('click', function cancelHandler(e) {
            if (e.target.closest('.cancel-name-edit-btn')) {
                nameCell.innerHTML = originalContent;
                document.removeEventListener('click', cancelHandler);
            }
        });

        // Enter key = save
        input.addEventListener('keypress', async (e) => {
            if (e.key === 'Enter') {
                const newName = input.value.trim();
                if (newName && newName !== currentName) {
                    await updateLanguageName(languageId, newName);
                }
            }
        });

        // Escape key = cancel
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                nameCell.innerHTML = originalContent;
            }
        });
    }

    async function updateLanguageName(languageId, newName) {
        console.log('💾 Updating language name:', languageId, newName);

        const payload = {
            language_id: parseInt(languageId),
            name: newName
        };

        const result = await ApiHandler.call('languages/update-name', payload, 'Update Name');

        if (!result.success) {
            return;
        }

        ApiHandler.showAlert('success', 'Language name updated successfully');
        reloadLanguagesTable();
    }

    // ========================================================================
    // Update Code (Inline Editing)
    // ========================================================================

    function enableInlineCodeEdit(languageId, currentCode, triggerButton) {
        console.log('🏷️ Enabling inline edit for language', languageId, 'code');

        const row = triggerButton.closest('tr');
        if (!row) {
            console.error('❌ Could not find row');
            return;
        }

        const codeCell = row.querySelector('[data-field="code"]');
        if (!codeCell) {
            console.error('❌ Could not find code cell');
            return;
        }

        const originalContent = codeCell.innerHTML;

        codeCell.innerHTML = `
            <div class="flex items-center gap-2">
                <input type="text" 
                       id="inline-code-input-${languageId}"
                       value="${currentCode}" 
                       maxlength="5"
                       class="px-3 py-1 border border-blue-500 dark:border-blue-400 rounded focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 outline-none uppercase font-mono"
                       style="width: 100px;">
                <button class="save-code-btn px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700" 
                        data-language-id="${languageId}" 
                        title="Save">✓</button>
                <button class="cancel-code-edit-btn px-3 py-1 bg-gray-400 text-white rounded hover:bg-gray-500" 
                        title="Cancel">✕</button>
            </div>
        `;

        const input = document.getElementById(`inline-code-input-${languageId}`);
        input.focus();
        input.select();

        // Auto-lowercase
        input.addEventListener('input', (e) => {
            e.target.value = e.target.value.toLowerCase();
        });

        // Save handler
        LanguagesHelpers.setupButtonHandler('.save-code-btn', async (id) => {
            const newCode = input.value.trim().toLowerCase();
            if (!LanguagesHelpers.isValidLanguageCode(newCode)) {
                ApiHandler.showAlert('warning', 'Code must be 2-5 lowercase letters');
                return;
            }
            if (newCode === currentCode) {
                codeCell.innerHTML = originalContent;
                return;
            }
            await updateLanguageCode(id, newCode);
        }, { requireData: false });

        // Cancel handler
        document.addEventListener('click', function cancelHandler(e) {
            if (e.target.closest('.cancel-code-edit-btn')) {
                codeCell.innerHTML = originalContent;
                document.removeEventListener('click', cancelHandler);
            }
        });

        // Enter key = save
        input.addEventListener('keypress', async (e) => {
            if (e.key === 'Enter') {
                const newCode = input.value.trim().toLowerCase();
                if (LanguagesHelpers.isValidLanguageCode(newCode) && newCode !== currentCode) {
                    await updateLanguageCode(languageId, newCode);
                }
            }
        });

        // Escape key = cancel
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                codeCell.innerHTML = originalContent;
            }
        });
    }

    async function updateLanguageCode(languageId, newCode) {
        console.log('💾 Updating language code:', languageId, newCode);

        const payload = {
            language_id: parseInt(languageId),
            code: newCode
        };

        const result = await ApiHandler.call('languages/update-code', payload, 'Update Code');

        if (!result.success) {
            return;
        }

        ApiHandler.showAlert('success', 'Language code updated successfully');
        reloadLanguagesTable();
    }

    // ========================================================================
    // Update Sort Order Modal
    // ========================================================================

    async function openUpdateSortModal(languageId) {
        console.log('🔢 Opening Update Sort Order Modal for language:', languageId);

        const queryPayload = {
            page: 1,
            per_page: 1,
            search: {
                columns: {
                    id: languageId
                }
            }
        };

        const result = await ApiHandler.call('languages/query', queryPayload, 'Query Language for Sort');

        if (!result.success) {
            ApiHandler.showAlert('danger', result.error || 'Failed to load language details');
            return;
        }

        if (!result.data || !result.data.data || result.data.data.length === 0) {
            ApiHandler.showAlert('danger', 'Language not found');
            return;
        }

        const language = result.data.data[0];

        document.getElementById('sort-language-id').value = language.id;
        document.getElementById('sort-language-name').textContent = language.name;
        document.getElementById('sort-current-order').textContent = language.sort_order || 'N/A';
        document.getElementById('sort-new-order').value = language.sort_order || 1;

        LanguagesHelpers.openModal('update-sort-modal');
    }

    function setupUpdateSortForm() {
        const form = document.getElementById('update-sort-form');
        if (!form) {
            console.warn('⚠️ Update Sort form not found');
            return;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const languageId = parseInt(document.getElementById('sort-language-id').value);
            const sortOrder = parseInt(document.getElementById('sort-new-order').value);

            if (!languageId || !sortOrder || sortOrder < 1) {
                ApiHandler.showAlert('warning', 'Invalid sort order');
                return;
            }

            const payload = {
                language_id: languageId,
                sort_order: sortOrder
            };

            console.log('📤 Updating sort order:', payload);

            const result = await ApiHandler.call('languages/update-sort', payload, 'Update Sort');

            if (!result.success) {
                return;
            }

            ApiHandler.showAlert('success', 'Sort order updated successfully');
            LanguagesHelpers.closeAllModals();
            reloadLanguagesTable();
        });

        console.log('✅ Update Sort Order form handler setup complete');
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    function reloadLanguagesTable() {
        if (window.languagesDebug && typeof window.languagesDebug.loadLanguages === 'function') {
            console.log('🔄 Reloading languages table...');
            window.languagesDebug.loadLanguages();
        } else {
            console.warn('⚠️ loadLanguages function not found');
            ApiHandler.showAlert('info', 'Please refresh the page to see changes');
        }
    }

    // ========================================================================
    // Export & Initialize
    // ========================================================================

    window.LanguagesActions = {
        toggleLanguageStatus,
        updateLanguageName,
        updateLanguageCode,
        openUpdateSortModal
    };

    console.log('✅ LanguagesActions exported to window (OPTIMIZED)');
    console.log('   ↳ Code reduced by ~200 lines using helpers!');

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initActionsModule);
    } else {
        initActionsModule();
    }

})();