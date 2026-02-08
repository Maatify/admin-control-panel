/**
 * I18n Scopes Management
 * Matches languages-with-components.js pattern
 */
(function() {
    'use strict';

    let currentPage = 1;
    let currentPerPage = 25;

    const headers = ['ID', 'Code', 'Name', 'Description', 'Active', 'Order', 'Actions'];
    const rowNames = ['id', 'code', 'name', 'description', 'is_active', 'sort_order', 'actions'];
    const capabilities = window.i18nScopesCapabilities || {};

    // ========================================================================
    // Renderers
    // ========================================================================

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function idRenderer(value) {
        if (capabilities.can_view_scope_details) {
            return `<a href="/i18n/scopes/${value}" class="text-blue-600 hover:text-blue-800 hover:underline font-mono">${value}</a>`;
        }
        return `<span class="font-mono text-gray-500">${value}</span>`;
    }

    function nameRenderer(value) {
        return `<span class="font-medium text-gray-900 dark:text-gray-100">${escapeHtml(value)}</span>`;
    }

    function codeRenderer(value) {
        if (window.AdminUIComponents && AdminUIComponents.renderCodeBadge) {
            return AdminUIComponents.renderCodeBadge(escapeHtml(value));
        }
        return `<code class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-mono border border-gray-200 dark:border-gray-600">${escapeHtml(value)}</code>`;
    }

    function statusRenderer(value) {
        const isActive = parseInt(value) === 1;
        if (window.AdminUIComponents && AdminUIComponents.renderStatusBadge) {
            return AdminUIComponents.renderStatusBadge(isActive ? 'Active' : 'Inactive');
        }
        return isActive ? 'Active' : 'Inactive';
    }

    function sortRenderer(value) {
        if (window.AdminUIComponents && AdminUIComponents.renderSortBadge) {
            return AdminUIComponents.renderSortBadge(value);
        }
        return value;
    }

    function actionsRenderer(value, row) {
        const actions = [];
        const id = row.id;

        if (capabilities.can_change_code) {
            actions.push(AdminUIComponents.buildActionButton({
                type: 'warning',
                label: 'Code',
                icon: AdminUIComponents.SVGIcons?.edit || '✏️',
                onClick: `window.ScopesActions.openChangeCodeModal('${id}')`
            }));
        }

        if (capabilities.can_update_meta) {
            actions.push(AdminUIComponents.buildActionButton({
                type: 'primary',
                label: 'Meta',
                icon: AdminUIComponents.SVGIcons?.edit || '📝',
                onClick: `window.ScopesActions.openUpdateMetadataModal('${id}')`
            }));
        }

        if (capabilities.can_update_sort) {
            actions.push(AdminUIComponents.buildActionButton({
                type: 'secondary',
                label: 'Sort',
                icon: AdminUIComponents.SVGIcons?.sort || '⇅',
                onClick: `window.ScopesActions.openUpdateSortModal('${id}')`
            }));
        }

        if (capabilities.can_set_active) {
            const isActive = parseInt(row.is_active) === 1;
            actions.push(AdminUIComponents.buildActionButton({
                type: isActive ? 'danger' : 'success',
                label: isActive ? 'Deactivate' : 'Activate',
                icon: isActive ? (AdminUIComponents.SVGIcons?.x || '✕') : (AdminUIComponents.SVGIcons?.check || '✓'),
                onClick: `window.ScopesActions.toggleActive('${id}', ${isActive})`
            }));
        }

        if (actions.length === 0) return '<span class="text-gray-400 text-xs">No actions</span>';
        return `<div class="flex flex-wrap gap-2">${actions.join('')}</div>`;
    }

    // ========================================================================
    // Data Loading
    // ========================================================================

    function buildQueryParams() {
        const params = { page: currentPage, per_page: currentPerPage };
        const globalSearch = document.getElementById('scopes-search')?.value?.trim();
        const columnFilters = {};

        const filterId = document.getElementById('filter-id')?.value?.trim();
        if (filterId) columnFilters.id = filterId;

        const filterName = document.getElementById('filter-name')?.value?.trim();
        if (filterName) columnFilters.name = filterName;

        const filterCode = document.getElementById('filter-code')?.value?.trim();
        if (filterCode) columnFilters.code = filterCode;

        const filterStatus = document.getElementById('filter-status')?.value;
        if (filterStatus) columnFilters.is_active = filterStatus;

        if (globalSearch || Object.keys(columnFilters).length > 0) {
            params.search = {};
            if (globalSearch) params.search.global = globalSearch;
            if (Object.keys(columnFilters).length > 0) params.search.columns = columnFilters;
        }

        return params;
    }

    async function loadScopes(pageNumber = null, perPageNumber = null) {
        if (pageNumber !== null) currentPage = pageNumber;
        if (perPageNumber !== null) currentPerPage = perPageNumber;

        const params = buildQueryParams();
        const result = await ApiHandler.call('i18n/scopes/query', params, 'Query Scopes');

        if (!result.success) {
            document.getElementById('table-container').innerHTML = `<div class="p-8 text-center text-red-600">❌ ${result.error || 'Failed to load data'}</div>`;
            return;
        }

        const data = result.data || {};
        const items = Array.isArray(data.data) ? data.data : [];
        const paginationInfo = data.pagination || {
            page: params.page || 1,
            per_page: params.per_page || 25,
            total: items.length
        };

        if (typeof TableComponent === 'function') {
            TableComponent(
                items, headers, rowNames, paginationInfo, "", false, 'id', null,
                {
                    id: idRenderer,
                    name: nameRenderer,
                    code: codeRenderer,
                    is_active: statusRenderer,
                    sort_order: sortRenderer,
                    actions: actionsRenderer
                },
                null,
                (pagination) => ({
                    start: (pagination.page - 1) * pagination.per_page + 1,
                    end: Math.min(pagination.page * pagination.per_page, pagination.total),
                    total: pagination.total,
                    filtered: pagination.filtered
                })
            );
        } else {
            console.error('❌ TableComponent not found');
        }
    }

    async function fetchScopeDetails(id) {
        const result = await ApiHandler.call('i18n/scopes/query', {
            page: 1,
            per_page: 1,
            search: { columns: { id: id } }
        }, 'Fetch Scope');

        if (result.success && result.data.data.length > 0) {
            return result.data.data[0];
        }
        ApiHandler.showAlert('danger', 'Failed to fetch scope details');
        return null;
    }

    // ========================================================================
    // Actions & Handlers
    // ========================================================================

    function setupCreateScopeForm() {
        const form = document.getElementById('create-scope-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Clear previous errors
            form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
            form.querySelectorAll('.field-error').forEach(el => el.remove());

            const payload = {
                code: document.getElementById('create-code').value.trim(),
                name: document.getElementById('create-name').value.trim(),
                description: document.getElementById('create-description').value.trim(),
                is_active: document.getElementById('create-active').checked
            };
            const result = await ApiHandler.call('i18n/scopes/create', payload, 'Create Scope');
            if (result.success) {
                ApiHandler.showAlert('success', 'Scope created successfully');
                document.getElementById('create-scope-modal').classList.add('hidden');
                form.reset();
                loadScopes();
            } else {
                if (result.data && result.data.errors) {
                    ApiHandler.showFieldErrors(result.data.errors, 'create-scope-form');
                }
            }
        });
    }

    async function openChangeCodeModal(id) {
        const scope = await fetchScopeDetails(id);
        if (!scope) return;

        document.getElementById('code-scope-id').value = scope.id;
        document.getElementById('code-new-code').value = scope.code;
        document.getElementById('change-code-modal').classList.remove('hidden');
    }

    function setupChangeCodeForm() {
        const form = document.getElementById('change-code-form');
        if (!form) return;
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
            form.querySelectorAll('.field-error').forEach(el => el.remove());

            const result = await ApiHandler.call('i18n/scopes/change-code', {
                id: parseInt(document.getElementById('code-scope-id').value),
                new_code: document.getElementById('code-new-code').value.trim()
            }, 'Change Code');

            if (result.success) {
                ApiHandler.showAlert('success', 'Scope code updated successfully');
                document.getElementById('change-code-modal').classList.add('hidden');
                form.reset();
                loadScopes();
            } else if (result.data && result.data.errors) {
                ApiHandler.showFieldErrors(result.data.errors, 'change-code-form');
            }
        });
    }

    async function openUpdateSortModal(id) {
        const scope = await fetchScopeDetails(id);
        if (!scope) return;

        document.getElementById('sort-scope-id').value = scope.id;
        document.getElementById('sort-scope-name').textContent = scope.name;
        document.getElementById('sort-current-order').textContent = scope.sort_order;
        document.getElementById('sort-new-order').value = scope.sort_order;
        document.getElementById('update-sort-modal').classList.remove('hidden');
    }

    function setupUpdateSortForm() {
        const form = document.getElementById('update-sort-form');
        if (!form) return;
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
            form.querySelectorAll('.field-error').forEach(el => el.remove());

            const result = await ApiHandler.call('i18n/scopes/update-sort', {
                id: parseInt(document.getElementById('sort-scope-id').value),
                position: parseInt(document.getElementById('sort-new-order').value)
            }, 'Update Sort');

            if (result.success) {
                ApiHandler.showAlert('success', 'Sort order updated successfully');
                document.getElementById('update-sort-modal').classList.add('hidden');
                form.reset();
                loadScopes();
            } else if (result.data && result.data.errors) {
                ApiHandler.showFieldErrors(result.data.errors, 'update-sort-form');
            }
        });
    }

    async function openUpdateMetadataModal(id) {
        const scope = await fetchScopeDetails(id);
        if (!scope) return;

        document.getElementById('meta-scope-id').value = scope.id;
        document.getElementById('meta-name').value = scope.name;
        document.getElementById('meta-description').value = scope.description || '';
        document.getElementById('update-metadata-modal').classList.remove('hidden');
    }

    function setupUpdateMetadataForm() {
        const form = document.getElementById('update-metadata-form');
        if (!form) return;
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
            form.querySelectorAll('.field-error').forEach(el => el.remove());

            const payload = {
                id: parseInt(document.getElementById('meta-scope-id').value),
                name: document.getElementById('meta-name').value.trim(),
                description: document.getElementById('meta-description').value.trim()
            };
            const result = await ApiHandler.call('i18n/scopes/update-metadata', payload, 'Update Metadata');

            if (result.success) {
                ApiHandler.showAlert('success', 'Metadata updated successfully');
                document.getElementById('update-metadata-modal').classList.add('hidden');
                form.reset();
                loadScopes();
            } else if (result.data && result.data.errors) {
                ApiHandler.showFieldErrors(result.data.errors, 'update-metadata-form');
            }
        });
    }

    async function toggleActive(id, currentStatus) {
        if (!confirm(`Are you sure you want to ${currentStatus ? 'deactivate' : 'activate'} this scope?`)) return;
        const result = await ApiHandler.call('i18n/scopes/set-active', {
            id: parseInt(id),
            is_active: !currentStatus
        }, 'Toggle Active');

        if (result.success) {
            ApiHandler.showAlert('success', `Scope ${!currentStatus ? 'activated' : 'deactivated'} successfully`);
            loadScopes();
        }
    }

    // ========================================================================
    // Initialization
    // ========================================================================

    function init() {
        // Setup Form Handlers
        setupCreateScopeForm();
        setupChangeCodeForm();
        setupUpdateSortForm();
        setupUpdateMetadataForm();

        // Close Modals
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = e.target.closest('.fixed');
                if (modal) modal.classList.add('hidden');
            });
        });

        // Search & Filters
        document.getElementById('scopes-search-btn')?.addEventListener('click', () => loadScopes(1));
        document.getElementById('scopes-clear-search')?.addEventListener('click', () => {
            document.getElementById('scopes-search').value = '';
            loadScopes(1);
        });
        document.getElementById('scopes-search')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') loadScopes(1);
        });
        document.getElementById('scopes-filter-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            loadScopes(1);
        });
        document.getElementById('scopes-reset-filters')?.addEventListener('click', () => {
            document.getElementById('scopes-filter-form').reset();
            loadScopes(1);
        });

        // Create Button
        const btnCreate = document.getElementById('btn-create-scope');
        if (btnCreate) {
            btnCreate.addEventListener('click', () => {
                document.getElementById('create-scope-modal').classList.remove('hidden');
            });
        }

        // Global Exports
        window.ScopesActions = { openChangeCodeModal, openUpdateSortModal, openUpdateMetadataModal, toggleActive };
        window.changePage = (page) => loadScopes(page, currentPerPage);
        window.changePerPage = (perPage) => loadScopes(1, perPage);

        loadScopes();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
