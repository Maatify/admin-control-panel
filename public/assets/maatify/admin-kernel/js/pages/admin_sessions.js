/**
 * Admin Sessions Page - Scoped to Single Admin
 * =============================================
 * PURPOSE:
 * - Display sessions for ONE specific admin only
 * - Admin ID is LOCKED and always sent in every request
 * - Follows same patterns as sessions.js but scoped
 *
 * KEY DIFFERENCE from sessions.js:
 * - admin_id is REQUIRED and IMMUTABLE (from URL/data-attribute)
 * - User CANNOT change admin_id via filters
 * - All other functionality remains identical
 */

document.addEventListener('DOMContentLoaded', () => {
    // ========================================================================
    // Configuration
    // ========================================================================

    const headers = ["Session ID", "Status", "Expires At"];
    const rows = ["session_id", "status", "expires_at"];

    // ✅ Get admin_id from data attribute (set by Twig)
    const adminContainer = document.querySelector('.container[data-admin-id]');
    const ADMIN_ID = adminContainer ? adminContainer.getAttribute('data-admin-id') : null;

    // Validation
    if (!ADMIN_ID) {
        console.error('❌ Admin ID not found in data attribute');
        showAlert('d', 'Configuration error: Admin ID missing');
        return;
    }

    console.log(`✅ Admin Sessions loaded for Admin ID: ${ADMIN_ID}`);

    // ========================================================================
    // DOM Elements
    // ========================================================================

    const searchForm = document.getElementById('admin-sessions-search-form');
    const resetBtn = document.getElementById('btn-reset');
    const selectedCount = document.getElementById('selected-count');
    const inputSessionId = document.getElementById('filter-session-id');
    const inputStatus = document.getElementById('filter-status');
    const btnBulkRevoke = document.getElementById('btn-bulk-revoke');

    let selectedSessions = new Set();
    let currentStatusFilter = 'all'; // Track current filter

    // ========================================================================
    // Custom Renderers - Define ONCE at the top
    // ========================================================================

    /**
     * Custom renderer for status column
     */
    const statusRenderer = (value, row) => {
        const isCurrent = row.is_current === true || row.is_current === 1 || row.is_current === "1";
        const status = value?.toLowerCase();

        let statusText = value || 'Unknown';
        let statusClass = "bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-600";

        if (isCurrent && status === 'active') {
            statusText = "Current";
            statusClass = "bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800";
        } else if (status === 'active') {
            statusText = "Active";
            statusClass = "bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-800";
        } else if (status === 'expired') {
            statusText = "Expired";
            statusClass = "bg-orange-100 dark:bg-orange-900/40 text-orange-800 dark:text-orange-300 border-orange-200 dark:border-orange-800";
        } else if (status === 'revoked') {
            statusText = "Revoked";
            statusClass = "bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800";
        }

        return `<span class="${statusClass} px-3 py-1 rounded-full text-xs font-medium border">${statusText}</span>`;
    };

    /**
     * Custom renderer for session_id column
     * Clickable to copy to clipboard
     */
    const sessionIdRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';

        const displayValue = value.length > 16 ? value.substring(0, 16) + '...' : value;

        return `
            <span class="font-mono text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 cursor-pointer underline decoration-dotted relative session-id-copy" 
                  data-session-id="${value}"
                  title="Click to copy: ${value}">
                ${displayValue}
            </span>
        `;
    };

    // ========================================================================
    // Initialize
    // ========================================================================

    init();

    function init() {
        loadSessions(); // ✅ Load on page load with admin_id locked
        setupEventListeners();
        setupTableEventListeners();
    }

    function setupTableFiltersAfterRender() {
        setTimeout(() => setupTableFilters(), 100);
    }

    // ========================================================================
    // Event Listeners
    // ========================================================================

    function setupEventListeners() {
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                loadSessions();
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (inputSessionId) inputSessionId.value = '';
                if (inputStatus) inputStatus.value = '';
                currentStatusFilter = 'all';
                loadSessions();
            });
        }

        if (btnBulkRevoke) {
            btnBulkRevoke.addEventListener('click', revokeAllSessionsSelected);
        }

        // ✅ Setup click handler for session ID copy
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('session-id-copy')) {
                const sessionId = e.target.getAttribute('data-session-id');
                copyToClipboard(sessionId, e.target);
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

            await loadSessionsWithParams(newParams);
        });
    }

    // ========================================================================
    // Selection & Revoke
    // ========================================================================

    function updateSelectionCount(selectedItems) {
        const count = selectedItems.size;
        console.log("📊 Selected:", count);

        if (selectedCount) selectedCount.textContent = count;

        if (btnBulkRevoke) {
            btnBulkRevoke.disabled = count === 0;
            btnBulkRevoke.className = count > 0
                ? "text-sm px-3 py-1 bg-red-600 text-white hover:bg-red-700 transition-all duration-300 border border-red-600 rounded-2xl"
                : "text-sm px-3 py-1 bg-gray-300 text-gray-500 transition-all duration-300 border border-gray-300 rounded-2xl cursor-not-allowed opacity-50";
        }
    }

    function revokeAllSessionsSelected() {
        const items = getSelectedItems();

        if (items.length === 0) {
            showAlert('w', 'Select at least one session');
            return;
        }

        if (!confirm(`Revoke ${items.length} session(s)?`)) return;

        selectedSessions = new Set(items);
        Promise.all(items.map(id => revokeSession(id)))
            .then(() => {
                clearSelectedItems();
                loadSessions();
            });
    }

    async function revokeSession(sessionId) {
        try {
            const response = await fetch(`/api/sessions/${sessionId}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' }
            });

            if (response.ok) {
                selectedSessions.delete(sessionId);
                showAlert('s', 'Session revoked');
                return true;
            } else {
                const data = await response.json();
                showAlert('w', data.error || 'Failed');
                return false;
            }
        } catch (e) {
            showAlert('d', 'Network error');
            return false;
        }
    }

    // ========================================================================
    // Table Filters (Custom UI)
    // ========================================================================

    function setupTableFilters() {
        const filterContainer = document.getElementById('table-custom-filters');
        if (!filterContainer) return;

        filterContainer.innerHTML = `
            <div class="flex gap-4 items-center flex-wrap">
                <div class="w-64">
                    <input id="admin-sessions-global-search" 
                        class="w-full border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 dark:placeholder-gray-400" 
                        placeholder="Search sessions..." />
                </div>
                
                <div class="flex gap-2">
                    <span data-status="all" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 hover:text-white transition-colors duration-200">All</span>
                    <span data-status="active" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 hover:text-white transition-colors duration-200">Active</span>
                    <span data-status="expired" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 hover:text-white transition-colors duration-200">Expired</span>
                    <span data-status="revoked" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 hover:text-white transition-colors duration-200">Revoked</span>
                </div>
            </div>
        `;

        const globalSearch = document.getElementById('admin-sessions-global-search');
        if (globalSearch) {
            globalSearch.addEventListener('keyup', (e) => {
                const value = e.target.value.trim();
                clearTimeout(globalSearch.searchTimeout);
                globalSearch.searchTimeout = setTimeout(() => {
                    handleGlobalSearch(value);
                }, 1000);
            });

            globalSearch.addEventListener('input', (e) => {
                const value = e.target.value.trim();
                if (value.length > 0) {
                    globalSearch.classList.add('border-blue-300', 'bg-blue-50', 'dark:bg-blue-900/20', 'dark:border-blue-800');
                } else {
                    globalSearch.classList.remove('border-blue-300', 'bg-blue-50', 'dark:bg-blue-900/20', 'dark:border-blue-800');
                }
            });
        }

        const statusBtns = filterContainer.querySelectorAll('[data-status]');
        statusBtns.forEach(btn => {
            const status = btn.getAttribute('data-status');

            if (status === currentStatusFilter) {
                btn.classList.add('active', 'bg-blue-600', 'text-white');
                btn.classList.remove('hover:bg-blue-400', 'hover:text-white');
            }

            btn.addEventListener('click', () => {
                console.log("🔘 Clicked status:", status);
                currentStatusFilter = status;

                statusBtns.forEach(b => {
                    b.classList.remove('active', 'bg-blue-600', 'text-white');
                    b.classList.add('hover:bg-blue-400', 'hover:text-white');
                });

                btn.classList.add('active', 'bg-blue-600', 'text-white');
                btn.classList.remove('hover:bg-blue-400', 'hover:text-white');

                handleStatusFilter(status);
            });
        });
    }

    function handleGlobalSearch(searchValue) {
        console.log("🔍 Global search:", searchValue);
        const params = buildParams(1, 10);

        if (searchValue && searchValue.trim()) {
            if (!params.search) {
                params.search = { columns: {} };
            }
            params.search.global = searchValue.trim();
        }

        loadSessionsWithParams(params);
    }

    function handleStatusFilter(status) {
        console.log("🔘 Filtering by status:", status);

        const params = buildParams(1, 10);

        if (status !== 'all') {
            if (!params.search) {
                params.search = { columns: {} };
            }
            params.search.columns.status = status;
        }

        loadSessionsWithParams(params);
    }

    // ========================================================================
    // Params Builder - ✅ LOCKS admin_id in EVERY request
    // ========================================================================

    function buildParams(pageNumber = 1, perPage = 10) {
        const params = {
            page: pageNumber,
            per_page: perPage
        };

        // ✅ CRITICAL: admin_id is ALWAYS present
        const searchColumns = {
            admin_id: ADMIN_ID  // 🔒 LOCKED - never changes
        };

        // Add other optional filters
        if (inputSessionId && inputSessionId.value.trim()) {
            searchColumns.session_id = inputSessionId.value.trim();
        }
        if (inputStatus && inputStatus.value) {
            searchColumns.status = inputStatus.value;
        }

        params.search = { columns: searchColumns };

        return params;
    }

    // ========================================================================
    // Pagination Info Callback
    // ========================================================================

    function getSessionsPaginationInfo(pagination, params) {
        console.log("🎯 getSessionsPaginationInfo called with:", pagination);

        const { page = 1, per_page = 10, total = 0, filtered = total } = pagination;

        const hasFilter = params.search &&
            (params.search.global ||
                (params.search.columns && Object.keys(params.search.columns).filter(k => k !== 'admin_id').length > 0));

        const isFiltered = hasFilter && filtered !== total;

        console.log("🔍 Filter status - hasFilter:", hasFilter, "isFiltered:", isFiltered);

        const displayCount = isFiltered ? filtered : total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem = Math.min(page * per_page, displayCount);

        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
        if (isFiltered) {
            infoText += ` <span class="text-gray-500 dark:text-gray-400 text-xs">(filtered from ${total} total)</span>`;
        }

        console.log("📤 Returning:", { total: displayCount, info: infoText });

        return {
            total: displayCount,
            info: infoText
        };
    }

    // ========================================================================
    // Load Sessions - ✅ ALWAYS includes admin_id
    // ========================================================================

    async function loadSessions(pageNumber = 1) {
        const params = buildParams(pageNumber, 10);
        await loadSessionsWithParams(params);
    }

    async function loadSessionsWithParams(params) {
        console.log("🚀 Admin Sessions sending:", JSON.stringify(params, null, 2));
        console.log(`🔒 Admin ID locked: ${ADMIN_ID}`);

        if (typeof createTable === 'function') {
            try {
                const result = await createTable(
                    "sessions/query",
                    params,
                    headers,
                    rows,
                    true,
                    'session_id',
                    updateSelectionCount,
                    {
                        status: statusRenderer,
                        session_id: sessionIdRenderer
                    },
                    null,
                    getSessionsPaginationInfo
                );

                if (result && result.success) {
                    console.log("✅ Sessions loaded:", result.data.length);
                    console.log("📊 Pagination:", result.pagination);

                    // Calculate selectable IDs (ONLY active, NOT expired or current)
                    const selectableIds = result.data
                        .filter(row => {
                            const isCurrent = row.is_current === true || row.is_current === 1 || row.is_current === "1";
                            const status = row.status?.toLowerCase();

                            if (isCurrent) return false;
                            return status === 'active';
                        })
                        .map(row => String(row.session_id));

                    console.log("✅ Selectable sessions:", selectableIds.length);

                    // Re-render with selectable IDs
                    await createTable(
                        "sessions/query",
                        params,
                        headers,
                        rows,
                        true,
                        'session_id',
                        updateSelectionCount,
                        {
                            status: statusRenderer,
                            session_id: sessionIdRenderer
                        },
                        selectableIds,
                        getSessionsPaginationInfo
                    );

                    setupTableFiltersAfterRender();
                }
            } catch (error) {
                console.error("❌ Error:", error);
                showAlert('d', 'Failed to load sessions');
            }
        } else {
            console.error("❌ createTable not found");
        }
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    function copyToClipboard(text, element) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                showCopyNotification(element);
            }).catch(err => {
                console.error('Failed to copy:', err);
                fallbackCopy(text, element);
            });
        } else {
            fallbackCopy(text, element);
        }
    }

    function fallbackCopy(text, element) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand('copy');
            showCopyNotification(element);
        } catch (err) {
            console.error('Fallback copy failed:', err);
        }

        document.body.removeChild(textarea);
    }

    function showCopyNotification(element) {
        const notification = document.createElement('div');
        notification.textContent = 'Copied!';
        notification.className = 'absolute bg-green-600 text-white text-xs px-2 py-1 rounded shadow-lg z-50 animate-fade-in';
        notification.style.cssText = 'font-size: 10px; font-weight: 500; pointer-events: none;';

        const rect = element.getBoundingClientRect();
        notification.style.position = 'fixed';
        notification.style.left = (rect.left + rect.width / 2) + 'px';
        notification.style.top = (rect.top - 30) + 'px';
        notification.style.transform = 'translateX(-50%)';

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, 1000);
    }

    function showAlert(type, message) {
        if (typeof window.showAlert === 'function') {
            window.showAlert(type, message);
        } else {
            console.log(`[${type}] ${message}`);
        }
    }
});