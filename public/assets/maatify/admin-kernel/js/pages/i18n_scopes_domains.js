/**
 * I18n Scope Domains Management
 * ========================================
 * Manages the "Domain Assignments" table on the Scope Details page.
 *
 * Features:
 * - List domains assigned to this scope
 * - Assign new domains (if capable)
 * - Unassign domains (if capable)
 * - Filter by code, name, active status
 *
 * API Endpoints:
 * - POST /api/i18n/scopes/{scope_id}/domains/query
 * - POST /api/i18n/scopes/{scope_id}/domains/assign
 * - POST /api/i18n/scopes/{scope_id}/domains/unassign
 */

(function() {
    'use strict';

    console.log('🌍 I18n Scope Domains Module Loading...');

    // ========================================================================
    // PREREQUISITES CHECK
    // ========================================================================

    if (typeof AdminUIComponents === 'undefined') {
        console.error('❌ AdminUIComponents library not found!');
        return;
    }

    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }

    if (typeof window.scopeDetailsId === 'undefined') {
        console.error('❌ Scope ID not found (window.scopeDetailsId)!');
        return;
    }

    console.log('✅ Dependencies loaded: AdminUIComponents, ApiHandler');

    // ========================================================================
    // STATE & CONFIGURATION
    // ========================================================================

    let currentPage = 1;
    let currentPerPage = 25;
    const scopeId = window.scopeDetailsId;

    const headers = ['ID', 'Code', 'Name', 'Description', 'Active', 'Assigned', 'Actions'];
    const rows = ['id', 'code', 'name', 'description', 'is_active', 'assigned', 'actions'];
    const capabilities = window.scopeDetailsCapabilities || {};

    console.log('🔐 Capabilities:', capabilities);
    console.log('🎯 Scope ID:', scopeId);

    // ========================================================================
    // UTILITY FUNCTIONS
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

    // ========================================================================
    // CUSTOM RENDERERS
    // ========================================================================

    const idRenderer = (value) => {
        return `<span class="font-mono text-gray-600 dark:text-gray-400">${escapeHtml(value)}</span>`;
    };

    const codeRenderer = (value) => {
        return AdminUIComponents.renderCodeBadge(escapeHtml(value), {
            color: 'blue',
            size: 'sm'
        });
    };

    const nameRenderer = (value) => {
        return `<span class="font-medium text-gray-900 dark:text-gray-100">${escapeHtml(value)}</span>`;
    };

    const descriptionRenderer = (value) => {
        if (!value || value === '') {
            return `<span class="text-gray-400 dark:text-gray-500 italic text-sm">No description</span>`;
        }
        const truncated = value.length > 50 ? value.substring(0, 50) + '...' : value;
        return `<span class="text-gray-700 dark:text-gray-300 text-sm" title="${escapeHtml(value)}">${escapeHtml(truncated)}</span>`;
    };

    const statusRenderer = (value) => {
        // Just display status, no toggle here (domains are managed in their own page)
        return AdminUIComponents.renderStatusBadge(value, {
            clickable: false,
            activeText: 'Active',
            inactiveText: 'Inactive'
        });
    };

    const assignedRenderer = (value) => {
        const isAssigned = value === 1 || value === true || value === '1';
        if (isAssigned) {
            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                <svg class="mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                    <circle cx="4" cy="4" r="3" />
                </svg>
                Assigned
            </span>`;
        }
        return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
            Not Assigned
        </span>`;
    };

    const actionsRenderer = (value, row) => {
        const isAssigned = row.assigned === 1 || row.assigned === true || row.assigned === '1';
        const actions = [];

        if (isAssigned) {
            if (capabilities.can_unassign) {
                actions.push(AdminUIComponents.buildActionButton({
                    cssClass: 'unassign-domain-btn',
                    icon: AdminUIComponents.SVGIcons?.x || '✕',
                    text: 'Unassign',
                    color: 'red',
                    entityId: row.id,
                    title: 'Unassign domain from scope',
                    dataAttributes: {
                        'domain-code': row.code,
                        'domain-name': row.name
                    }
                }));
            }
        } else {
            if (capabilities.can_assign) {
                actions.push(AdminUIComponents.buildActionButton({
                    cssClass: 'assign-domain-btn',
                    icon: AdminUIComponents.SVGIcons?.plus || '+',
                    text: 'Assign',
                    color: 'green',
                    entityId: row.id,
                    title: 'Assign domain to scope',
                    dataAttributes: {
                        'domain-code': row.code,
                        'domain-name': row.name
                    }
                }));
            }
        }

        if (actions.length === 0) {
            return '<span class="text-gray-400 dark:text-gray-500 text-xs">No actions</span>';
        }

        return `<div class="flex flex-wrap gap-2">${actions.join('')}</div>`;
    };

    // ========================================================================
    // QUERY BUILDING
    // ========================================================================

    function buildQueryParams() {
        const params = {
            page: currentPage,
            per_page: currentPerPage
        };

        const columnFilters = {};

        const filterCode = document.getElementById('domain-filter-code')?.value?.trim();
        if (filterCode) columnFilters.code = filterCode;

        const filterName = document.getElementById('domain-filter-name')?.value?.trim();
        if (filterName) columnFilters.name = filterName;

        const filterAssigned = document.getElementById('domain-filter-assigned')?.value;
        if (filterAssigned !== '' && filterAssigned !== undefined) columnFilters.assigned = filterAssigned;

        const filterActive = document.getElementById('domain-filter-active')?.value;
        if (filterActive !== '' && filterActive !== undefined) columnFilters.is_active = filterActive;

        // Global search
        const globalSearch = document.getElementById('domain-search-global')?.value?.trim();

        if (globalSearch || Object.keys(columnFilters).length > 0) {
            params.search = {};
            if (globalSearch) params.search.global = globalSearch;
            if (Object.keys(columnFilters).length > 0) params.search.columns = columnFilters;
        }

        return params;
    }

    // ========================================================================
    // DATA LOADING
    // ========================================================================

    async function loadDomains(page = null, perPage = null) {
        if (page !== null) currentPage = page;
        if (perPage !== null) currentPerPage = perPage;

        console.log('📡 Loading scope domains...', { currentPage, currentPerPage });

        const params = buildQueryParams();
        const endpoint = `i18n/scopes/${scopeId}/domains/query`;

        const result = await ApiHandler.call(endpoint, params, 'Query Scope Domains');

        const container = document.getElementById('domains-table-container');
        if (!container) {
            console.error('❌ domains-table-container not found');
            return;
        }

        if (!result.success) {
            console.error('❌ Query failed:', result);
            renderErrorState(container, result);
            return;
        }

        const data = result.data || {};
        const domains = Array.isArray(data.data) ? data.data : [];
        const paginationInfo = data.pagination || {
            page: params.page || 1,
            per_page: params.per_page || 25,
            total: domains.length
        };

        // Render table
        if (typeof TableComponent === 'function') {
            // Hijack the global table-container ID temporarily for TableComponent
            // This is a workaround because TableComponent hardcodes #table-container
            const originalTableContainer = document.getElementById('table-container');
            const tempId = 'table-container-original-' + Date.now();
            if (originalTableContainer && originalTableContainer !== container) {
                originalTableContainer.id = tempId;
            }

            // Ensure our container has the right ID for TableComponent
            const originalContainerId = container.id;
            container.id = 'table-container';

            try {
                TableComponent(
                    domains,
                    headers,
                    rows,
                    paginationInfo,
                    "",
                    false,
                    'id',
                    null,
                    {
                        id: idRenderer,
                        code: codeRenderer,
                        name: nameRenderer,
                        description: descriptionRenderer,
                        is_active: statusRenderer,
                        assigned: assignedRenderer,
                        actions: actionsRenderer
                    },
                    null,
                    getPaginationInfo
                );
            } catch (error) {
                console.error('❌ TABLE ERROR:', error);
                ApiHandler.showAlert('danger', 'Failed to render table: ' + error.message);
            } finally {
                // Restore IDs
                container.id = originalContainerId;
                if (originalTableContainer && originalTableContainer !== container) {
                    originalTableContainer.id = 'table-container';
                }
            }
        } else {
            console.error('❌ TableComponent not found');
        }
    }

    function renderErrorState(container, result) {
        let errorHtml = `
            <div class="p-6 text-center border border-red-200 bg-red-50 rounded-lg dark:bg-red-900/20 dark:border-red-800">
                <div class="text-red-600 dark:text-red-400 text-lg font-semibold mb-2">⚠️ Error Loading Data</div>
                <div class="text-gray-600 dark:text-gray-300 mb-4">${escapeHtml(result.error || 'Failed to load domains')}</div>
        `;

        if (result.rawBody) {
            errorHtml += `
                <details class="mt-4 text-left">
                    <summary class="cursor-pointer text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                        📄 Show Raw Response
                    </summary>
                    <pre class="mt-2 p-4 bg-gray-100 dark:bg-gray-800 rounded text-xs overflow-auto max-h-96 text-left">${escapeHtml(result.rawBody)}</pre>
                </details>
            `;
        }

        errorHtml += `
                <button onclick="window.reloadScopeDomainsTable()" class="mt-6 px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    🔄 Retry
                </button>
            </div>
        `;

        container.innerHTML = errorHtml;
    }

    function getPaginationInfo(pagination) {
        const { page = 1, per_page = 25, total = 0, filtered = total } = pagination;
        const displayCount = filtered || total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem = Math.min(page * per_page, displayCount);

        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
        if (filtered && filtered !== total) {
            infoText += ` <span class="text-gray-500 dark:text-gray-400">(filtered from ${total} total)</span>`;
        }

        return { total: displayCount, info: infoText };
    }

    // ========================================================================
    // ACTION HANDLERS
    // ========================================================================

    async function handleAssign(domainCode, domainName) {
        if (!confirm(`Are you sure you want to assign domain "${domainName}" (${domainCode}) to this scope?`)) return;

        const endpoint = `i18n/scopes/${scopeId}/domains/assign`;
        const payload = { domain_code: domainCode };

        const result = await ApiHandler.call(endpoint, payload, 'Assign Domain');

        if (result.success) {
            ApiHandler.showAlert('success', `Domain "${domainName}" assigned successfully`);
            loadDomains(); // Reload table to reflect changes
        } else {
            ApiHandler.showAlert('danger', result.error || 'Failed to assign domain');
        }
    }

    async function handleUnassign(domainCode, domainName) {
        if (!confirm(`Are you sure you want to unassign domain "${domainName}" (${domainCode}) from this scope?`)) return;

        const endpoint = `i18n/scopes/${scopeId}/domains/unassign`;
        const payload = { domain_code: domainCode };

        const result = await ApiHandler.call(endpoint, payload, 'Unassign Domain');

        if (result.success) {
            ApiHandler.showAlert('success', `Domain "${domainName}" unassigned successfully`);
            loadDomains(); // Reload table to reflect changes
        } else {
            ApiHandler.showAlert('danger', result.error || 'Failed to unassign domain');
        }
    }

    // ========================================================================
    // EVENT DELEGATION
    // ========================================================================

    function setupEventDelegation() {
        document.addEventListener('click', (e) => {
            // Assign Button
            const assignBtn = e.target.closest('.assign-domain-btn');
            if (assignBtn) {
                const code = assignBtn.dataset.domainCode;
                const name = assignBtn.dataset.domainName;
                if (code) handleAssign(code, name);
                return;
            }

            // Unassign Button
            const unassignBtn = e.target.closest('.unassign-domain-btn');
            if (unassignBtn) {
                const code = unassignBtn.dataset.domainCode;
                const name = unassignBtn.dataset.domainName;
                if (code) handleUnassign(code, name);
                return;
            }
        });

        // Listen for table events (pagination)
        document.addEventListener('tableAction', (e) => {
            // Ensure this event is for us (check if target is inside our container)
            // Note: Since we hijack table-container ID, we need to be careful.
            // But since we only have one table active at a time usually, this is okay.
            // A better way is to check if the event originated from our wrapper.
            
            const { action, value } = e.detail;
            if (action === 'pageChange') {
                loadDomains(value);
            } else if (action === 'perPageChange') {
                loadDomains(1, value);
            }
        });

        // Filter Form Listeners
        const filterForm = document.getElementById('scope-domains-filter-form');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                loadDomains(1);
            });
        }

        // Filter Search Button Listener
        const filterSearchBtn = document.getElementById('btn-filter-search');
        if (filterSearchBtn) {
            filterSearchBtn.addEventListener('click', (e) => {
                e.preventDefault(); // Prevent default if it's inside a form
                loadDomains(1);
            });
        }

        const resetBtn = document.getElementById('btn-reset-filters');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (filterForm) filterForm.reset();
                loadDomains(1);
            });
        }

        // Global Search Listeners
        const searchBtn = document.getElementById('btn-search-global');
        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                loadDomains(1);
            });
        }

        const clearSearchBtn = document.getElementById('btn-clear-search');
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', () => {
                const searchInput = document.getElementById('domain-search-global');
                if (searchInput) searchInput.value = '';
                loadDomains(1);
            });
        }

        const searchInput = document.getElementById('domain-search-global');
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    loadDomains(1);
                }
            });
        }
    }

    // ========================================================================
    // INITIALIZATION
    // ========================================================================

    function init() {
        console.log('🎬 Initializing I18n Scope Domains Module...');

        setupEventDelegation();
        loadDomains();

        // Export reload function
        window.reloadScopeDomainsTable = function() {
            loadDomains(currentPage, currentPerPage);
        };

        console.log('✅ I18n Scope Domains Module initialized');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
