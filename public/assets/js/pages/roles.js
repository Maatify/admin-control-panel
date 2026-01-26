/**
 * Roles Page - Roles Management
 * Controls ALL params structure and handles role-specific logic
 * Follows Canonical LIST / QUERY Contract (LOCKED)
 *
 * ⚠️ IMPORTANT:
 * - Role keys (name) are IMMUTABLE
 * - Only display_name and description can be updated
 * - Groups are DERIVED (not stored)
 * - Permission assignments are managed separately
 * - UI capabilities control what actions are available
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('🎭 Roles Management - Initializing');
    console.log('═'.repeat(60));

    // ========================================================================
    // Capabilities Check
    // ========================================================================
    const capabilities = window.rolesCapabilities || {
        can_create: false,
        can_update_meta: false,
        can_rename: false,
        can_toggle: false
    };

    console.log('🔐 UI Capabilities:', capabilities);
    console.log('  ├─ can_create:', capabilities.can_create ? '✅ YES' : '❌ NO');
    console.log('  ├─ can_update_meta:', capabilities.can_update_meta ? '✅ YES' : '❌ NO');
    console.log('  ├─ can_rename:', capabilities.can_rename ? '✅ YES' : '❌ NO (⏳ Planned)');
    console.log('  └─ can_toggle:', capabilities.can_toggle ? '✅ YES' : '❌ NO (⏳ Planned)');
    console.log('═'.repeat(60));

    // ========================================================================
    // Table Configuration
    // ========================================================================
    const headers = ["ID", "Name", "Group", "Display Name", "Description", "Actions"];
    const rows = ["id", "name", "group", "display_name", "description", "actions"];

    // ========================================================================
    // DOM Elements - Search Form
    // ========================================================================
    const searchForm = document.getElementById('roles-search-form');
    const resetBtn = document.getElementById('btn-reset');
    const inputRoleId = document.getElementById('filter-role-id');
    const inputRoleName = document.getElementById('filter-role-name');
    const inputGroup = document.getElementById('filter-group');

    // ========================================================================
    // DOM Elements - Edit Modal
    // ========================================================================
    const editModal = document.getElementById('edit-metadata-modal');
    const editForm = document.getElementById('edit-metadata-form');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelModalBtn = document.getElementById('cancel-modal-btn');
    const saveMetadataBtn = document.getElementById('save-metadata-btn');
    const modalMessage = document.getElementById('modal-message');

    // Modal fields
    const modalRoleId = document.getElementById('modal-role-id');
    const modalRoleName = document.getElementById('modal-role-name');
    const modalRoleGroup = document.getElementById('modal-role-group');
    const modalDisplayName = document.getElementById('modal-display-name');
    const modalDescription = document.getElementById('modal-description');

    // ========================================================================
    // State Management
    // ========================================================================
    let currentEditingRole = null;

    // ========================================================================
    // Custom Renderers - Define ONCE at the top
    // ========================================================================

    /**
     * Custom renderer for ID column
     */
    const idRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';
        return `<span class="font-mono text-sm text-gray-800 font-medium">#${value}</span>`;
    };

    /**
     * Custom renderer for name column (immutable technical key)
     */
    const nameRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';
        return `
            <code class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-mono border border-gray-200">
                ${value}
            </code>
        `;
    };

    /**
     * Custom renderer for group column (derived from name)
     */
    const groupRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';

        // Different colors for different groups
        const groupColors = {
            'admins': 'bg-blue-100 text-blue-800 border-blue-200',
            'sessions': 'bg-green-100 text-green-800 border-green-200',
            'permissions': 'bg-purple-100 text-purple-800 border-purple-200',
            'roles': 'bg-orange-100 text-orange-800 border-orange-200',
            'default': 'bg-gray-100 text-gray-800 border-gray-200'
        };

        const colorClass = groupColors[value.toLowerCase()] || groupColors['default'];

        return `
            <span class="${colorClass} px-3 py-1 rounded-full text-xs font-medium border">
                ${value}
            </span>
        `;
    };

    /**
     * Custom renderer for display_name column
     */
    const displayNameRenderer = (value, row) => {
        if (!value || value.trim() === '') {
            return '<span class="text-gray-400 italic text-xs">Not set</span>';
        }
        return `<span class="text-sm text-gray-800">${value}</span>`;
    };

    /**
     * Custom renderer for description column
     */
    const descriptionRenderer = (value, row) => {
        if (!value || value.trim() === '') {
            return '<span class="text-gray-400 italic text-xs">No description</span>';
        }

        // Truncate long descriptions
        const maxLength = 60;
        if (value.length > maxLength) {
            const truncated = value.substring(0, maxLength) + '...';
            return `<span class="text-sm text-gray-600" title="${value}">${truncated}</span>`;
        }

        return `<span class="text-sm text-gray-600">${value}</span>`;
    };

    /**
     * Custom renderer for actions column
     * Shows available actions based on capabilities
     */
    const actionsRenderer = (value, row) => {
        const roleId = row.id;
        if (!roleId) return '<span class="text-gray-400 italic">—</span>';

        const actions = [];

        // ✅ Edit Metadata (if user has permission)
        if (capabilities.can_update_meta) {
            actions.push(`
                <button 
                    class="edit-metadata-btn inline-flex items-center gap-1 text-xs px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-200"
                    data-role-id="${roleId}"
                    data-role-name="${row.name || ''}"
                    data-role-group="${row.group || ''}"
                    data-display-name="${row.display_name || ''}"
                    data-description="${row.description || ''}"
                    title="Edit metadata (display name & description)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    Edit
                </button>
            `);
        }

        // ⏳ Rename (Planned - disabled)
        if (capabilities.can_rename) {
            actions.push(`
                <button 
                    class="rename-role-btn inline-flex items-center gap-1 text-xs px-3 py-1 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors duration-200"
                    data-role-id="${roleId}"
                    data-role-name="${row.name || ''}"
                    title="Rename role technical key">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                    </svg>
                    Rename
                </button>
            `);
        }

        // ⏳ Toggle (Planned - disabled)
        if (capabilities.can_toggle) {
            const isActive = row.is_active === 1 || row.is_active === true || row.is_active === '1';
            const toggleClass = isActive ? 'bg-orange-600 hover:bg-orange-700' : 'bg-green-600 hover:bg-green-700';
            const toggleText = isActive ? 'Disable' : 'Enable';
            const toggleIcon = isActive
                ? '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />'
                : '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />';

            actions.push(`
                <button 
                    class="toggle-role-btn inline-flex items-center gap-1 text-xs px-3 py-1 ${toggleClass} text-white rounded-md transition-colors duration-200"
                    data-role-id="${roleId}"
                    data-role-name="${row.name || ''}"
                    data-is-active="${isActive ? '1' : '0'}"
                    title="Toggle role activation">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                        ${toggleIcon}
                    </svg>
                    ${toggleText}
                </button>
            `);
        }

        // If no actions available
        if (actions.length === 0) {
            return '<span class="text-gray-400 italic text-xs">No actions</span>';
        }

        return `<div class="flex items-center gap-2 flex-wrap">${actions.join('')}</div>`;
    };

    // ========================================================================
    // Initialize
    // ========================================================================
    init();

    function init() {
        console.log('🔧 Setting up event listeners');
        loadRoles(); // ✅ Load data on page load
        setupEventListeners();
        setupTableEventListeners();
        setupModalEventListeners();
    }

    function setupTableFiltersAfterRender() {
        setTimeout(() => setupTableFilters(), 100);
    }

    // ========================================================================
    // Event Listeners - Search Form
    // ========================================================================
    function setupEventListeners() {
        console.log('🎯 Setting up event listeners');

        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                console.log('🔍 Search form submitted');
                loadRoles();
            });
            console.log('  ├─ Search form listener: ✅');
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                console.log('🔄 Resetting filters');
                if (inputRoleId) inputRoleId.value = '';
                if (inputRoleName) inputRoleName.value = '';
                if (inputGroup) inputGroup.value = '';
                loadRoles();
            });
            console.log('  ├─ Reset button listener: ✅');
        }

        // ✅ Create Role button (Planned)
        const createRoleBtn = document.getElementById('btn-create-role');
        if (createRoleBtn) {
            createRoleBtn.addEventListener('click', handleCreateRole);
            console.log('  ├─ Create role button listener: ✅');
        } else {
            console.log('  ├─ Create role button: ❌ (hidden - no permission)');
        }

        // ✅ Setup click handler for edit buttons (delegated)
        document.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.edit-metadata-btn');
            if (editBtn) {
                handleEditClick(editBtn);
                return;
            }

            // ⏳ Rename button (Planned)
            const renameBtn = e.target.closest('.rename-role-btn');
            if (renameBtn) {
                handleRenameClick(renameBtn);
                return;
            }

            // ⏳ Toggle button (Planned)
            const toggleBtn = e.target.closest('.toggle-role-btn');
            if (toggleBtn) {
                handleToggleClick(toggleBtn);
                return;
            }
        });
        console.log('  ├─ Delegated action listeners: ✅');
        console.log('  └─ Event listeners setup complete');
    }

    function setupTableEventListeners() {
        console.log('📊 Setting up table event listeners');
        document.addEventListener('tableAction', async (e) => {
            const { action, value, currentParams } = e.detail;
            console.log('━'.repeat(60));
            console.log("🔨 Table Event Received");
            console.log('━'.repeat(60));
            console.log('  ├─ Action:', action);
            console.log('  ├─ Value:', value);
            console.log('  └─ Current params:', JSON.stringify(currentParams, null, 2));

            let newParams = JSON.parse(JSON.stringify(currentParams));

            switch(action) {
                case 'pageChange':
                    newParams.page = value;
                    console.log('📄 Page change:', value);
                    break;

                case 'perPageChange':
                    newParams.per_page = value;
                    newParams.page = 1;
                    console.log('🔢 Per-page change:', value, '(reset to page 1)');
                    break;
            }

            // Clean empty values
            console.log('🧹 Cleaning empty search values...');
            if (newParams.search) {
                if (!newParams.search.global || !newParams.search.global.trim()) {
                    delete newParams.search.global;
                    console.log('  ├─ Removed empty global search');
                }

                if (newParams.search.columns) {
                    Object.keys(newParams.search.columns).forEach(key => {
                        if (!newParams.search.columns[key] || !newParams.search.columns[key].toString().trim()) {
                            delete newParams.search.columns[key];
                            console.log('  ├─ Removed empty column:', key);
                        }
                    });

                    if (Object.keys(newParams.search.columns).length === 0) {
                        delete newParams.search.columns;
                        console.log('  ├─ Removed empty columns object');
                    }
                }

                if (Object.keys(newParams.search).length === 0) {
                    delete newParams.search;
                    console.log('  └─ Removed empty search object');
                }
            }

            console.log('✅ Cleaned params:', JSON.stringify(newParams, null, 2));
            console.log('━'.repeat(60));

            await loadRolesWithParams(newParams);
        });
        console.log('  └─ Table event listener: ✅');
    }

    // ========================================================================
    // Modal Event Listeners
    // ========================================================================
    function setupModalEventListeners() {
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', closeEditModal);
        }

        if (cancelModalBtn) {
            cancelModalBtn.addEventListener('click', closeEditModal);
        }

        if (editForm) {
            editForm.addEventListener('submit', handleMetadataUpdate);
        }

        // Close modal on background click
        if (editModal) {
            editModal.addEventListener('click', (e) => {
                if (e.target === editModal) {
                    closeEditModal();
                }
            });
        }

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !editModal.classList.contains('hidden')) {
                closeEditModal();
            }
        });
    }

    // ========================================================================
    // Table Filters (Custom UI) - Global Search
    // ========================================================================
    function setupTableFilters() {
        const filterContainer = document.getElementById('table-custom-filters');
        if (!filterContainer) return;

        filterContainer.innerHTML = `
            <div class="flex gap-4 items-center flex-wrap">
                <div class="w-64">
                    <input id="roles-global-search" 
                        class="w-full border rounded-lg px-3 py-1 text-sm transition-colors duration-200" 
                        placeholder="Search roles..." />
                </div>
            </div>
        `;

        const globalSearch = document.getElementById('roles-global-search');
        if (globalSearch) {
            globalSearch.addEventListener('keyup', (e) => {
                const value = e.target.value.trim();

                // Clear previous timeout
                clearTimeout(globalSearch.searchTimeout);

                // Set new timeout (1 second debounce)
                globalSearch.searchTimeout = setTimeout(() => {
                    handleGlobalSearch(value);
                }, 1000);
            });

            // Visual feedback while typing
            globalSearch.addEventListener('input', (e) => {
                const value = e.target.value.trim();
                if (value.length > 0) {
                    globalSearch.classList.add('border-blue-300', 'bg-blue-50');
                } else {
                    globalSearch.classList.remove('border-blue-300', 'bg-blue-50');
                }
            });
        }
    }

    function handleGlobalSearch(searchValue) {
        console.log("🔎 Global search:", searchValue);
        const params = buildParams(1, 25);

        if (searchValue && searchValue.trim()) {
            if (!params.search) {
                params.search = {};
            }
            params.search.global = searchValue.trim();
        }

        loadRolesWithParams(params);
    }

    // ========================================================================
    // Params Builder
    // ========================================================================
    function buildParams(pageNumber = 1, perPage = 25) {
        console.log('📦 Building API params');
        console.log('  ├─ Page:', pageNumber);
        console.log('  └─ Per page:', perPage);

        const params = {
            page: pageNumber,
            per_page: perPage
        };

        const searchColumns = {};

        // Build column search
        if (inputRoleId && inputRoleId.value.trim()) {
            searchColumns.id = inputRoleId.value.trim();
            console.log('  ├─ Filter: id =', searchColumns.id);
        }
        if (inputRoleName && inputRoleName.value.trim()) {
            searchColumns.name = inputRoleName.value.trim();
            console.log('  ├─ Filter: name =', searchColumns.name);
        }
        if (inputGroup && inputGroup.value.trim()) {
            searchColumns.group = inputGroup.value.trim();
            console.log('  ├─ Filter: group =', searchColumns.group);
        }

        if (Object.keys(searchColumns).length > 0) {
            params.search = { columns: searchColumns };
            console.log('  └─ Search columns:', Object.keys(searchColumns).length);
        } else {
            console.log('  └─ No search filters');
        }

        return params;
    }

    // ========================================================================
    // Pagination Info Callback
    // ========================================================================
    function getRolesPaginationInfo(pagination, params) {
        console.log("🎯 getRolesPaginationInfo called with:", pagination);

        const { page = 1, per_page = 25, total = 0, filtered = total } = pagination;

        // Check if we're filtering
        const hasFilter = params.search &&
            (params.search.global ||
                (params.search.columns && Object.keys(params.search.columns).length > 0));

        const isFiltered = hasFilter && filtered !== total;

        console.log("🔍 Filter status - hasFilter:", hasFilter, "isFiltered:", isFiltered);

        // Calculate based on filtered when applicable
        const displayCount = isFiltered ? filtered : total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem = Math.min(page * per_page, displayCount);

        // Build info text
        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
        if (isFiltered) {
            infoText += ` <span class="text-gray-500 text-xs">(filtered from ${total} total)</span>`;
        }

        console.log("📤 Returning:", { total: displayCount, info: infoText });

        return {
            total: displayCount,
            info: infoText
        };
    }

    // ========================================================================
    // Load Roles
    // ========================================================================
    async function loadRoles(pageNumber = 1) {
        const params = buildParams(pageNumber, 25);
        await loadRolesWithParams(params);
    }

    async function loadRolesWithParams(params) {
        console.log('━'.repeat(60));
        console.log('🚀 API Request - Roles Query');
        console.log('━'.repeat(60));
        console.log('📤 Sending to: /api/roles/query');
        console.log('📦 Payload:', JSON.stringify(params, null, 2));

        if (typeof createTable === 'function') {
            try {
                const result = await createTable(
                    "roles/query",
                    params,
                    headers,
                    rows,
                    false, // ✅ No selection for roles (read-only list)
                    'id',
                    null, // No selection callback
                    {
                        id: idRenderer,
                        name: nameRenderer,
                        group: groupRenderer,
                        display_name: displayNameRenderer,
                        description: descriptionRenderer,
                        actions: actionsRenderer
                    },
                    null, // No selectable IDs
                    getRolesPaginationInfo
                );

                if (result && result.success) {
                    console.log('✅ API Response - Success');
                    console.log('📊 Roles loaded:', result.data.length);
                    console.log('📄 Pagination:', result.pagination);
                    console.log('  ├─ Page:', result.pagination.page);
                    console.log('  ├─ Per page:', result.pagination.per_page);
                    console.log('  ├─ Total:', result.pagination.total);
                    console.log('  └─ Filtered:', result.pagination.filtered);
                    console.log('━'.repeat(60));
                    setupTableFiltersAfterRender();
                } else {
                    console.error('❌ API Response - Failed');
                    console.error('Result:', result);
                    console.log('━'.repeat(60));
                }
            } catch (error) {
                console.error('━'.repeat(60));
                console.error('❌ API Error - Exception thrown');
                console.error('Error:', error);
                console.error('Stack:', error.stack);
                console.error('━'.repeat(60));
                showAlert('d', 'Failed to load roles');
            }
        } else {
            console.error('❌ Critical Error: createTable function not found');
            console.error('Make sure data_table.js is loaded before roles.js');
        }
    }

    // ========================================================================
    // Edit Metadata Modal Handlers
    // ========================================================================

    /**
     * Handle edit button click - opens modal with role data
     */
    function handleEditClick(btn) {
        const roleId = btn.getAttribute('data-role-id');
        const roleName = btn.getAttribute('data-role-name');
        const roleGroup = btn.getAttribute('data-role-group');
        const displayName = btn.getAttribute('data-display-name');
        const description = btn.getAttribute('data-description');

        console.log('━'.repeat(60));
        console.log('✏️ Edit Metadata - Opening Modal');
        console.log('━'.repeat(60));
        console.log('📌 Role Details:');
        console.log('  ├─ ID:', roleId);
        console.log('  ├─ Name:', roleName);
        console.log('  ├─ Group:', roleGroup);
        console.log('  ├─ Display Name:', displayName || '(not set)');
        console.log('  └─ Description:', description || '(not set)');

        // Store current role being edited
        currentEditingRole = {
            id: roleId,
            name: roleName,
            group: roleGroup,
            display_name: displayName === 'null' || !displayName ? '' : displayName,
            description: description === 'null' || !description ? '' : description
        };

        console.log('💾 Stored in currentEditingRole');
        console.log('🎨 Populating modal fields');

        // Populate modal
        modalRoleId.textContent = `#${roleId}`;
        modalRoleName.textContent = roleName;
        modalRoleGroup.textContent = roleGroup;
        modalDisplayName.value = currentEditingRole.display_name;
        modalDescription.value = currentEditingRole.description;

        // Clear any previous messages
        hideModalMessage();

        console.log('✅ Modal ready');
        console.log('━'.repeat(60));

        // Show modal
        openEditModal();
    }

    function openEditModal() {
        console.log('🎨 Opening edit modal');
        if (editModal) {
            editModal.classList.remove('hidden');
            console.log('  ├─ Modal visible');
            // Focus on first input
            setTimeout(() => {
                if (modalDisplayName) {
                    modalDisplayName.focus();
                    console.log('  └─ Focus set on display_name field');
                }
            }, 100);
        }
    }

    function closeEditModal() {
        console.log('🚪 Closing edit modal');
        if (editModal) {
            editModal.classList.add('hidden');
            console.log('  ├─ Modal hidden');
        }
        currentEditingRole = null;
        console.log('  ├─ Cleared currentEditingRole');

        // Reset form
        if (editForm) {
            editForm.reset();
            console.log('  ├─ Form reset');
        }
        hideModalMessage();
        console.log('  └─ Messages cleared');
    }

    /**
     * Handle metadata update form submission
     */
    async function handleMetadataUpdate(e) {
        e.preventDefault();

        if (!currentEditingRole) {
            console.error('❌ No role being edited');
            return;
        }

        console.log('━'.repeat(60));
        console.log('💾 Metadata Update - Starting');
        console.log('━'.repeat(60));

        // Hide any previous messages
        hideModalMessage();

        // Get form values
        const newDisplayName = modalDisplayName.value.trim();
        const newDescription = modalDescription.value.trim();

        console.log('📝 Current values:');
        console.log('  ├─ display_name:', currentEditingRole.display_name || '(empty)');
        console.log('  └─ description:', currentEditingRole.description || '(empty)');
        console.log('📝 New values:');
        console.log('  ├─ display_name:', newDisplayName || '(empty)');
        console.log('  └─ description:', newDescription || '(empty)');

        // Build request body - only include fields that changed
        const requestBody = {};
        let hasChanges = false;

        if (newDisplayName !== currentEditingRole.display_name) {
            requestBody.display_name = newDisplayName;
            hasChanges = true;
            console.log('✏️ display_name changed');
        }

        if (newDescription !== currentEditingRole.description) {
            requestBody.description = newDescription;
            hasChanges = true;
            console.log('✏️ description changed');
        }

        // Check if there are any changes
        if (!hasChanges) {
            console.log('ℹ️ No changes detected - skipping API call');
            console.log('━'.repeat(60));
            showModalMessage('No changes to save.', 'info');
            return;
        }

        console.log('📤 Sending to: POST /api/roles/' + currentEditingRole.id + '/metadata');
        console.log('📦 Payload:', JSON.stringify(requestBody, null, 2));

        // Disable form during submission
        setModalFormDisabled(true);
        showModalLoadingState();

        try {
            const response = await fetch(`/api/roles/${currentEditingRole.id}/metadata`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestBody)
            });

            console.log('📥 Response status:', response.status, response.statusText);

            // Handle Step-Up required (2FA verification)
            if (response.status === 403) {
                const data = await response.json().catch(() => null);
                console.log('🔐 Step-Up 2FA required');
                console.log('Redirecting to 2FA verification...');
                if (data && data.code === 'STEP_UP_REQUIRED') {
                    const scope = encodeURIComponent(data.scope || 'roles.metadata.update');
                    const returnTo = encodeURIComponent(window.location.pathname);
                    console.log('  ├─ Scope:', data.scope);
                    console.log('  └─ Return to:', window.location.pathname);
                    window.location.href = `/2fa/verify?scope=${scope}&return_to=${returnTo}`;
                    return;
                }
            }

            // Handle 204 No Content (valid but no update needed)
            if (response.status === 204) {
                console.log('ℹ️ 204 No Content - no update needed');
                console.log('━'.repeat(60));
                showModalMessage('No changes were necessary.', 'info');
                setModalFormDisabled(false);
                hideModalLoadingState();
                return;
            }

            // Handle error response
            if (!response.ok) {
                const data = await response.json().catch(() => null);
                const errorMsg = data && data.message ? data.message : 'Failed to update metadata.';
                console.error('❌ Update failed:', errorMsg);
                console.error('Response data:', data);
                console.log('━'.repeat(60));
                showModalMessage(errorMsg, 'error');
                setModalFormDisabled(false);
                hideModalLoadingState();
                return;
            }

            // Success
            console.log('✅ Metadata updated successfully');
            console.log('━'.repeat(60));
            showModalMessage('Metadata updated successfully!', 'success');

            // Wait a moment, then close modal and reload table
            setTimeout(() => {
                closeEditModal();
                loadRoles(); // Reload to show updated data
                showAlert('s', 'Role metadata updated successfully');
            }, 1500);

        } catch (err) {
            console.error('━'.repeat(60));
            console.error('❌ Network error');
            console.error('Error:', err);
            console.error('Stack:', err.stack);
            console.error('━'.repeat(60));
            showModalMessage('Network error. Please try again.', 'error');
            setModalFormDisabled(false);
            hideModalLoadingState();
        }
    }

    // ========================================================================
    // Modal UI Helper Functions
    // ========================================================================

    function showModalMessage(message, type = 'error') {
        if (!modalMessage) return;

        modalMessage.className = 'mb-4 p-4 rounded-lg flex items-start gap-3';

        if (type === 'error') {
            modalMessage.classList.add('bg-red-50', 'border', 'border-red-200');
            modalMessage.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <p class="text-sm text-red-800">${message}</p>
            `;
        } else if (type === 'success') {
            modalMessage.classList.add('bg-green-50', 'border', 'border-green-200');
            modalMessage.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <p class="text-sm text-green-800">${message}</p>
            `;
        } else if (type === 'info') {
            modalMessage.classList.add('bg-blue-50', 'border', 'border-blue-200');
            modalMessage.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <p class="text-sm text-blue-800">${message}</p>
            `;
        }

        modalMessage.classList.remove('hidden');
    }

    function hideModalMessage() {
        if (modalMessage) {
            modalMessage.classList.add('hidden');
            modalMessage.innerHTML = '';
        }
    }

    function setModalFormDisabled(disabled) {
        if (saveMetadataBtn) saveMetadataBtn.disabled = disabled;
        if (modalDisplayName) modalDisplayName.disabled = disabled;
        if (modalDescription) modalDescription.disabled = disabled;
    }

    function showModalLoadingState() {
        if (!saveMetadataBtn) return;

        const originalHTML = saveMetadataBtn.innerHTML;
        saveMetadataBtn.setAttribute('data-original-html', originalHTML);

        saveMetadataBtn.innerHTML = `
            <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Saving...</span>
        `;
    }

    function hideModalLoadingState() {
        if (!saveMetadataBtn) return;

        const originalHTML = saveMetadataBtn.getAttribute('data-original-html');
        if (originalHTML) {
            saveMetadataBtn.innerHTML = originalHTML;
        }
    }

    // ========================================================================
    // Planned Features Handlers (⏳ Not Yet Implemented)
    // ========================================================================

    /**
     * ⏳ Handle Create Role button click
     * POST /api/roles
     */
    function handleCreateRole() {
        console.log('━'.repeat(60));
        console.log('➕ Create Role - Feature Planned');
        console.log('━'.repeat(60));
        console.log('⏳ This feature is not yet implemented');
        console.log('📋 Will create role with:');
        console.log('  ├─ name: technical key (immutable)');
        console.log('  ├─ display_name: optional UI label');
        console.log('  ├─ description: optional help text');
        console.log('  └─ is_active: defaults to true');
        console.log('📡 Endpoint: POST /api/roles');
        console.log('🔐 Permission: roles.create');
        console.log('━'.repeat(60));

        showAlert('w', 'Create Role feature is planned but not yet implemented');
    }

    /**
     * ⏳ Handle Rename Role button click
     * POST /api/roles/{id}/rename
     */
    function handleRenameClick(btn) {
        const roleId = btn.getAttribute('data-role-id');
        const roleName = btn.getAttribute('data-role-name');

        console.log('━'.repeat(60));
        console.log('✏️ Rename Role - Feature Planned');
        console.log('━'.repeat(60));
        console.log('📌 Target Role:');
        console.log('  ├─ ID:', roleId);
        console.log('  └─ Current name:', roleName);
        console.log('⏳ This feature is not yet implemented');
        console.log('⚠️  This is a HIGH-IMPACT operation');
        console.log('📋 Will rename technical key (name)');
        console.log('🔗 Existing bindings remain intact');
        console.log('📡 Endpoint: POST /api/roles/' + roleId + '/rename');
        console.log('🔐 Permission: roles.rename');
        console.log('━'.repeat(60));

        showAlert('w', 'Rename Role feature is planned but not yet implemented');
    }

    /**
     * ⏳ Handle Toggle Role button click
     * POST /api/roles/{id}/toggle
     */
    function handleToggleClick(btn) {
        const roleId = btn.getAttribute('data-role-id');
        const roleName = btn.getAttribute('data-role-name');
        const isActive = btn.getAttribute('data-is-active') === '1';
        const newState = !isActive;

        console.log('━'.repeat(60));
        console.log('🔄 Toggle Role - Feature Planned');
        console.log('━'.repeat(60));
        console.log('📌 Target Role:');
        console.log('  ├─ ID:', roleId);
        console.log('  ├─ Name:', roleName);
        console.log('  ├─ Current state:', isActive ? 'ACTIVE' : 'DISABLED');
        console.log('  └─ New state:', newState ? 'ACTIVE' : 'DISABLED');
        console.log('⏳ This feature is not yet implemented');
        console.log('📋 Will toggle is_active flag');
        console.log('⚠️  Disabled roles are ignored during authorization');
        console.log('✅ No deletion occurs - soft toggle only');
        console.log('📡 Endpoint: POST /api/roles/' + roleId + '/toggle');
        console.log('🔐 Permission: roles.toggle');
        console.log('━'.repeat(60));

        showAlert('w', 'Toggle Role feature is planned but not yet implemented');
    }

    // ========================================================================
    // Helpers
    // ========================================================================
    function showAlert(type, message) {
        if (typeof window.showAlert === 'function') {
            window.showAlert(type, message);
        } else {
            console.log(`[${type}] ${message}`);
        }
    }

    console.log('═'.repeat(60));
    console.log('✅ Roles Management - Ready');
    console.log('📊 Configuration:');
    console.log('  ├─ Table headers:', headers.length, 'columns');
    console.log('  ├─ Table rows:', rows.length, 'fields');
    console.log('  ├─ Capabilities loaded:', Object.keys(capabilities).length);
    console.log('  ├─ Search form:', searchForm ? '✅' : '❌');
    console.log('  ├─ Modal form:', editForm ? '✅' : '❌');
    console.log('  └─ All systems operational');
    console.log('═'.repeat(60));
});