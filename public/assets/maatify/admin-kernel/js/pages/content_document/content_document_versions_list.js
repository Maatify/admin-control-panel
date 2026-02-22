/**
 * Content Document Versions List
 *
 * Manages the UI for listing, creating, and managing content document versions.
 *
 * 🔐 AUTHORIZATION:
 * - Uses `window.contentDocumentVersionsCapabilities` for permission checks.
 * - Uses `window.contentDocumentVersionsApi` for API endpoints.
 * - Uses `window.typeId` for scoping.
 */

document.addEventListener('DOMContentLoaded', () => {
    // ========================================================================
    // 1. Initialization & Capabilities
    // ========================================================================

    const capabilities = window.contentDocumentVersionsCapabilities || {};
    const apiEndpoints = window.contentDocumentVersionsApi || {};
    const typeId = window.typeId;
    const tableContainerId = 'content-document-versions-table-container';

    if (!typeId) {
        console.error("❌ Missing typeId in window context.");
        return;
    }

    console.log("🚀 Content Document Versions Initialized", {
        typeId,
        capabilities,
        apiEndpoints
    });

    // ========================================================================
    // 2. Table Configuration
    // ========================================================================

    // Define headers and rows
    const headers = [
        "ID",
        "Version",
        "Status",
        "Requires Acceptance",
        "Published At",
        "Created At",
        "Actions"
    ];

    const rows = [
        "id",
        "version",
        "status",
        "requires_acceptance",
        "published_at",
        "created_at",
        "actions"
    ];

    // ========================================================================
    // 3. Custom Renderers
    // ========================================================================

    const renderers = {
        // ID Renderer
        id: (value, row) => {
            return `<span class="font-mono text-sm text-gray-800 dark:text-gray-300 font-medium">#${value}</span>`;
        },

        // Version Renderer
        version: (value, row) => {
            return `<span class="font-mono text-sm font-semibold text-blue-600 dark:text-blue-400">${value}</span>`;
        },

        // Status Renderer
        status: (value, row) => {
            let status = 'draft';
            if (row.archived_at) {
                status = 'archived';
            } else if (row.is_active) {
                status = 'active';
            } else if (row.published_at) {
                status = 'inactive';
            }

            const styles = {
                draft: "bg-gray-100 text-gray-800 border-gray-200",
                inactive: "bg-blue-100 text-blue-800 border-blue-200",
                active: "bg-green-100 text-green-800 border-green-200",
                archived: "bg-red-100 text-red-800 border-red-200"
            };

            const style = styles[status] || styles.draft;
            const label = status.charAt(0).toUpperCase() + status.slice(1);

            return `<span class="px-2.5 py-0.5 rounded-full text-xs font-medium border ${style}">
                ${label}
            </span>`;
        },

        // Requires Acceptance Renderer
        requires_acceptance: (value, row) => {
            return value
                ? `<span class="text-green-600 dark:text-green-400 font-medium text-xs">Yes</span>`
                : `<span class="text-gray-400 dark:text-gray-500 text-xs">No</span>`;
        },

        // Date Renderers
        published_at: (value, row) => {
            if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic text-xs">-</span>';
            return `<span class="text-xs text-gray-600 dark:text-gray-400">${value}</span>`;
        },
        created_at: (value, row) => {
            if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic text-xs">-</span>';
            return `<span class="text-xs text-gray-600 dark:text-gray-400">${value}</span>`;
        },

        // Actions Renderer
        actions: (value, row) => {
            const buttons = [];
            const id = row.id;

            // Determine status for logic
            let status = 'draft';
            if (row.archived_at) status = 'archived';
            else if (row.is_active) status = 'active';
            else if (row.published_at) status = 'inactive';

            // 1. Translations (View/Edit)
            if (capabilities.can_view_translations) {
                // Assuming UI URL follows pattern: /content-document-types/{type_id}/versions/{document_id}/translations
                const uiUrl = `/content-document-types/${typeId}/versions/${id}/translations`;

                buttons.push(window.AdminUIComponents.buildActionButton({
                    cssClass: '',
                    icon: window.AdminUIComponents.SVGIcons.view,
                    text: 'Translations',
                    color: 'gray',
                    entityId: id,
                    title: 'Manage Translations',
                    dataAttributes: { href: uiUrl } 
                }).replace('<button', `<a href="${uiUrl}"`).replace('</button>', '</a>'));
            }

            // 2. Publish
            if (capabilities.can_publish && status === 'draft') {
                buttons.push(window.AdminUIComponents.buildActionButton({
                    cssClass: 'btn-publish',
                    icon: window.AdminUIComponents.SVGIcons.check,
                    text: 'Publish',
                    color: 'blue',
                    entityId: id,
                    title: 'Publish',
                    dataAttributes: { version: row.version }
                }));
            }

            // 3. Activate
            if (capabilities.can_activate && status === 'inactive') {
                buttons.push(window.AdminUIComponents.buildActionButton({
                    cssClass: 'btn-activate',
                    icon: window.AdminUIComponents.SVGIcons.check,
                    text: 'Activate',
                    color: 'green',
                    entityId: id,
                    title: 'Activate',
                    dataAttributes: { version: row.version }
                }));
            }

            // 4. Deactivate
            if (capabilities.can_deactivate && status === 'active') {
                buttons.push(window.AdminUIComponents.buildActionButton({
                    cssClass: 'btn-deactivate',
                    icon: window.AdminUIComponents.SVGIcons.x,
                    text: 'Deactivate',
                    color: 'amber',
                    entityId: id,
                    title: 'Deactivate',
                    dataAttributes: { version: row.version }
                }));
            }

            // 5. Archive
            if (capabilities.can_archive && status !== 'archived') {
                buttons.push(window.AdminUIComponents.buildActionButton({
                    cssClass: 'btn-archive',
                    icon: window.AdminUIComponents.SVGIcons.delete,
                    text: 'Archive',
                    color: 'red',
                    entityId: id,
                    title: 'Archive',
                    dataAttributes: { version: row.version }
                }));
            }

            return `<div class="flex items-center gap-2 justify-end">${buttons.join('')}</div>`;
        }
    };

    // ========================================================================
    // 4. Filter & Search Logic
    // ========================================================================

    const filterForm = document.getElementById('content-document-versions-filter-form');
    const resetBtn = document.getElementById('content-document-versions-reset-filters');
    const searchInput = document.getElementById('content-document-versions-search');
    const searchBtn = document.getElementById('content-document-versions-search-btn');
    const clearSearchBtn = document.getElementById('content-document-versions-clear-search');

    // Filter Inputs
    const inputDocumentId = document.getElementById('filter-document-id');
    const inputVersion = document.getElementById('filter-version');
    const inputStatus = document.getElementById('filter-status');
    const inputRequiresAcceptance = document.getElementById('filter-requires-acceptance');
    const inputCreatedFrom = document.getElementById('filter-created-from');
    const inputCreatedTo = document.getElementById('filter-created-to');

    let currentPage = 1;
    let currentPerPage = 25;

    function buildParams(page = 1, perPage = 25) {
        const params = {
            page: page,
            per_page: perPage,
            search: {
                columns: {}
            }
        };

        // Global Search
        if (searchInput && searchInput.value.trim()) {
            params.search.global = searchInput.value.trim();
        }

        // Column Filters
        if (inputDocumentId && inputDocumentId.value.trim()) {
            params.search.columns.document_id = inputDocumentId.value.trim();
        }
        if (inputVersion && inputVersion.value.trim()) {
            params.search.columns.version = inputVersion.value.trim();
        }
        if (inputStatus && inputStatus.value) {
            params.search.columns.status = inputStatus.value;
        }
        if (inputRequiresAcceptance && inputRequiresAcceptance.value) {
            params.search.columns.requires_acceptance = inputRequiresAcceptance.value;
        }
        if (inputCreatedFrom && inputCreatedFrom.value) {
            params.search.columns.created_from = inputCreatedFrom.value;
        }
        if (inputCreatedTo && inputCreatedTo.value) {
            params.search.columns.created_to = inputCreatedTo.value;
        }

        // Cleanup empty objects
        if (Object.keys(params.search.columns).length === 0) {
            delete params.search.columns;
        }
        if (Object.keys(params.search).length === 0) {
            delete params.search;
        }

        return params;
    }

    async function loadVersions(page = 1) {
        currentPage = page;
        const params = buildParams(page, currentPerPage);
        const endpoint = apiEndpoints.query.replace('{type_id}', typeId);

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

        if (typeof createTable === 'function') {
            await createTable(
                endpoint,
                params,
                headers,
                rows,
                false, // no checkboxes
                'id',
                null,
                renderers,
                null,
                null // default pagination info
            ).then(() => {
                container.id = originalContainerId;
                if (tempId && originalTableContainer) originalTableContainer.id = 'table-container';
            }).catch(err => {
                console.error("Table creation failed", err);
                container.id = originalContainerId;
                if (tempId && originalTableContainer) originalTableContainer.id = 'table-container';
            });
        } else {
            console.error("❌ createTable function not found.");
            container.id = originalContainerId;
            if (tempId && originalTableContainer) originalTableContainer.id = 'table-container';
        }
    }

    // Event Listeners for Filters
    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            loadVersions(1);
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            filterForm.reset();
            // Manually clear flatpickr inputs if needed
            if (inputCreatedFrom && inputCreatedFrom._flatpickr) inputCreatedFrom._flatpickr.clear();
            if (inputCreatedTo && inputCreatedTo._flatpickr) inputCreatedTo._flatpickr.clear();
            if (searchInput) searchInput.value = ''; // Also clear global search
            loadVersions(1);
        });
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', () => loadVersions(1));
    }

    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                loadVersions(1);
            }
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            loadVersions(1);
        });
    }

    // Table Events
    document.addEventListener('tableAction', (e) => {
        const { action, value } = e.detail;
        if (action === 'pageChange') {
            currentPage = value;
            loadVersions(currentPage);
        } else if (action === 'perPageChange') {
            currentPerPage = value;
            currentPage = 1;
            loadVersions(1);
        }
    });

    // ========================================================================
    // 5. Create Modal Logic
    // ========================================================================

    const btnCreate = document.getElementById('btn-create-content-document-version');
    if (btnCreate) {
        btnCreate.addEventListener('click', () => {
            openCreateModal();
        });
    }

    function openCreateModal() {
        const modalId = 'create-version-modal';
        
        // Remove existing if any
        const existing = document.getElementById(modalId);
        if (existing) existing.remove();

        const content = `
            <form id="create-version-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Version Number <span class="text-red-500">*</span></label>
                    <input type="text" name="version" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                           placeholder="e.g., 1.0.0">
                    <p class="text-xs text-gray-500 mt-1">Must be unique for this document type.</p>
                </div>
                
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="create-requires-acceptance" name="requires_acceptance" 
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="create-requires-acceptance" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Requires Acceptance
                    </label>
                </div>
            </form>
        `;

        const footer = window.AdminUIComponents.buildModalFooter({
            submitText: 'Create Version',
            submitColor: 'green'
        });

        const modalHtml = window.AdminUIComponents.buildModalTemplate({
            id: modalId,
            title: 'Create New Version',
            icon: window.AdminUIComponents.SVGIcons.plus,
            content: content,
            footer: footer
        });

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = document.getElementById(modalId);
        modal.classList.remove('hidden');

        // Handle Close
        const closeBtns = modal.querySelectorAll('.close-modal');
        closeBtns.forEach(btn => btn.addEventListener('click', () => modal.remove()));

        // Handle Submit
        const form = modal.querySelector('form');
        const submitBtn = modal.querySelector('button[type="submit"]');

        submitBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            
            const version = form.version.value.trim();
            const requiresAcceptance = form.requires_acceptance.checked;

            if (!version) {
                alert('Version is required');
                return;
            }

            const payload = {
                version: version,
                requires_acceptance: requiresAcceptance
            };

            try {
                const endpoint = apiEndpoints.create.replace('{type_id}', typeId);
                const response = await ApiHandler.call(endpoint, payload, 'Create Version');
                
                if (response && response.success) {
                    modal.remove();
                    loadVersions(1);
                    ApiHandler.showAlert('success', 'Version created successfully');
                } else {
                    ApiHandler.showAlert('danger', response.error || 'Failed to create version');
                }
            } catch (error) {
                console.error("Create failed", error);
                ApiHandler.showAlert('danger', 'An error occurred while creating version');
            }
        });
    }

    // ========================================================================
    // 6. Action Handlers (Publish, Activate, Deactivate, Archive)
    // ========================================================================

    // Delegate events for dynamic table buttons
    // Using event delegation as per best practices
    document.addEventListener('click', async (e) => {
        const target = e.target.closest('button');
        if (!target) return;

        // Check if it's one of our action buttons
        if (!target.classList.contains('btn-publish') && 
            !target.classList.contains('btn-activate') && 
            !target.classList.contains('btn-deactivate') && 
            !target.classList.contains('btn-archive')) {
            return;
        }

        const id = target.dataset.entityId; // AdminUIComponents uses data-entity-id
        const version = target.dataset.version;

        if (target.classList.contains('btn-publish')) {
            await handleAction('publish', id, version, 'Publish', 'blue');
        } else if (target.classList.contains('btn-activate')) {
            await handleAction('activate', id, version, 'Activate', 'green');
        } else if (target.classList.contains('btn-deactivate')) {
            await handleAction('deactivate', id, version, 'Deactivate', 'amber');
        } else if (target.classList.contains('btn-archive')) {
            await handleAction('archive', id, version, 'Archive', 'red');
        }
    });

    async function handleAction(action, id, version, actionName, color) {
        const confirmed = await window.AdminUIComponents.showConfirmation({
            title: `${actionName} Version`,
            message: `Are you sure you want to ${actionName.toLowerCase()} version "${version}"?`,
            confirmText: actionName,
            type: color === 'red' ? 'danger' : 'warning'
        });

        if (!confirmed) return;

        try {
            const endpoint = apiEndpoints[action]
                .replace('{type_id}', typeId)
                .replace('{document_id}', id);

            const response = await ApiHandler.call(endpoint, {}, `${actionName} Version`);

            if (response && response.success) { 
                loadVersions(currentPage); // Reload table to reflect state change
                ApiHandler.showAlert('success', `Version ${actionName.toLowerCase()}d successfully`);
            } else {
                ApiHandler.showAlert('danger', response.error || `Failed to ${actionName.toLowerCase()} version`);
            }
        } catch (error) {
            console.error(`${actionName} failed`, error);
            ApiHandler.showAlert('danger', `An error occurred while trying to ${actionName.toLowerCase()} version`);
        }
    }

    // ========================================================================
    // 7. Initial Load
    // ========================================================================

    loadVersions();
});
