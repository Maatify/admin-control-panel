/**
 * Content Document Types Management
 * Handles listing, creating, and updating content document types.
 */

document.addEventListener('DOMContentLoaded', () => {
    // ============================================================
    // 1. Initialization & State
    // ============================================================
    const tableContainerId = 'content-document-types-table-container';
    const filterForm = document.getElementById('content-document-types-filter-form');
    const globalSearchInput = document.getElementById('content-document-types-search');
    const globalSearchBtn = document.getElementById('content-document-types-search-btn');
    const globalClearBtn = document.getElementById('content-document-types-clear-search');
    const resetFiltersBtn = document.getElementById('content-document-types-reset-filters');
    const createBtn = document.getElementById('btn-create-content-document-type');

    // ============================================================
    // 2. Data Table Configuration
    // ============================================================

    // Define Table Columns
    const headers = ['ID', 'Key', 'Requires Acceptance', 'System Type', 'Created At', 'Actions'];
    const rowKeys = ['id', 'key', 'requires_acceptance_default', 'is_system', 'created_at', 'actions'];

    // Custom Renderers
    const customRenderers = {
        id: (value, row) => {
            if (window.contentDocumentTypesCapabilities.can_view_versions) {
                return `<a href="/content-document-types/${row.id}/documents" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">${value}</a>`;
            }
            return `<span class="text-gray-500">${value}</span>`;
        },
        key: (value) => `<span class="font-mono text-sm text-blue-600 dark:text-blue-400">${value}</span>`,
        requires_acceptance_default: (value) => {
            return window.AdminUIComponents.renderStatusBadge(
                value,
                {
                    clickable: false,
                    activeText: 'Yes',
                    inactiveText: 'No',
                    activeColor: 'green',
                    inactiveColor: 'gray'
                }
            );
        },
        is_system: (value) => {
            return window.AdminUIComponents.renderStatusBadge(
                value,
                {
                    clickable: false,
                    activeText: 'System',
                    inactiveText: 'Custom',
                    activeColor: 'purple',
                    inactiveColor: 'blue'
                }
            );
        },
        created_at: (value) => `<span class="text-sm text-gray-500 dark:text-gray-400">${value}</span>`,
        actions: (_, row) => {
            if (!window.contentDocumentTypesCapabilities.can_update) return '';

            return window.AdminUIComponents.buildActionButton({
                icon: window.AdminUIComponents.SVGIcons.edit,
                text: 'Edit',
                color: 'blue',
                title: 'Edit Document Type',
                dataAttributes: {
                    id: row.id,
                    key: encodeURIComponent(row.key),
                    requires_acceptance: row.requires_acceptance_default,
                    is_system: row.is_system
                },
                cssClass: 'btn-edit-document-type'
            });
        }
    };

    // Pagination Info Callback
    const getPaginationInfo = (pagination) => {
        const { page = 1, per_page = 25, total = 0, filtered = total } = pagination;
        const displayCount = filtered !== undefined ? filtered : total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem = Math.min(page * per_page, displayCount);

        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;

        if (typeof filtered !== 'undefined' && filtered !== total) {
            infoText += ` <span class="text-gray-500 dark:text-gray-400">(filtered from ${total} total)</span>`;
        }

        return { total: displayCount, info: infoText };
    };

    let currentPage = 1;
    let currentPerPage = 25;

    const loadTable = () => {
        const filters = {
            id: document.getElementById('filter-id').value,
            key: document.getElementById('filter-key').value,
            requires_acceptance_default: document.getElementById('filter-requires-acceptance-default').value,
            is_system: document.getElementById('filter-is-system').value
        };
        // Remove empty filters
        Object.keys(filters).forEach(key => filters[key] === "" && delete filters[key]);

        const globalSearch = globalSearchInput.value.trim();

        const params = {
            page: currentPage,
            per_page: currentPerPage
        };

        const search = {};
        if (globalSearch) search.global = globalSearch;
        if (Object.keys(filters).length > 0) search.columns = filters;

        if (Object.keys(search).length > 0) {
            params.search = search;
        }

        // Hijack container ID for TableComponent
        const container = document.getElementById(tableContainerId);
        if (!container) return;

        const originalTableContainer = document.getElementById('table-container');
        let tempId = null;

        if (originalTableContainer && originalTableContainer !== container) {
            tempId = 'table-container-original-' + Date.now();
            originalTableContainer.id = tempId;
        }

        const originalContainerId = container.id;
        container.id = 'table-container';

        createTable(
            window.contentDocumentTypesApi.query,
            params,
            headers,
            rowKeys,
            false, // withSelection
            'id', // primaryKey
            null, // onSelectionChange
            customRenderers,
            null, // selectableIds
            getPaginationInfo
        ).then(() => {
            container.id = originalContainerId;
            if (tempId && originalTableContainer) originalTableContainer.id = 'table-container';
        }).catch(err => {
            console.error("Table creation failed", err);
            container.id = originalContainerId;
            if (tempId && originalTableContainer) originalTableContainer.id = 'table-container';
        });
    };

    // ============================================================
    // 3. Event Listeners (Search & Filter)
    // ============================================================

    // Column Filters
    filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        currentPage = 1;
        loadTable();
    });

    resetFiltersBtn.addEventListener('click', () => {
        filterForm.reset();
        currentPage = 1;
        loadTable();
    });

    // Global Search
    globalSearchBtn.addEventListener('click', () => {
        currentPage = 1;
        loadTable();
    });

    globalSearchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            currentPage = 1;
            loadTable();
        }
    });

    globalClearBtn.addEventListener('click', () => {
        globalSearchInput.value = '';
        currentPage = 1;
        loadTable();
    });

    // Table Events
    document.addEventListener('tableAction', (e) => {
        const { action, value } = e.detail;
        if (action === 'pageChange') {
            currentPage = value;
            loadTable();
        } else if (action === 'perPageChange') {
            currentPerPage = value;
            currentPage = 1;
            loadTable();
        }
    });

    // ============================================================
    // 4. Actions (Create, Update)
    // ============================================================

    // --- Create Modal ---
    if (createBtn) {
        createBtn.addEventListener('click', async () => {
            // Fetch Dropdown Data
            let dropdownData = [];
            try {
                const result = await ApiHandler.call(window.contentDocumentTypesApi.dropdown, {}, 'Fetch Document Types Dropdown');
                
                if (result.success) {
                    // Handle standard API response wrapper { data: [...] }
                    if (result.data && Array.isArray(result.data.data)) {
                        dropdownData = result.data.data;
                    } 
                    // Handle direct array response [...]
                    else if (Array.isArray(result.data)) {
                        dropdownData = result.data;
                    }
                } else {
                    throw new Error(result.error || 'Failed to load document types');
                }
            } catch (error) {
                console.error('Dropdown fetch failed:', error);
                ApiHandler.showAlert('danger', 'Failed to load available document types');
                return;
            }

            if (dropdownData.length === 0) {
                ApiHandler.showAlert('info', 'All document types are already registered.');
                return;
            }

            // Build Options
            const options = dropdownData.map(item => ({
                value: item.key,
                label: item.label
            }));

            const modalContent = `
                <div class="p-6">
                    <form id="create-document-type-form" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Document Type Key</label>
                            <div id="create-key-container" class="relative w-full">
                                <div class="js-select-box w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 cursor-pointer flex items-center justify-between hover:border-gray-400 transition-colors">
                                    <input type="text" class="js-select-input bg-transparent border-none outline-none w-full cursor-pointer text-gray-900 dark:text-gray-100" placeholder="Select Document Type" readonly>
                                    <svg class="js-arrow w-4 h-4 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                                <div class="js-dropdown hidden absolute z-50 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                    <div class="p-2 sticky top-0 bg-white dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                                        <input type="text" class="js-search-input w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Search...">
                                    </div>
                                    <ul class="js-select-list py-1">
                                        <!-- Options will be populated by JS -->
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="create-requires-acceptance" class="rounded text-blue-600 w-4 h-4 border-gray-300 dark:border-gray-600 focus:ring-blue-500" checked>
                            <label for="create-requires-acceptance" class="text-sm text-gray-700 dark:text-gray-300">Requires Acceptance (Default)</label>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="create-is-system" class="rounded text-blue-600 w-4 h-4 border-gray-300 dark:border-gray-600 focus:ring-blue-500">
                            <label for="create-is-system" class="text-sm text-gray-700 dark:text-gray-300">Is System Type</label>
                        </div>
                    </form>
                </div>
            `;

            // Create modal container
            const modalId = 'create-document-type-modal';
            const modalHtml = window.AdminUIComponents.buildModalTemplate({
                id: modalId,
                title: 'Create Content Document Type',
                content: modalContent,
                footer: window.AdminUIComponents.buildModalFooter({
                    submitText: 'Create',
                    submitColor: 'green'
                }),
                icon: window.AdminUIComponents.SVGIcons.plus
            });

            // Append to body
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modalEl = document.getElementById(modalId);
            modalEl.classList.remove('hidden');

            // Initialize Select2 for Key
            let keySelect = Select2('#create-key-container', options);

            // Handle Submit
            const submitBtn = modalEl.querySelector('button[type="submit"]');
            submitBtn.addEventListener('click', async () => {
                const key = keySelect.getValue();
                const requiresAcceptance = document.getElementById('create-requires-acceptance').checked;
                const isSystem = document.getElementById('create-is-system').checked;

                // Validation
                if (!key) {
                    const keyContainer = document.getElementById('create-key-container');
                    keyContainer.querySelector('.js-select-box').classList.add('border-red-500');
                    return;
                } else {
                    document.getElementById('create-key-container').querySelector('.js-select-box').classList.remove('border-red-500');
                }

                try {
                    const result = await ApiHandler.call(window.contentDocumentTypesApi.create, {
                        key: key,
                        requires_acceptance_default: requiresAcceptance,
                        is_system: isSystem
                    }, 'Create Document Type');

                    if (result.success) {
                        ApiHandler.showAlert('success', 'Document Type created successfully');
                        loadTable();
                        modalEl.remove();
                    } else {
                        ApiHandler.showAlert('danger', result.error || 'Failed to create document type');
                    }
                } catch (error) {
                    ApiHandler.showAlert('danger', 'An error occurred');
                }
            });

            // Handle Close
            const closeBtns = modalEl.querySelectorAll('.close-modal');
            closeBtns.forEach(btn => {
                btn.addEventListener('click', () => modalEl.remove());
            });
        });
    }

    // --- Edit Modal ---
    // Use event delegation for edit buttons
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.btn-edit-document-type');
        if (editBtn) {
            const id = editBtn.dataset.id;
            const key = decodeURIComponent(editBtn.dataset.key);
            const requiresAcceptance = editBtn.dataset.requires_acceptance === '1' || editBtn.dataset.requires_acceptance === 'true';
            const isSystem = editBtn.dataset.is_system === '1' || editBtn.dataset.is_system === 'true';
            openEditModal(id, key, requiresAcceptance, isSystem);
        }
    });

    window.openEditModal = (id, key, requiresAcceptance, isSystem) => {
        const modalContent = `
            <div class="p-6">
                <form id="edit-document-type-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Key</label>
                        <input type="text" value="${key}" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 cursor-not-allowed" disabled>
                        <p class="text-xs text-gray-400 mt-1">Key cannot be modified.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="edit-requires-acceptance" class="rounded text-blue-600 w-4 h-4 border-gray-300 dark:border-gray-600 focus:ring-blue-500" ${requiresAcceptance ? 'checked' : ''}>
                        <label for="edit-requires-acceptance" class="text-sm text-gray-700 dark:text-gray-300">Requires Acceptance (Default)</label>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="edit-is-system" class="rounded text-blue-600 w-4 h-4 border-gray-300 dark:border-gray-600 focus:ring-blue-500" ${isSystem ? 'checked' : ''}>
                        <label for="edit-is-system" class="text-sm text-gray-700 dark:text-gray-300">Is System Type</label>
                    </div>
                </form>
            </div>
        `;

        const modalId = 'edit-document-type-modal';
        const modalHtml = window.AdminUIComponents.buildModalTemplate({
            id: modalId,
            title: 'Edit Content Document Type',
            content: modalContent,
            footer: window.AdminUIComponents.buildModalFooter({
                submitText: 'Save Changes',
                submitColor: 'blue'
            }),
            icon: window.AdminUIComponents.SVGIcons.edit
        });

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modalEl = document.getElementById(modalId);
        modalEl.classList.remove('hidden');

        // Handle Submit
        const submitBtn = modalEl.querySelector('button[type="submit"]');
        submitBtn.addEventListener('click', async () => {
            const newRequiresAcceptance = document.getElementById('edit-requires-acceptance').checked;
            const newIsSystem = document.getElementById('edit-is-system').checked;

            try {
                const updateUrl = window.contentDocumentTypesApi.update.replace('{type_id}', id);
                const result = await ApiHandler.call(updateUrl, {
                    requires_acceptance_default: newRequiresAcceptance,
                    is_system: newIsSystem
                }, 'Update Document Type');

                if (result.success) {
                    ApiHandler.showAlert('success', 'Document Type updated successfully');
                    loadTable();
                    modalEl.remove();
                } else {
                    ApiHandler.showAlert('danger', result.error || 'Failed to update document type');
                }
            } catch (error) {
                ApiHandler.showAlert('danger', 'An error occurred');
            }
        });

        // Handle Close
        const closeBtns = modalEl.querySelectorAll('.close-modal');
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => modalEl.remove());
        });
    };

    // Initial Load
    loadTable();
});
