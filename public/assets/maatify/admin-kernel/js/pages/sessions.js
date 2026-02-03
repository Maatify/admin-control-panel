/**
 * Sessions Page - Final Version
 * Controls ALL params structure and handles session-specific logic
 * IMPROVED: Better debounce delay (1000ms) with visual feedback
 */

document.addEventListener('DOMContentLoaded', () => {
    const headers = ["User ID", "Session ID", "Status", "Expires At", "Actions"];
    const rows = ["admin_id", "session_id", "status", "expires_at", "actions"];

    const searchForm = document.getElementById('sessions-search-form');
    const resetBtn = document.getElementById('btn-reset');
    const selectedCount = document.getElementById('selected-count');
    const inputSessionId = document.getElementById('filter-session-id');
    const inputAdminId = document.getElementById('filter-admin-id');
    const inputStatus = document.getElementById('filter-status');
    const btnBulkRevoke = document.getElementById('btn-bulk-revoke');

    let selectedSessions = new Set();
    let currentStatusFilter = 'all'; // ✅ Track current filter

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
        let statusClass = "bg-gray-600";

        if (isCurrent && status === 'active') {
            statusText = "Current";
            statusClass = "bg-green-600"; // 🟢
        } else if (status === 'active') {
            statusText = "Active";
            statusClass = "bg-blue-600"; // 🔵
        } else if (status === 'expired') {
            statusText = "Expired";
            statusClass = "bg-orange-600"; // 🟠
        } else if (status === 'revoked') {
            statusText = "Revoked";
            statusClass = "bg-red-600"; // 🔴
        }

        return `<span class="${statusClass} text-white px-3 py-1 rounded-lg text-xs font-medium uppercase tracking-wide">${statusText}</span>`;
    };

    /**
     * Custom renderer for session_id column
     * Clickable to copy to clipboard
     */
    const sessionIdRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';

        const displayValue = value.length > 16 ? value.substring(0, 16) + '...' : value;

        return `
            <span class="font-mono text-xs text-blue-600 hover:text-blue-800 cursor-pointer underline decoration-dotted relative session-id-copy" 
                  data-session-id="${value}"
                  title="Click to copy: ${value}">
                ${displayValue}
            </span>
        `;
    };

    /**
     * Custom renderer for admin_id column
     * Clickable link to admin profile (if capability exists)
     */
    const adminIdRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 italic">N/A</span>';

        // Check if user has can_view_admin capability
        const canViewAdmin = window.sessionsCapabilities?.can_view_admin ?? false;

        if (canViewAdmin) {
            return `<a href="/admins/${value}/profile" 
                       class="text-blue-600 hover:text-blue-800 hover:underline font-medium"
                       title="View admin profile">
                       ${value}
                    </a>`;
        } else {
            return `<span class="text-gray-700 font-medium">${value}</span>`;
        }
    };

    /**
     * Custom renderer for actions column
     * Shows revoke button for active sessions (not current, not expired)
     */
    const actionsRenderer = (value, row) => {
        const isCurrent = row.is_current === true || row.is_current === 1 || row.is_current === "1";
        const status = row.status?.toLowerCase();

        // Check if user has can_revoke_id capability
        const canRevokeId = window.sessionsCapabilities?.can_revoke_id ?? false;

        // Show revoke button only if:
        // 1. User has capability
        // 2. Session is active
        // 3. Session is not current
        if (canRevokeId && status === 'active' && !isCurrent) {
            return `
                <button class="text-xs px-3 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 transition-all duration-300 revoke-session-btn"
                        data-session-id="${row.session_id}"
                        title="Revoke this session">
                    Revoke
                </button>
            `;
        }

        // Otherwise show dash or empty
        return '<span class="text-gray-400">—</span>';
    };

    // ========================================================================
    // Initialize
    // ========================================================================

    init();

    function init() {
        loadSessions();
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
                if (inputAdminId) inputAdminId.value = '';
                if (inputStatus) inputStatus.value = '';
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

            // ✅ Setup click handler for revoke button in each row
            if (e.target.classList.contains('revoke-session-btn')) {
                const sessionId = e.target.getAttribute('data-session-id');
                if (sessionId && confirm('Are you sure you want to revoke this session?')) {
                    revokeSession(sessionId).then((success) => {
                        if (success) {
                            loadSessions(); // Reload table
                        }
                    });
                }
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
            // ✅ Same size as other buttons: text-sm px-3 py-1
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
            <div class="flex gap-4 items-center justify-between flex-wrap">
             <div class="flex gap-2">
                    <span data-status="all" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 hover:text-white transition-colors duration-200">All</span>
                    <span data-status="active" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 hover:text-white transition-colors duration-200">Active</span>
                    <span data-status="expired" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 hover:text-white transition-colors duration-200">Expired</span>
                    <span data-status="revoked" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 hover:text-white transition-colors duration-200">Revoked</span>
                </div>
                <div class="w-90">
                    <input id="sessions-global-search" 
                        class="w-full border rounded-lg px-3 py-1 text-sm transition-colors duration-200" 
                        placeholder="Search sessions..." />
                </div>
                
               
            </div>
        `;

        const globalSearch = document.getElementById('sessions-global-search');
        if (globalSearch) {
            // ✅ IMPROVED: Longer debounce delay (1000ms = 1 second)
            globalSearch.addEventListener('keyup', (e) => {
                const value = e.target.value.trim();

                // Clear previous timeout
                clearTimeout(globalSearch.searchTimeout);

                // Set new timeout with longer delay (1 second)
                globalSearch.searchTimeout = setTimeout(() => {
                    handleGlobalSearch(value);
                }, 1000); // ✅ Increased to 1000ms (1 second) to allow finishing typing
            });

            // ✅ IMPROVED: Visual feedback while typing
            globalSearch.addEventListener('input', (e) => {
                const value = e.target.value.trim();
                if (value.length > 0) {
                    // Show user that input is being processed
                    globalSearch.classList.add('border-blue-300', 'bg-blue-50');
                } else {
                    globalSearch.classList.remove('border-blue-300', 'bg-blue-50');
                }
            });
        }

        const statusBtns = filterContainer.querySelectorAll('[data-status]');
        statusBtns.forEach(btn => {
            const status = btn.getAttribute('data-status');

            // ✅ Restore active state based on currentStatusFilter
            if (status === currentStatusFilter) {
                btn.classList.add('active', 'bg-blue-600', 'text-white');
                btn.classList.remove('hover:bg-blue-400', 'hover:text-white');
            }

            btn.addEventListener('click', () => {
                console.log("📘 Clicked status:", status);

                // ✅ Update current filter
                currentStatusFilter = status;

                // ✅ Remove active from all buttons
                statusBtns.forEach(b => {
                    b.classList.remove('active', 'bg-blue-600', 'text-white');
                    b.classList.add('hover:bg-blue-400', 'hover:text-white');
                });

                // ✅ Add active to clicked button
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
        console.log("📘 Filtering by status:", status);

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
    // Params Builder
    // ========================================================================

    function buildParams(pageNumber = 1, perPage = 10) {
        const params = {
            page: pageNumber,
            per_page: perPage
        };

        const searchColumns = {};

        if (inputSessionId && inputSessionId.value.trim()) {
            searchColumns.session_id = inputSessionId.value.trim();
        }
        if (inputAdminId && inputAdminId.value.trim()) {
            searchColumns.admin_id = inputAdminId.value.trim();
        }
        if (inputStatus && inputStatus.value) {
            searchColumns.status = inputStatus.value;
        }

        if (Object.keys(searchColumns).length > 0) {
            params.search = { columns: searchColumns };
        }

        return params;
    }

    // ========================================================================
    // Pagination Info Callback
    // ========================================================================

    /**
     * Custom pagination info - Sessions page business logic
     * Returns what should be displayed based on filtered/total
     */
    function getSessionsPaginationInfo(pagination, params) {
        console.log("🎯 getSessionsPaginationInfo called with:", pagination);

        const { page = 1, per_page = 10, total = 0, filtered = total } = pagination;

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
            total: displayCount,  // Use filtered count for pagination calculations
            info: infoText
        };
    }

    // ========================================================================
    // Load Sessions
    // ========================================================================

    async function loadSessions(pageNumber = 1) {
        const params = buildParams(pageNumber, 10);
        await loadSessionsWithParams(params);
    }

    async function loadSessionsWithParams(params) {
        console.log("🚀 Sessions sending:", JSON.stringify(params, null, 2));

        if (typeof createTable === 'function') {
            try {
                // ✅ Check if user has can_revoke_bulk capability
                const canRevokeBulk = window.sessionsCapabilities?.can_revoke_bulk ?? false;

                const result = await createTable(
                    "sessions/query",
                    params,
                    headers,
                    rows,
                    canRevokeBulk, // ✅ Show checkboxes only if user has capability
                    'session_id',
                    updateSelectionCount,
                    {
                        status: statusRenderer,
                        session_id: sessionIdRenderer,
                        admin_id: adminIdRenderer,
                        actions: actionsRenderer
                    },
                    null, // selectableIds - will set in second call
                    getSessionsPaginationInfo // ✅ Pass callback
                );

                if (result && result.success) {
                    console.log("✅ Sessions loaded:", result.data.length);
                    console.log("📊 Pagination:", result.pagination);

                    // ✅ Calculate selectable IDs (ONLY active, NOT expired or current)
                    const selectableIds = result.data
                        .filter(row => {
                            const isCurrent = row.is_current === true || row.is_current === 1 || row.is_current === "1";
                            const status = row.status?.toLowerCase();

                            // Not selectable if current
                            if (isCurrent) return false;

                            // ✅ ONLY active is selectable (NOT expired)
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
                        canRevokeBulk, // ✅ Show checkboxes only if user has capability
                        'session_id',
                        updateSelectionCount,
                        {
                            status: statusRenderer,
                            session_id: sessionIdRenderer,
                            admin_id: adminIdRenderer,
                            actions: actionsRenderer
                        },
                        selectableIds,
                        getSessionsPaginationInfo // ✅ Pass callback again
                    );

                    setupTableFiltersAfterRender();
                }
            } catch (error) {
                console.error("❌ Error:", error);
                showAlert('d', 'Failed to load');
            }
        } else {
            console.error("❌ createTable not found");
        }
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /**
     * Copy text to clipboard and show notification
     */
    function copyToClipboard(text, element) {
        // Use modern Clipboard API
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

    /**
     * Fallback copy method for older browsers
     */
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

    /**
     * Show small "Copied!" notification near the element
     */
    function showCopyNotification(element) {
        // Create notification
        const notification = document.createElement('div');
        notification.textContent = 'Copied!';
        notification.className = 'absolute bg-green-600 text-white text-xs px-2 py-1 rounded shadow-lg z-50 animate-fade-in';
        notification.style.cssText = 'font-size: 10px; font-weight: 500; pointer-events: none;';

        // Position near the element
        const rect = element.getBoundingClientRect();
        notification.style.position = 'fixed';
        notification.style.left = (rect.left + rect.width / 2) + 'px';
        notification.style.top = (rect.top - 30) + 'px';
        notification.style.transform = 'translateX(-50%)';

        document.body.appendChild(notification);

        // Fade out and remove
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