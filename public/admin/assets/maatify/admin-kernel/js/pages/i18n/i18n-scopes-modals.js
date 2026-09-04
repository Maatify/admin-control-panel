/**
 * 🌐 I18n Scopes Management - Modals Module
 * ==========================================
 * Features:
 * - Create Scope Modal
 * - Change Code Modal
 * - Update Metadata Modal
 * - Update Sort Order Modal
 */

(function() {
    'use strict';

    console.log('📝 I18n Scopes Modals Module Loading...');

    // ========================================================================
    // PREREQUISITES CHECK
    // ========================================================================

    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }

    console.log('✅ ApiHandler loaded');

    const capabilities = window.i18nScopesCapabilities || {};

    // ========================================================================
    // MODAL HTML DEFINITIONS
    // ========================================================================

    const createScopeModalHTML = `
        <div id="create-scope-modal" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4 border border-transparent dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">➕ Create New Scope</h3>
                    <button class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="create-scope-form" class="px-6 py-4 space-y-4">
                    <div>
                        <label for="create-code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Scope Code <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="create-code"
                            name="code"
                            required
                            maxlength="50"
                            placeholder="e.g., user.profile"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Unique identifier for this scope (1-50 characters)</p>
                    </div>

                    <div>
                        <label for="create-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Scope Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="create-name"
                            name="name"
                            required
                            maxlength="100"
                            placeholder="e.g., User Profile"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Display name (1-100 characters)</p>
                    </div>

                    <div>
                        <label for="create-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Description
                        </label>
                        <textarea
                            id="create-description"
                            name="description"
                            rows="3"
                            maxlength="255"
                            placeholder="Brief description of this scope..."
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        ></textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Optional (max 255 characters)</p>
                    </div>

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="create-active"
                            name="is_active"
                            checked
                            class="w-4 h-4 text-blue-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500"
                        />
                        <label for="create-active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" class="close-modal px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                            Create Scope
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    const changeCodeModalHTML = `
        <div id="change-code-modal" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 border border-transparent dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">🏷️ Change Scope Code</h3>
                    <button class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="change-code-form" class="px-6 py-4 space-y-4">
                    <input type="hidden" id="code-scope-id" name="id" />

                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700 dark:text-yellow-200">
                                    <strong>Warning:</strong> Changing the code may break integrations relying on the old value.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Current Code
                        </label>
                        <div id="code-current-code" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-300 font-mono"></div>
                    </div>

                    <div>
                        <label for="code-new-code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            New Code <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="code-new-code"
                            name="new_code"
                            required
                            maxlength="50"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none font-mono"
                        />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" class="close-modal px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition-colors shadow-sm">
                            Change Code
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    const updateMetadataModalHTML = `
        <div id="update-metadata-modal" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4 border border-transparent dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">✏️ Update Scope Metadata</h3>
                    <button class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="update-metadata-form" class="px-6 py-4 space-y-4">
                    <input type="hidden" id="meta-scope-id" name="id" />

                    <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400 p-3">
                        <p class="text-sm text-blue-700 dark:text-blue-200">
                            ℹ️ At least one field (name or description) must be provided.
                        </p>
                    </div>

                    <div>
                        <label for="meta-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Name
                        </label>
                        <input
                            type="text"
                            id="meta-name"
                            name="name"
                            maxlength="100"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        />
                    </div>

                    <div>
                        <label for="meta-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Description
                        </label>
                        <textarea
                            id="meta-description"
                            name="description"
                            rows="3"
                            maxlength="255"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" class="close-modal px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                            Update Metadata
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

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
                    <input type="hidden" id="sort-scope-id" name="id" />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Scope Name</label>
                        <div id="sort-scope-name" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-200 font-medium"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Current Sort Order</label>
                        <div id="sort-current-order" class="px-4 py-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-600 dark:text-gray-400"></div>
                    </div>

                    <div>
                        <label for="sort-new-order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            New Position <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            id="sort-new-order"
                            name="position"
                            min="0"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" class="close-modal px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                            Update Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    // ========================================================================
    // HELPER FUNCTION - Fetch Scope Details
    // ========================================================================

    async function fetchScopeDetails(scopeId) {
        console.log('🔍 Fetching scope details for ID:', scopeId);

        const result = await ApiHandler.call('i18n/scopes/query', {
            page: 1,
            per_page: 1,
            search: { columns: { id: scopeId } }
        }, 'Fetch Scope Details');

        if (result.success && result.data.data && result.data.data.length > 0) {
            console.log('✅ Scope details fetched:', result.data.data[0]);
            return result.data.data[0];
        }

        console.error('❌ Failed to fetch scope details');
        ApiHandler.showAlert('danger', 'Failed to fetch scope details');
        return null;
    }

    // ========================================================================
    // CREATE SCOPE MODAL
    // ========================================================================

    function setupCreateScopeModal() {
        const btnCreate = document.getElementById('btn-create-scope');
        if (btnCreate && capabilities.can_create) {
            btnCreate.addEventListener('click', () => {
                console.log('➕ Opening create scope modal');
                document.getElementById('create-scope-modal').classList.remove('hidden');
            });
        }

        const form = document.getElementById('create-scope-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('📤 Submitting create scope form');

            // Clear previous errors
            form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
            form.querySelectorAll('.field-error').forEach(el => el.remove());

            const payload = {
                code: document.getElementById('create-code').value.trim(),
                name: document.getElementById('create-name').value.trim(),
                description: document.getElementById('create-description').value.trim(),
                is_active: document.getElementById('create-active').checked
            };

            console.log('📦 Payload:', payload);

            const result = await ApiHandler.call('i18n/scopes/create', payload, 'Create Scope');

            if (result.success) {
                ApiHandler.showAlert('success', '✅ Scope created successfully');
                document.getElementById('create-scope-modal').classList.add('hidden');
                form.reset();

                // Reload table
                if (typeof window.reloadScopesTable === 'function') {
                    window.reloadScopesTable();
                }
            } else {
                // Show validation errors if present
                if (result.data && result.data.errors) {
                    ApiHandler.showFieldErrors(result.data.errors, 'create-scope-form');
                } else {
                    // Show general error message
                    ApiHandler.showAlert('danger', result.error || 'Failed to create scope');
                }
            }
        });

        console.log('✅ Create scope modal setup complete');
    }

    // ========================================================================
    // CHANGE CODE MODAL
    // ========================================================================

    async function openChangeCodeModal(scopeId) {
        console.log('🏷️ Opening change code modal for scope:', scopeId);

        const scope = await fetchScopeDetails(scopeId);
        if (!scope) return;

        document.getElementById('code-scope-id').value = scope.id;
        document.getElementById('code-current-code').textContent = scope.code;
        document.getElementById('code-new-code').value = scope.code;
        document.getElementById('change-code-modal').classList.remove('hidden');
    }

    function setupChangeCodeModal() {
        const form = document.getElementById('change-code-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('📤 Submitting change code form');

            // Clear previous errors
            form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
            form.querySelectorAll('.field-error').forEach(el => el.remove());

            const payload = {
                id: parseInt(document.getElementById('code-scope-id').value),
                new_code: document.getElementById('code-new-code').value.trim()
            };

            console.log('📦 Payload:', payload);

            const result = await ApiHandler.call('i18n/scopes/change-code', payload, 'Change Code');

            if (result.success) {
                ApiHandler.showAlert('success', '✅ Scope code updated successfully');
                document.getElementById('change-code-modal').classList.add('hidden');
                form.reset();

                if (typeof window.reloadScopesTable === 'function') {
                    window.reloadScopesTable();
                }
            } else {
                // Show validation errors if present
                if (result.data && result.data.errors) {
                    ApiHandler.showFieldErrors(result.data.errors, 'change-code-form');
                } else {
                    // Show general error message
                    ApiHandler.showAlert('danger', result.error || 'Failed to change scope code');
                }
            }
        });

        console.log('✅ Change code modal setup complete');
    }

    // ========================================================================
    // UPDATE METADATA MODAL
    // ========================================================================

    async function openUpdateMetadataModal(scopeId) {
        console.log('✏️ Opening update metadata modal for scope:', scopeId);

        const scope = await fetchScopeDetails(scopeId);
        if (!scope) return;

        document.getElementById('meta-scope-id').value = scope.id;
        document.getElementById('meta-name').value = scope.name;
        document.getElementById('meta-description').value = scope.description || '';
        document.getElementById('update-metadata-modal').classList.remove('hidden');
    }

    function setupUpdateMetadataModal() {
        const form = document.getElementById('update-metadata-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('📤 Submitting update metadata form');

            // Clear previous errors
            form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
            form.querySelectorAll('.field-error').forEach(el => el.remove());

            const name = document.getElementById('meta-name').value.trim();
            const description = document.getElementById('meta-description').value.trim();

            if (!name && !description) {
                ApiHandler.showAlert('warning', 'At least one field (name or description) must be provided');
                return;
            }

            const payload = {
                id: parseInt(document.getElementById('meta-scope-id').value)
            };

            if (name) payload.name = name;
            if (description) payload.description = description;

            console.log('📦 Payload:', payload);

            const result = await ApiHandler.call('i18n/scopes/update-metadata', payload, 'Update Metadata');

            if (result.success) {
                ApiHandler.showAlert('success', '✅ Metadata updated successfully');
                document.getElementById('update-metadata-modal').classList.add('hidden');
                form.reset();

                if (typeof window.reloadScopesTable === 'function') {
                    window.reloadScopesTable();
                }
            } else {
                // Show validation errors if present
                if (result.data && result.data.errors) {
                    ApiHandler.showFieldErrors(result.data.errors, 'update-metadata-form');
                } else {
                    // Show general error message
                    ApiHandler.showAlert('danger', result.error || 'Failed to update metadata');
                }
            }
        });

        console.log('✅ Update metadata modal setup complete');
    }

    // ========================================================================
    // UPDATE SORT MODAL
    // ========================================================================

    async function openUpdateSortModal(scopeId) {
        console.log('🔢 Opening update sort modal for scope:', scopeId);

        const scope = await fetchScopeDetails(scopeId);
        if (!scope) return;

        document.getElementById('sort-scope-id').value = scope.id;
        document.getElementById('sort-scope-name').textContent = scope.name;
        document.getElementById('sort-current-order').textContent = scope.sort_order;
        document.getElementById('sort-new-order').value = scope.sort_order;
        document.getElementById('update-sort-modal').classList.remove('hidden');
    }

    function setupUpdateSortModal() {
        const form = document.getElementById('update-sort-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('📤 Submitting update sort form');

            // Clear previous errors
            form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
            form.querySelectorAll('.field-error').forEach(el => el.remove());

            const payload = {
                id: parseInt(document.getElementById('sort-scope-id').value),
                position: parseInt(document.getElementById('sort-new-order').value)
            };

            console.log('📦 Payload:', payload);

            const result = await ApiHandler.call('i18n/scopes/update-sort', payload, 'Update Sort Order');

            if (result.success) {
                ApiHandler.showAlert('success', '✅ Sort order updated successfully');
                document.getElementById('update-sort-modal').classList.add('hidden');
                form.reset();

                if (typeof window.reloadScopesTable === 'function') {
                    window.reloadScopesTable();
                }
            } else {
                // Show validation errors if present
                if (result.data && result.data.errors) {
                    ApiHandler.showFieldErrors(result.data.errors, 'update-sort-form');
                } else {
                    // Show general error message
                    ApiHandler.showAlert('danger', result.error || 'Failed to update sort order');
                }
            }
        });

        console.log('✅ Update sort modal setup complete');
    }

    // ========================================================================
    // MODAL CLOSE HANDLERS
    // ========================================================================

    function setupModalCloseHandlers() {
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = e.target.closest('.fixed');
                if (modal) {
                    modal.classList.add('hidden');
                    // Reset form if exists
                    const form = modal.querySelector('form');
                    if (form) form.reset();
                }
            });
        });

        console.log('✅ Modal close handlers setup complete');
    }

    // ========================================================================
    // INITIALIZATION
    // ========================================================================

    function initModalsModule() {
        console.log('🎬 Initializing I18n Scopes Modals Module...');

        // Inject modals into DOM
        document.body.insertAdjacentHTML('beforeend', createScopeModalHTML);
        document.body.insertAdjacentHTML('beforeend', changeCodeModalHTML);
        document.body.insertAdjacentHTML('beforeend', updateMetadataModalHTML);
        document.body.insertAdjacentHTML('beforeend', updateSortModalHTML);
        console.log('✅ Modals injected into DOM');

        // Setup all modals
        setupCreateScopeModal();
        setupChangeCodeModal();
        setupUpdateMetadataModal();
        setupUpdateSortModal();
        setupModalCloseHandlers();

        // Export modal opener functions
        window.ScopesModals = {
            openChangeCodeModal,
            openUpdateMetadataModal,
            openUpdateSortModal
        };

        console.log('✅ I18n Scopes Modals Module initialized');
    }

    // Run initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModalsModule);
    } else {
        initModalsModule();
    }

})();