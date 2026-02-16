/**
 * App Settings Management
 * Handles listing, creating, updating, and toggling app settings.
 */

document.addEventListener('DOMContentLoaded', () => {
    // ============================================================
    // 1. Initialization & State
    // ============================================================
    const tableContainerId = 'app-settings-table-container';
    const filterForm = document.getElementById('app-settings-filter-form');
    const globalSearchInput = document.getElementById('app-settings-search');
    const globalSearchBtn = document.getElementById('app-settings-search-btn');
    const globalClearBtn = document.getElementById('app-settings-clear-search');
    const resetFiltersBtn = document.getElementById('app-settings-reset-filters');
    const createBtn = document.getElementById('btn-create-app-setting');

    // Metadata cache
    let metadataCache = null;

    // ============================================================
    // 2. Data Table Configuration
    // ============================================================
    
    // Define Table Columns
    const headers = ['ID', 'Group', 'Key', 'Value', 'Type', 'Status', 'Actions'];
    const rowKeys = ['id', 'setting_group', 'setting_key', 'setting_value', 'setting_type', 'is_active', 'actions'];

    // Custom Renderers
    const customRenderers = {
        id: (value) => `<span class="text-gray-500">#${value}</span>`,
        setting_group: (value) => `<span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-medium text-gray-600 dark:text-gray-300">${value}</span>`,
        setting_key: (value, row) => {
            let html = `<span class="font-mono text-sm text-blue-600 dark:text-blue-400">${value}</span>`;
            if (row.is_protected) {
                html += ` <span title="Protected Setting" class="text-gray-400 ml-1 cursor-help">🔒</span>`;
            }
            return html;
        },
        setting_value: (value) => `<span class="font-mono text-sm text-gray-800 dark:text-gray-200 truncate max-w-xs block" title="${value}">${value}</span>`,
        setting_type: (value) => `<span class="text-xs font-mono text-gray-500 dark:text-gray-400 uppercase">${value || 'string'}</span>`,
        is_active: (value, row) => {
            // If not editable or no capability, show read-only badge
            if (!window.appSettingsCapabilities.can_set_active || row.is_editable === false) {
                return window.AdminUIComponents.renderStatusBadge(value);
            }
            
            const isChecked = value === 1 ? 'checked' : '';
            return `
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer" ${isChecked} onchange="toggleActive('${row.setting_group}', '${row.setting_key}', this.checked)">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                </label>
            `;
        },
        actions: (_, row) => {
            if (!window.appSettingsCapabilities.can_update) return '';
            
            // If not editable, show protected indicator instead of edit button
            if (row.is_editable === false) {
                return `<span class="text-gray-400 text-xs italic flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg> Protected</span>`;
            }

            return window.AdminUIComponents.buildActionButton({
                icon: window.AdminUIComponents.SVGIcons.edit,
                text: 'Edit',
                color: 'blue',
                title: 'Edit Setting',
                dataAttributes: {
                    group: row.setting_group,
                    key: row.setting_key,
                    value: encodeURIComponent(row.setting_value),
                    type: row.setting_type || 'string'
                },
                cssClass: 'btn-edit-setting'
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
            setting_group: document.getElementById('filter-group').value,
            setting_key: document.getElementById('filter-key').value,
            is_active: document.getElementById('filter-status').value
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
            window.appSettingsApi.query,
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
    // 4. Actions (Create, Update, Toggle)
    // ============================================================

    // --- Toggle Active ---
    window.toggleActive = (group, key, isActive) => {
        ApiHandler.call(window.appSettingsApi.setActive, {
            setting_group: group,
            setting_key: key,
            is_active: isActive
        })
            .then(result => {
                if (result.success) {
                    ApiHandler.showAlert('success', 'Status updated successfully');
                } else {
                    ApiHandler.showAlert('danger', result.error || 'Failed to update status');
                    loadTable(); // Revert toggle
                }
            })
            .catch(error => {
                console.error('Toggle failed:', error);
                ApiHandler.showAlert('danger', 'An error occurred');
                loadTable(); // Revert toggle
            });
    };

    // --- Metadata Fetcher ---
    const fetchMetadata = async () => {
        if (metadataCache) return metadataCache;
        try {
            const result = await ApiHandler.call(window.appSettingsApi.metadata, {});
            if (result.success && result.data && result.data.groups) {
                metadataCache = result.data;
                return result.data;
            }
            throw new Error(result.error || 'Invalid metadata response');
        } catch (error) {
            console.error('Metadata fetch failed:', error);
            ApiHandler.showAlert('danger', 'Failed to load settings configuration');
            return null;
        }
    };

    // --- Create Modal ---
    if (createBtn) {
        createBtn.addEventListener('click', async () => {
            const metadata = await fetchMetadata();
            if (!metadata) return;

            // Build Group Options
            const groupOptions = metadata.groups.map(g => ({
                value: g.name,
                label: `${g.label} (${g.name})`
            }));

            const modalContent = `
                <div class="p-6">
                    <form id="create-setting-form" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Group</label>
                            <div id="create-group-container" class="relative w-full">
                                <div class="js-select-box w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 cursor-pointer flex items-center justify-between hover:border-gray-400 transition-colors">
                                    <input type="text" class="js-select-input bg-transparent border-none outline-none w-full cursor-pointer text-gray-900 dark:text-gray-100" placeholder="Select Group" readonly>
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
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Key</label>
                            
                            <!-- Key Dropdown (Select2) -->
                            <div id="create-key-container" class="relative w-full hidden">
                                <div class="js-select-box w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 cursor-pointer flex items-center justify-between hover:border-gray-400 transition-colors">
                                    <input type="text" class="js-select-input bg-transparent border-none outline-none w-full cursor-pointer text-gray-900 dark:text-gray-100" placeholder="Select Key" readonly>
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

                            <!-- Key Input (Text) -->
                            <input type="text" id="create-key-input" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all dark:placeholder-gray-400 hidden" placeholder="Enter key name">
                            
                            <p id="key-help" class="text-xs text-gray-500 mt-1"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                            <select id="create-type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                <option value="string">String</option>
                                <option value="int">Integer</option>
                                <option value="bool">Boolean</option>
                                <option value="json">JSON</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Value</label>
                            <textarea id="create-value" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all dark:placeholder-gray-400" rows="3" required></textarea>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="create-active" class="rounded text-blue-600 w-4 h-4 border-gray-300 dark:border-gray-600 focus:ring-blue-500" checked>
                            <label for="create-active" class="text-sm text-gray-700 dark:text-gray-300">Active</label>
                        </div>
                    </form>
                </div>
            `;

            // Create modal container
            const modalId = 'create-setting-modal';
            const modalHtml = window.AdminUIComponents.buildModalTemplate({
                id: modalId,
                title: 'Create App Setting',
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

            // Initialize Select2 for Group
            let groupSelect = Select2('#create-group-container', groupOptions);
            let keySelect = null;

            // Handle Group Change
            const keyContainer = document.getElementById('create-key-container');
            const keyInput = document.getElementById('create-key-input');
            const keyHelp = document.getElementById('key-help');
            const groupInput = document.querySelector('#create-group-container .js-select-input');

            // Listen for change event on the hidden input or custom event from Select2
            // Since Select2 updates the input value and dispatches change, we listen on the input
            groupInput.addEventListener('change', () => {
                const selectedGroupName = groupSelect.getValue(); // Or get from dataset
                const selectedGroup = metadata.groups.find(g => g.name === selectedGroupName);
                
                // Reset Key UI
                if (keySelect) {
                    // Ideally destroy, but simple clear is fine for now
                    keyContainer.innerHTML = `
                        <div class="js-select-box w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 cursor-pointer flex items-center justify-between hover:border-gray-400 transition-colors">
                            <input type="text" class="js-select-input bg-transparent border-none outline-none w-full cursor-pointer text-gray-900 dark:text-gray-100" placeholder="Select Key" readonly>
                            <svg class="js-arrow w-4 h-4 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div class="js-dropdown hidden absolute z-50 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-y-auto">
                            <div class="p-2 sticky top-0 bg-white dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                                <input type="text" class="js-search-input w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Search...">
                            </div>
                            <ul class="js-select-list py-1"></ul>
                        </div>
                    `;
                    keySelect = null;
                }
                
                keyContainer.classList.add('hidden');
                keyInput.classList.add('hidden');
                keyInput.value = '';
                keyHelp.textContent = '';

                if (!selectedGroup) return;

                const wildcard = selectedGroup.keys.find(k => k.wildcard);
                const fixedKeys = selectedGroup.keys.filter(k => !k.wildcard);

                if (fixedKeys.length > 0) {
                    // Show dropdown
                    keyContainer.classList.remove('hidden');
                    
                    const keyOptions = fixedKeys.map(k => ({ value: k.key, label: k.key }));
                    if (wildcard) {
                        keyOptions.push({ value: '__custom__', label: '-- Custom Key --' });
                    }

                    keySelect = Select2('#create-key-container', keyOptions);
                    
                    // Handle Key Change (specifically for custom key)
                    const keyInputEl = document.querySelector('#create-key-container .js-select-input');
                    keyInputEl.addEventListener('change', () => {
                        const val = keySelect.getValue();
                        if (val === '__custom__') {
                            keyContainer.classList.add('hidden');
                            keyInput.classList.remove('hidden');
                            keyInput.focus();
                        }
                    });

                } else if (wildcard) {
                    // Only wildcard allowed
                    keyInput.classList.remove('hidden');
                    keyHelp.textContent = 'Custom keys allowed for this group.';
                }
            });

            // Handle Submit
            const submitBtn = modalEl.querySelector('button[type="submit"]');
            submitBtn.addEventListener('click', async () => {
                const group = groupSelect.getValue();
                
                // Determine which key input is active
                let key = '';
                if (!keyContainer.classList.contains('hidden') && keySelect) {
                    key = keySelect.getValue();
                } else if (!keyInput.classList.contains('hidden')) {
                    key = keyInput.value;
                }

                const valueInput = document.getElementById('create-value');
                const value = valueInput.value;
                const type = document.getElementById('create-type').value;
                const isActive = document.getElementById('create-active').checked;

                // --- VALIDATION START ---
                // Use Input_checker.js functions
                
                // 1. Validate Group (Select2)
                let isValid = true;

                if (!group) {
                    const groupContainer = document.getElementById('create-group-container');
                    groupContainer.querySelector('.js-select-box').classList.add('border-red-500');
                    isValid = false;
                } else {
                    document.getElementById('create-group-container').querySelector('.js-select-box').classList.remove('border-red-500');
                }

                // 2. Validate Key
                if (!key) {
                    if (!keyContainer.classList.contains('hidden')) {
                        keyContainer.querySelector('.js-select-box').classList.add('border-red-500');
                    } else if (!keyInput.classList.contains('hidden')) {
                        checkRegCodeAndEmpty([keyInput]); // Use helper for text input
                    }
                    isValid = false;
                } else {
                    if (!keyContainer.classList.contains('hidden')) {
                        keyContainer.querySelector('.js-select-box').classList.remove('border-red-500');
                    }
                    if (!keyInput.classList.contains('hidden')) {
                        checkRegCodeAndEmpty([keyInput]); // Clear error
                    }
                }

                // 3. Validate Value
                if (!checkRegCodeAndEmpty([valueInput])) {
                    isValid = false;
                }

                if (!isValid) {
                    return;
                }
                // --- VALIDATION END ---

                try {
                    const result = await ApiHandler.call(window.appSettingsApi.create, {
                        setting_group: group,
                        setting_key: key,
                        setting_value: value,
                        setting_type: type,
                        is_active: isActive
                    });
                    if (result.success) {
                        ApiHandler.showAlert('success', 'Setting created successfully');
                        loadTable();
                        modalEl.remove();
                    } else {
                        ApiHandler.showAlert('danger', result.error || 'Failed to create setting');
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
        const editBtn = e.target.closest('.btn-edit-setting');
        if (editBtn) {
            const group = editBtn.dataset.group;
            const key = editBtn.dataset.key;
            const value = decodeURIComponent(editBtn.dataset.value);
            const type = editBtn.dataset.type || 'string';
            openEditModal(group, key, value, type);
        }
    });

    window.openEditModal = (group, key, value, type) => {
        const modalContent = `
            <div class="p-6">
                <form id="edit-setting-form" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Group</label>
                            <input type="text" value="${group}" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 cursor-not-allowed" disabled>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Key</label>
                            <input type="text" value="${key}" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 cursor-not-allowed" disabled>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select id="edit-type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="string" ${type === 'string' ? 'selected' : ''}>String</option>
                            <option value="int" ${type === 'int' ? 'selected' : ''}>Integer</option>
                            <option value="bool" ${type === 'bool' ? 'selected' : ''}>Boolean</option>
                            <option value="json" ${type === 'json' ? 'selected' : ''}>JSON</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Value</label>
                        <textarea id="edit-value" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" rows="4" required>${value}</textarea>
                    </div>
                </form>
            </div>
        `;

        const modalId = 'edit-setting-modal';
        const modalHtml = window.AdminUIComponents.buildModalTemplate({
            id: modalId,
            title: 'Edit App Setting',
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
            const valueInput = document.getElementById('edit-value');
            const newValue = valueInput.value;
            const newType = document.getElementById('edit-type').value;
            
            // --- VALIDATION START ---
            if (!checkRegCodeAndEmpty([valueInput])) {
                return;
            }
            // --- VALIDATION END ---

            try {
                const result = await ApiHandler.call(window.appSettingsApi.update, {
                    setting_group: group,
                    setting_key: key,
                    setting_value: newValue,
                    setting_type: newType
                });
                if (result.success) {
                    ApiHandler.showAlert('success', 'Setting updated successfully');
                    loadTable();
                    modalEl.remove();
                } else {
                    ApiHandler.showAlert('danger', result.error || 'Failed to update setting');
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
