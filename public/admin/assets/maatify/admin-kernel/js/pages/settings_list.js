/**
 * Settings Management
 * Handles listing and updating system settings
 */

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
}[char]));

document.addEventListener('DOMContentLoaded', () => {
    // ============================================================
    // 1. Initialization & State
    // ============================================================
    const tableContainerId = 'settings-table-container';
    const filterForm = document.getElementById('settings-filter-form');
    const globalSearchInput = document.getElementById('settings-search');
    const globalSearchBtn = document.getElementById('settings-search-btn');
    const globalClearBtn = document.getElementById('settings-clear-search');
    const resetFiltersBtn = document.getElementById('settings-reset-filters');

    // ============================================================
    // 2. Data Table Configuration
    // ============================================================

    const headers = ['Key', 'Admin Note', 'Value', 'Type', 'Actions'];
    const rowKeys = ['setting_key', 'admin_note', 'setting_value', 'value_type', 'actions'];

    // Custom Renderers
    const customRenderers = {
        setting_key: (value) => {
            const safeValue = escapeHtml(value);
            return `<span class="font-mono text-sm text-blue-600 dark:text-blue-400">${safeValue}</span>`;
        },
        admin_note: (value) => {
            const safeValue = escapeHtml(value || '-');
            return `<span class="text-gray-800 dark:text-gray-200">${safeValue}</span>`;
        },
        setting_value: (value) => {
            const safeValue = escapeHtml(value);
            return `<span class="font-mono text-sm text-gray-700 dark:text-gray-300 truncate max-w-sm block" title="${safeValue}">${safeValue}</span>`;
        },
        value_type: (value) => {
            const safeValue = escapeHtml(value || 'string');
            return `<span class="text-xs font-mono text-gray-500 dark:text-gray-400 uppercase bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">${safeValue}</span>`;
        },
        actions: (_, row) => {
            if (!window.settingsCapabilities.can_edit) return '';

            return window.AdminUIComponents.buildActionButton({
                icon: window.AdminUIComponents.SVGIcons.edit,
                text: 'Edit',
                color: 'blue',
                title: 'Edit Setting',
                dataAttributes: {
                    key: row.setting_key,
                    value: encodeURIComponent(row.setting_value),
                    type: row.value_type || 'string',
                    adminNote: row.admin_note || ''
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
            key: document.getElementById('filter-key').value,
            admin_note: document.getElementById('filter-admin-note').value,
            value_type: document.getElementById('filter-value-type').value
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
            window.settingsApi.query,
            params,
            headers,
            rowKeys,
            false, // withSelection
            'setting_key', // primaryKey
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
    // 4. Actions (Edit)
    // ============================================================

    // --- Edit Modal ---
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.btn-edit-setting');
        if (editBtn) {
            const key = editBtn.dataset.key;
            const value = decodeURIComponent(editBtn.dataset.value);
            const type = editBtn.dataset.type || 'string';
            const adminNote = editBtn.dataset.adminNote || key;
            openEditModal(key, value, type, adminNote);
        }
    });

    window.openEditModal = (key, value, type, adminNote) => {
        const safeKey = escapeHtml(key);
        const safeValue = escapeHtml(value);
        const safeType = escapeHtml(type);
        const safeAdminNote = escapeHtml(adminNote);

        const modalContent = `
            <div class="p-6">
                <form id="edit-setting-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Key</label>
                        <input type="text" value="${safeKey}" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 dark:text-gray-400 cursor-not-allowed" disabled>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Admin Note</label>
                        <input type="text" value="${safeAdminNote}" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 dark:text-gray-400 cursor-not-allowed" disabled>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Type</label>
                        <input type="text" value="${safeType}" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 dark:text-gray-400 cursor-not-allowed" disabled>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Value</label>
                        <textarea id="edit-value" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all dark:placeholder-gray-400" rows="5" required>${safeValue}</textarea>
                    </div>
                </form>
            </div>
        `;

        const modalId = 'edit-setting-modal';
        const modalHtml = window.AdminUIComponents.buildModalTemplate({
            id: modalId,
            title: `Edit Setting: ${key}`,
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

            valueInput.classList.remove('border-red-500');

            try {
                const result = await ApiHandler.call(window.settingsApi.update, {
                    setting_key: key,
                    value: newValue
                });
                if (result.success) {
                    ApiHandler.showAlert('success', 'Setting updated successfully');
                    loadTable();
                    modalEl.remove();
                } else {
                    ApiHandler.showAlert('danger', result.error || 'Failed to update setting');
                }
            } catch (error) {
                console.error('Update failed:', error);
                ApiHandler.showAlert('danger', 'An error occurred while updating the setting');
            }
        });

        // Handle Close
        const closeBtns = modalEl.querySelectorAll('.close-modal');
        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => modalEl.remove());
        });

        // Focus on value input
        const valueInput = document.getElementById('edit-value');
        valueInput.focus();
        valueInput.select();
    };

    // Initial Load
    loadTable();
});
