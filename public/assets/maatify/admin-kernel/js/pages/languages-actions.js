/**
 * 🎯 Languages Management - Actions Module
 * ==========================================
 * Handles inline and modal actions for language management:
 * - Update Name (inline with contenteditable)
 * - Update Code (inline with contenteditable)
 * - Update Sort Order (modal with input)
 * - Toggle Active (inline button - moved from languages.js)
 *
 * Features:
 * - ✅ Uses ApiHandler for all API calls
 * - ✅ Respects capabilities from server
 * - ✅ Inline editing with contenteditable
 * - ✅ Modal for sort order
 * - ✅ Proper error handling
 * - ✅ Success/error messages
 * - ✅ Auto table reload on success
 *
 * Dependencies:
 * - ApiHandler (api_handler.js)
 * - LanguagesHelpers (languages-helpers.js)
 * - window.languagesCapabilities (injected by server)
 */

(function() {
    'use strict';

    console.log('🎯 Languages Actions Module Loading...');

    // Check dependencies
    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found! Make sure api_handler.js is loaded first.');
        return;
    }

    if (typeof LanguagesHelpers === 'undefined') {
        console.error('❌ LanguagesHelpers not found! Make sure languages-helpers.js is loaded first.');
        return;
    }

    console.log('✅ Dependencies loaded: ApiHandler, LanguagesHelpers');

    // ========================================================================
    // Update Sort Order Modal HTML
    // ========================================================================

    /**
     * Update Sort Order Modal Template
     * Simple modal with number input
     */
    const updateSortModalHTML = `
        <div id="update-sort-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">🔢 Update Sort Order</h3>
                    <button class="close-modal text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="update-sort-form" class="px-6 py-4 space-y-4">
                    <input type="hidden" id="sort-language-id" name="language_id" />

                    <!-- Language Name (Display Only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Language
                        </label>
                        <div id="sort-language-name" class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-700 font-medium">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                    <!-- Current Sort Order (Display Only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Current Sort Order
                        </label>
                        <div id="sort-current-order" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-600">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                    <!-- New Sort Order -->
                    <div>
                        <label for="sort-new-order" class="block text-sm font-medium text-gray-700 mb-2">
                            New Sort Order <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            id="sort-new-order"
                            name="sort_order"
                            min="1"
                            max="999"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Enter new position..."
                        />
                        <p class="mt-1 text-xs text-gray-500">Other languages will be shifted accordingly</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <button
                            type="button"
                            class="close-modal px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                        >
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
        console.log('🎬 Initializing Languages Actions Module...');

        // Inject modal HTML
        document.body.insertAdjacentHTML('beforeend', updateSortModalHTML);
        console.log('✅ Update Sort Order modal injected');

        // Setup modal close handlers (uses LanguagesHelpers)
        LanguagesHelpers.setupModalCloseHandlers();

        // Setup form handlers
        setupUpdateSortForm();

        // Setup event delegation for inline actions
        setupInlineActionHandlers();

        console.log('✅ Languages Actions Module initialized');
    }

    // ========================================================================
    // Inline Action Handlers
    // ========================================================================

    /**
     * Setup event delegation for all inline actions
     * Uses event delegation on document to handle dynamically created buttons
     */
    function setupInlineActionHandlers() {
        document.addEventListener('click', async (e) => {
            // Toggle Active Status - use closest() to handle clicks on nested elements
            const toggleBtn = e.target.closest('.toggle-status-btn');
            if (toggleBtn) {
                e.preventDefault();
                const languageId = toggleBtn.getAttribute('data-language-id');
                const currentStatus = toggleBtn.getAttribute('data-current-status') === '1';
                await toggleLanguageStatus(languageId, !currentStatus);
                return; // Important: prevent other handlers
            }

            // Open Update Sort Modal - use closest()
            const sortBtn = e.target.closest('.update-sort-btn');
            if (sortBtn) {
                e.preventDefault();
                const languageId = sortBtn.getAttribute('data-language-id');
                openUpdateSortModal(languageId);
                return;
            }

            // Update Name (inline edit trigger) - use closest()
            const nameBtn = e.target.closest('.edit-name-btn');
            if (nameBtn) {
                e.preventDefault();
                const languageId = nameBtn.getAttribute('data-language-id');
                const currentName = nameBtn.getAttribute('data-current-name');
                enableInlineNameEdit(languageId, currentName, nameBtn);
                return;
            }

            // Update Code (inline edit trigger) - use closest()
            const codeBtn = e.target.closest('.edit-code-btn');
            if (codeBtn) {
                e.preventDefault();
                const languageId = codeBtn.getAttribute('data-language-id');
                const currentCode = codeBtn.getAttribute('data-current-code');
                enableInlineCodeEdit(languageId, currentCode, codeBtn);
                return;
            }
        });

        console.log('✅ Inline action handlers setup complete');
    }

    // ========================================================================
    // Toggle Active Status
    // ========================================================================

    /**
     * Toggle language active status
     * ✅ Uses ApiHandler
     * ✅ Handles 409 business rule errors
     */
    async function toggleLanguageStatus(languageId, newStatus) {
        console.log(`🔄 Toggling language ${languageId} to ${newStatus ? 'active' : 'inactive'}`);

        // Build payload
        const payload = {
            language_id: parseInt(languageId),
            is_active: newStatus
        };

        // Use ApiHandler
        const result = await ApiHandler.call('languages/set-active', payload, 'Toggle Active');

        if (!result.success) {
            // Error already shown by ApiHandler
            return;
        }

        // Success!
        ApiHandler.showAlert('success', `Language ${newStatus ? 'activated' : 'deactivated'} successfully`);

        // Reload table
        reloadLanguagesTable();
    }

    // ========================================================================
    // Update Name (Inline Editing)
    // ========================================================================

    /**
     * Enable inline name editing
     * Converts the display element to an editable input
     */
    function enableInlineNameEdit(languageId, currentName, buttonElement) {
        console.log(`✏️ Enabling inline edit for language ${languageId} name`);

        // Find the name display element (should be near the button)
        const row = buttonElement.closest('tr');
        if (!row) {
            console.error('❌ Could not find table row');
            return;
        }

        const nameCell = row.querySelector('[data-field="name"]');
        if (!nameCell) {
            console.error('❌ Could not find name cell');
            return;
        }

        // Save original content
        const originalContent = nameCell.innerHTML;

        // Create inline input
        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentName;
        input.className = 'w-full px-2 py-1 border border-blue-500 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none';
        input.setAttribute('data-language-id', languageId);
        input.setAttribute('data-original-name', currentName);

        // Create action buttons
        const buttonsDiv = document.createElement('div');
        buttonsDiv.className = 'flex gap-1 mt-1';

        const saveBtn = document.createElement('button');
        saveBtn.innerHTML = '✓';
        saveBtn.className = 'px-2 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700';
        saveBtn.title = 'Save';

        const cancelBtn = document.createElement('button');
        cancelBtn.innerHTML = '✕';
        cancelBtn.className = 'px-2 py-1 bg-gray-400 text-white text-xs rounded hover:bg-gray-500';
        cancelBtn.title = 'Cancel';

        buttonsDiv.appendChild(saveBtn);
        buttonsDiv.appendChild(cancelBtn);

        // Replace cell content
        nameCell.innerHTML = '';
        nameCell.appendChild(input);
        nameCell.appendChild(buttonsDiv);

        // Focus input
        input.focus();
        input.select();

        // Save handler
        saveBtn.addEventListener('click', async () => {
            const newName = input.value.trim();

            if (!newName) {
                ApiHandler.showAlert('warning', 'Name cannot be empty');
                input.focus();
                return;
            }

            if (newName === currentName) {
                // No change, just restore
                nameCell.innerHTML = originalContent;
                return;
            }

            // Call API
            const result = await updateLanguageName(languageId, newName);

            if (result) {
                // Success - reload table
                reloadLanguagesTable();
            } else {
                // Error - restore original
                nameCell.innerHTML = originalContent;
            }
        });

        // Cancel handler
        cancelBtn.addEventListener('click', () => {
            nameCell.innerHTML = originalContent;
        });

        // Enter key = save, Escape = cancel
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveBtn.click();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancelBtn.click();
            }
        });
    }

    /**
     * Update language name via API
     * ✅ Uses ApiHandler
     */
    async function updateLanguageName(languageId, newName) {
        console.log(`📝 Updating language ${languageId} name to: ${newName}`);

        const payload = {
            language_id: parseInt(languageId),
            name: newName
        };

        const result = await ApiHandler.call('languages/update-name', payload, 'Update Name');

        if (!result.success) {
            // Error already shown by ApiHandler
            return false;
        }

        // Success!
        ApiHandler.showAlert('success', 'Language name updated successfully');
        return true;
    }

    // ========================================================================
    // Update Code (Inline Editing)
    // ========================================================================

    /**
     * Enable inline code editing
     * Converts the display element to an editable input
     */
    function enableInlineCodeEdit(languageId, currentCode, buttonElement) {
        console.log(`🏷️ Enabling inline edit for language ${languageId} code`);

        // Find the code display element
        const row = buttonElement.closest('tr');
        if (!row) {
            console.error('❌ Could not find table row');
            return;
        }

        const codeCell = row.querySelector('[data-field="code"]');
        if (!codeCell) {
            console.error('❌ Could not find code cell');
            return;
        }

        // Save original content
        const originalContent = codeCell.innerHTML;

        // Create inline input
        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentCode;
        input.maxLength = 5;
        input.className = 'w-full px-2 py-1 border border-blue-500 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none uppercase';
        input.setAttribute('data-language-id', languageId);
        input.setAttribute('data-original-code', currentCode);

        // Create action buttons
        const buttonsDiv = document.createElement('div');
        buttonsDiv.className = 'flex gap-1 mt-1';

        const saveBtn = document.createElement('button');
        saveBtn.innerHTML = '✓';
        saveBtn.className = 'px-2 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700';
        saveBtn.title = 'Save';

        const cancelBtn = document.createElement('button');
        cancelBtn.innerHTML = '✕';
        cancelBtn.className = 'px-2 py-1 bg-gray-400 text-white text-xs rounded hover:bg-gray-500';
        cancelBtn.title = 'Cancel';

        buttonsDiv.appendChild(saveBtn);
        buttonsDiv.appendChild(cancelBtn);

        // Replace cell content
        codeCell.innerHTML = '';
        codeCell.appendChild(input);
        codeCell.appendChild(buttonsDiv);

        // Focus input
        input.focus();
        input.select();

        // Auto-lowercase as user types
        input.addEventListener('input', () => {
            input.value = input.value.toLowerCase();
        });

        // Save handler
        saveBtn.addEventListener('click', async () => {
            const newCode = input.value.trim().toLowerCase();

            // Validate code format
            if (!newCode) {
                ApiHandler.showAlert('warning', 'Code cannot be empty');
                input.focus();
                return;
            }

            if (!/^[a-z]{2,5}$/.test(newCode)) {
                ApiHandler.showAlert('warning', 'Code must be 2-5 lowercase letters');
                input.focus();
                return;
            }

            if (newCode === currentCode.toLowerCase()) {
                // No change, just restore
                codeCell.innerHTML = originalContent;
                return;
            }

            // Call API
            const result = await updateLanguageCode(languageId, newCode);

            if (result) {
                // Success - reload table
                reloadLanguagesTable();
            } else {
                // Error - restore original
                codeCell.innerHTML = originalContent;
            }
        });

        // Cancel handler
        cancelBtn.addEventListener('click', () => {
            codeCell.innerHTML = originalContent;
        });

        // Enter key = save, Escape = cancel
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveBtn.click();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancelBtn.click();
            }
        });
    }

    /**
     * Update language code via API
     * ✅ Uses ApiHandler
     */
    async function updateLanguageCode(languageId, newCode) {
        console.log(`🏷️ Updating language ${languageId} code to: ${newCode}`);

        const payload = {
            language_id: parseInt(languageId),
            code: newCode
        };

        const result = await ApiHandler.call('languages/update-code', payload, 'Update Code');

        if (!result.success) {
            // Error already shown by ApiHandler
            return false;
        }

        // Success!
        ApiHandler.showAlert('success', 'Language code updated successfully');
        return true;
    }

    // ========================================================================
    // Update Sort Order Modal
    // ========================================================================

    /**
     * Open Update Sort Order Modal
     * Fetches language details and populates form
     */
    async function openUpdateSortModal(languageId) {
        console.log('🔢 Opening Update Sort Order Modal for language:', languageId);

        // Build query to fetch language details
        const queryPayload = {
            page: 1,
            per_page: 1,
            search: {
                columns: {
                    id: languageId  // Keep as string - backend will handle filtering
                }
            }
        };

        // Use ApiHandler to fetch language
        const result = await ApiHandler.call('languages/query', queryPayload, 'Query Language for Sort');

        if (!result.success) {
            ApiHandler.showAlert('danger', result.error || 'Failed to load language details');
            return;
        }

        // Check if language found
        if (!result.data || !result.data.data || result.data.data.length === 0) {
            ApiHandler.showAlert('danger', 'Language not found');
            return;
        }

        const language = result.data.data[0];

        // Populate form
        document.getElementById('sort-language-id').value = language.id;
        document.getElementById('sort-language-name').textContent = language.name;
        document.getElementById('sort-current-order').textContent = language.sort_order || 'N/A';
        document.getElementById('sort-new-order').value = language.sort_order || 1;

        LanguagesHelpers.openModal('update-sort-modal');
    }

    /**
     * Setup Update Sort Order Form Handler
     * ✅ Uses ApiHandler
     */
    function setupUpdateSortForm() {
        const form = document.getElementById('update-sort-form');
        if (!form) {
            console.warn('⚠️ Update Sort Order form not found');
            return;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const languageId = parseInt(document.getElementById('sort-language-id').value);
            const newSortOrder = parseInt(document.getElementById('sort-new-order').value);

            // Validate
            if (!languageId || !newSortOrder || newSortOrder < 1) {
                ApiHandler.showAlert('warning', 'Invalid sort order value');
                return;
            }

            // Build payload
            const payload = {
                language_id: languageId,
                sort_order: newSortOrder
            };

            console.log('📤 Updating sort order:', payload);

            // Use ApiHandler
            const result = await ApiHandler.call('languages/update-sort', payload, 'Update Sort Order');

            if (!result.success) {
                // Error already shown by ApiHandler
                return;
            }

            // Success!
            ApiHandler.showAlert('success', 'Sort order updated successfully');
            LanguagesHelpers.closeAllModals();

            // Reload table
            reloadLanguagesTable();
        });

        console.log('✅ Update Sort Order form handler setup complete');
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /**
     * Reload languages table
     * Calls the global loadLanguages function if available
     */
    function reloadLanguagesTable() {
        if (window.languagesDebug && typeof window.languagesDebug.loadLanguages === 'function') {
            console.log('🔄 Reloading languages table...');
            window.languagesDebug.loadLanguages();
        } else {
            console.warn('⚠️ loadLanguages function not found - table will not auto-reload');
            ApiHandler.showAlert('info', 'Please refresh the page to see changes');
        }
    }

    // ========================================================================
    // Export Functions to Window
    // ========================================================================

    // Export functions for use by other modules
    window.LanguagesActions = {
        toggleLanguageStatus,
        openUpdateSortModal,
        enableInlineNameEdit,
        enableInlineCodeEdit,
        updateLanguageName,
        updateLanguageCode
    };

    console.log('✅ LanguagesActions exported to window');

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initActionsModule);
    } else {
        initActionsModule();
    }

})();