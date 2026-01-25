/**
 * Permissions Page - Permissions Management
 * Controls ALL params structure and handles permission-specific logic
 * Follows Canonical LIST / QUERY Contract (LOCKED)
 *
 * ⚠️ IMPORTANT:
 * - Permission keys (name) are IMMUTABLE
 * - Only display_name and description can be updated
 * - Groups are DERIVED (not stored)
 * - Role assignments are managed separately
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('🔐 Permissions Management - Initializing');

    // ========================================================================
    // Table Configuration
    // ========================================================================
    const headers = ["ID", "Name", "Group", "Display Name", "Description", "Actions"];
    const rows = ["id", "name", "group", "display_name", "description", "actions"];

    // ========================================================================
    // DOM Elements - Search Form
    // ========================================================================
    const searchForm = document.getElementById('permissions-search-form');
    const resetBtn = document.getElementById('btn-reset');
    const inputPermissionId = document.getElementById('filter-permission-id');
    const inputPermissionName = document.getElementById('filter-permission-name');
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
    const modalPermissionId = document.getElementById('modal-permission-id');
    const modalPermissionName = document.getElementById('modal-permission-name');
    const modalPermissionGroup = document.getElementById('modal-permission-group');
    const modalDisplayName = document.getElementById('modal-display-name');
    const modalDescription = document.getElementById('modal-description');

    // ========================================================================
    // State Management
    // ========================================================================
    let currentEditingPermission = null;

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
     * Edit metadata button only
     */
    const actionsRenderer = (value, row) => {
        const permissionId = row.id;
        if (!permissionId) return '<span class="text-gray-400 italic">—</span>';

        return `
            <div class="flex items-center gap-2">
                <button 
                    class="edit-metadata-btn inline-flex items-center gap-1 text-xs px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-200"
                    data-permission-id="${permissionId}"
                    data-permission-name="${row.name || ''}"
                    data-permission-group="${row.group || ''}"
                    data-display-name="${row.display_name || ''}"
                    data-description="${row.description || ''}"
                    title="Edit metadata (display name & description)">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    Edit
                </button>
            </div>
        `;
    };

    // ========================================================================
    // Initialize
    // ========================================================================
    init();

    function init() {
        console.log('🔧 Setting up event listeners');
        loadPermissions(); // ✅ Load data on page load
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
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                console.log('🔍 Search form submitted');
                loadPermissions();
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                console.log('🔄 Resetting filters');
                if (inputPermissionId) inputPermissionId.value = '';
                if (inputPermissionName) inputPermissionName.value = '';
                if (inputGroup) inputGroup.value = '';
                loadPermissions();
            });
        }

        // ✅ Setup click handler for edit buttons (delegated)
        document.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.edit-metadata-btn');
            if (editBtn) {
                handleEditClick(editBtn);
            }
        });
    }

    function setupTableEventListeners() {
        document.addEventListener('tableAction', async (e) => {
            const { action, value, currentParams } = e.detail;
            console.log("📨 Table event:", action, value);

            let newParams = JSON.parse(JSON.stringify(currentParams));

            switch(action) {
                case 'pageChange':
                    newParams.page = value;
                    break;

                case 'perPageChange':
                    newParams.per_page = value;
                    newParams.page = 1;
                    break;
            }

            // Clean empty values
            if (newParams.search) {
                if (!newParams.search.global || !newParams.search.global.trim()) {
                    delete newParams.search.global;
                }

                if (newParams.search.columns) {
                    Object.keys(newParams.search.columns).forEach(key => {
                        if (!newParams.search.columns[key] || !newParams.search.columns[key].toString().trim()) {
                            delete newParams.search.columns[key];
                        }
                    });

                    if (Object.keys(newParams.search.columns).length === 0) {
                        delete newParams.search.columns;
                    }
                }

                if (Object.keys(newParams.search).length === 0) {
                    delete newParams.search;
                }
            }

            await loadPermissionsWithParams(newParams);
        });
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
                    <input id="permissions-global-search" 
                        class="w-full border rounded-lg px-3 py-1 text-sm transition-colors duration-200" 
                        placeholder="Search permissions..." />
                </div>
            </div>
        `;

        const globalSearch = document.getElementById('permissions-global-search');
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
        console.log("🔍 Global search:", searchValue);
        const params = buildParams(1, 25);

        if (searchValue && searchValue.trim()) {
            if (!params.search) {
                params.search = {};
            }
            params.search.global = searchValue.trim();
        }

        loadPermissionsWithParams(params);
    }

    // ========================================================================
    // Params Builder
    // ========================================================================
    function buildParams(pageNumber = 1, perPage = 25) {
        const params = {
            page: pageNumber,
            per_page: perPage
        };

        const searchColumns = {};

        // Build column search
        if (inputPermissionId && inputPermissionId.value.trim()) {
            searchColumns.id = inputPermissionId.value.trim();
        }
        if (inputPermissionName && inputPermissionName.value.trim()) {
            searchColumns.name = inputPermissionName.value.trim();
        }
        if (inputGroup && inputGroup.value.trim()) {
            searchColumns.group = inputGroup.value.trim();
        }

        if (Object.keys(searchColumns).length > 0) {
            params.search = { columns: searchColumns };
        }

        return params;
    }

    // ========================================================================
    // Pagination Info Callback
    // ========================================================================
    function getPermissionsPaginationInfo(pagination, params) {
        console.log("🎯 getPermissionsPaginationInfo called with:", pagination);

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
    // Load Permissions
    // ========================================================================
    async function loadPermissions(pageNumber = 1) {
        const params = buildParams(pageNumber, 25);
        await loadPermissionsWithParams(params);
    }

    async function loadPermissionsWithParams(params) {
        console.log("🚀 Permissions sending:", JSON.stringify(params, null, 2));

        if (typeof createTable === 'function') {
            try {
                const result = await createTable(
                    "permissions/query",
                    params,
                    headers,
                    rows,
                    false, // ✅ No selection for permissions (read-only list)
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
                    getPermissionsPaginationInfo
                );

                if (result && result.success) {
                    console.log("✅ Permissions loaded:", result.data.length);
                    console.log("📊 Pagination:", result.pagination);
                    setupTableFiltersAfterRender();
                }
            } catch (error) {
                console.error("❌ Error:", error);
                showAlert('d', 'Failed to load permissions');
            }
        } else {
            console.error("❌ createTable not found");
        }
    }

    // ========================================================================
    // Edit Metadata Modal Handlers
    // ========================================================================

    /**
     * Handle edit button click - opens modal with permission data
     */
    function handleEditClick(btn) {
        const permissionId = btn.getAttribute('data-permission-id');
        const permissionName = btn.getAttribute('data-permission-name');
        const permissionGroup = btn.getAttribute('data-permission-group');
        const displayName = btn.getAttribute('data-display-name');
        const description = btn.getAttribute('data-description');

        console.log('✏️ Edit clicked for permission:', permissionId);

        // Store current permission being edited
        currentEditingPermission = {
            id: permissionId,
            name: permissionName,
            group: permissionGroup,
            display_name: displayName === 'null' || !displayName ? '' : displayName,
            description: description === 'null' || !description ? '' : description
        };

        // Populate modal
        modalPermissionId.textContent = `#${permissionId}`;
        modalPermissionName.textContent = permissionName;
        modalPermissionGroup.textContent = permissionGroup;
        modalDisplayName.value = currentEditingPermission.display_name;
        modalDescription.value = currentEditingPermission.description;

        // Clear any previous messages
        hideModalMessage();

        // Show modal
        openEditModal();
    }

    function openEditModal() {
        if (editModal) {
            editModal.classList.remove('hidden');
            // Focus on first input
            setTimeout(() => {
                if (modalDisplayName) modalDisplayName.focus();
            }, 100);
        }
    }

    function closeEditModal() {
        if (editModal) {
            editModal.classList.add('hidden');
        }
        currentEditingPermission = null;

        // Reset form
        if (editForm) editForm.reset();
        hideModalMessage();
    }

    /**
     * Handle metadata update form submission
     */
    async function handleMetadataUpdate(e) {
        e.preventDefault();

        if (!currentEditingPermission) {
            console.error('❌ No permission being edited');
            return;
        }

        // Hide any previous messages
        hideModalMessage();

        // Get form values
        const newDisplayName = modalDisplayName.value.trim();
        const newDescription = modalDescription.value.trim();

        console.log('💾 Saving metadata:', {
            id: currentEditingPermission.id,
            display_name: newDisplayName,
            description: newDescription
        });

        // Build request body - only include fields that changed
        const requestBody = {};
        let hasChanges = false;

        if (newDisplayName !== currentEditingPermission.display_name) {
            requestBody.display_name = newDisplayName;
            hasChanges = true;
        }

        if (newDescription !== currentEditingPermission.description) {
            requestBody.description = newDescription;
            hasChanges = true;
        }

        // Check if there are any changes
        if (!hasChanges) {
            showModalMessage('No changes to save.', 'info');
            return;
        }

        // Disable form during submission
        setModalFormDisabled(true);
        showModalLoadingState();

        try {
            const response = await fetch(`/api/permissions/${currentEditingPermission.id}/metadata`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestBody)
            });

            // Handle Step-Up required (2FA verification)
            if (response.status === 403) {
                const data = await response.json().catch(() => null);
                if (data && data.code === 'STEP_UP_REQUIRED') {
                    const scope = encodeURIComponent(data.scope || 'permissions.metadata.update');
                    const returnTo = encodeURIComponent(window.location.pathname);
                    window.location.href = `/2fa/verify?scope=${scope}&return_to=${returnTo}`;
                    return;
                }
            }

            // Handle 204 No Content (valid but no update needed)
            if (response.status === 204) {
                showModalMessage('No changes were necessary.', 'info');
                setModalFormDisabled(false);
                hideModalLoadingState();
                return;
            }

            // Handle error response
            if (!response.ok) {
                const data = await response.json().catch(() => null);
                const errorMsg = data && data.message ? data.message : 'Failed to update metadata.';
                showModalMessage(errorMsg, 'error');
                setModalFormDisabled(false);
                hideModalLoadingState();
                return;
            }

            // Success
            console.log('✅ Metadata updated successfully');
            showModalMessage('Metadata updated successfully!', 'success');

            // Wait a moment, then close modal and reload table
            setTimeout(() => {
                closeEditModal();
                loadPermissions(); // Reload to show updated data
                showAlert('s', 'Permission metadata updated successfully');
            }, 1500);

        } catch (err) {
            console.error('❌ Network error:', err);
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
    // Helpers
    // ========================================================================
    function showAlert(type, message) {
        if (typeof window.showAlert === 'function') {
            window.showAlert(type, message);
        } else {
            console.log(`[${type}] ${message}`);
        }
    }

    console.log('✅ Permissions Management - Ready');
});