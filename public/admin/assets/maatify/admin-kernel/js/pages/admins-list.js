/**
 * Admins Page - Admin Management
 * Controls ALL params structure and handles admin-specific logic
 * Follows Canonical LIST / QUERY Contract (LOCKED)
 *
 * 🔐 AUTHORIZATION SYSTEM:
 * - Capabilities are injected from server-side (window.adminsCapabilities)
 * - can_create: Controls visibility of "Add Admin" button
 * - can_view_admin: Controls visibility of "Actions" column and View buttons
 * - Authorization is ALWAYS enforced server-side at API level
 */

document.addEventListener('DOMContentLoaded', () => {
    // ✅ Check capabilities before defining table structure
    const canViewAdmin = window.adminsCapabilities?.can_view_admin ?? false;
    const canCreate = window.adminsCapabilities?.can_create ?? false;

    console.log('🔐 Admins Capabilities:', {
        can_view_admin: canViewAdmin,
        can_create: canCreate
    });

    // ✅ Define headers and rows based on can_view_admin capability
    const headers = canViewAdmin
        ? ["ID", "Display Name", "Status", "Created At", "Actions"]
        : ["ID", "Display Name", "Status", "Created At"];

    const rows = canViewAdmin
        ? ["id", "display_name", "status", "created_at", "actions"]
        : ["id", "display_name", "status", "created_at"];

    const searchForm = document.getElementById('admins-search-form');
    const resetBtn = document.getElementById('btn-reset');
    const inputAdminId = document.getElementById('filter-admin-id');
    const inputEmail = document.getElementById('filter-email'); // ✅ Keep for blind-index search
    const inputDisplayName = document.getElementById('filter-display-name');
    const inputDateFrom = document.getElementById('filter-date-from');
    const inputDateTo = document.getElementById('filter-date-to');

    let currentStatusFilter = 'all'; // ✅ Track current status filter

    // ========================================================================
    // Custom Renderers - Define ONCE at the top
    // ========================================================================

    /**
     * Custom renderer for status column
     * Matches AdminStatusEnum: ACTIVE | SUSPENDED | DISABLED
     */
    const statusRenderer = (value, row) => {
        const status = value?.toUpperCase();

        let statusText = value || 'Unknown';
        let statusClass = "bg-gray-600";

        switch(status) {
            case 'ACTIVE':
                statusText = "Active";
                statusClass = "bg-green-600"; // 🟢
                break;
            case 'SUSPENDED':
                statusText = "Suspended";
                statusClass = "bg-orange-600"; // 🟠
                break;
            case 'DISABLED':
                statusText = "Disabled";
                statusClass = "bg-red-600"; // 🔴
                break;
            default:
                statusText = "Unknown";
                statusClass = "bg-gray-600";
        }

        return `<span class="${statusClass} text-white px-3 py-1 rounded-lg text-xs font-medium uppercase tracking-wide">${statusText}</span>`;
    };

    /**
     * Custom renderer for ID column
     * Clickable link to admin profile (only if can_view_admin capability exists)
     */
    const idRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';

        // ✅ Check capability
        const canViewAdmin = window.adminsCapabilities?.can_view_admin ?? false;

        if (!canViewAdmin) {
            // Show ID without link
            return `<span class="font-mono text-sm text-gray-800 dark:text-gray-300 font-medium">#${value}</span>`;
        }

        return `
            <a href="/admins/${value}/profile" 
               class="font-mono text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline cursor-pointer font-medium"
               title="View admin profile">
                #${value}
            </a>
        `;
    };

    /**
     * Custom renderer for display_name column
     * Clickable link to admin profile (only if can_view_admin capability exists)
     */
    const displayNameRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';

        const adminId = row.id;

        // ✅ Check capability
        const canViewAdmin = window.adminsCapabilities?.can_view_admin ?? false;

        if (!canViewAdmin || !adminId) {
            // Show name without link
            return `<span class="text-sm font-medium text-gray-800 dark:text-gray-200">${value}</span>`;
        }

        return `
            <a href="/admins/${adminId}/profile" 
               class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline cursor-pointer"
               title="View admin profile">
                ${value}
            </a>
        `;
    };

    /**
     * Custom renderer for created_at column
     */
    const createdAtRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';
        return `<span class="text-sm text-gray-600 dark:text-gray-400">${value}</span>`;
    };

    /**
     * Custom renderer for actions column
     * View Profile button
     * Note: This column only appears if can_view_admin capability is true
     */
    const actionsRenderer = (value, row) => {
        const adminId = row.id;
        if (!adminId) return '<span class="text-gray-400 dark:text-gray-500 italic">-</span>';

        return `
            <div class="flex items-center gap-2">
                <a href="/admins/${adminId}/profile" 
                   class="inline-flex items-center gap-1 text-xs px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-200"
                   title="View admin profile">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    View
                </a>
            </div>
        `;
    };

    // ========================================================================
    // Initialize
    // ========================================================================

    init();

    function init() {
        loadAdmins(); // ✅ Load data on page load
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

                // ✅ Validate date pair (must be atomic)
                const dateFrom = inputDateFrom?.value;
                const dateTo = inputDateTo?.value;

                if ((dateFrom && !dateTo) || (!dateFrom && dateTo)) {
                    showAlert('w', 'Date filter must include both FROM and TO dates');
                    return;
                }

                loadAdmins();
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (inputAdminId) inputAdminId.value = '';
                if (inputEmail) inputEmail.value = ''; // ✅ Reset email search field
                if (inputDisplayName) inputDisplayName.value = '';
                if (inputDateFrom) inputDateFrom.value = '';
                if (inputDateTo) inputDateTo.value = '';

                // ✅ Reset status filter to 'all'
                currentStatusFilter = 'all';

                loadAdmins();
            });
        }

        // Note: Email not displayed in table, but can still be searched via blind-index
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

            // Clean empty date filter
            if (newParams.date) {
                if (!newParams.date.from || !newParams.date.to) {
                    delete newParams.date;
                }
            }

            await loadAdminsWithParams(newParams);
        });
    }

    // ========================================================================
    // Table Filters (Custom UI) - Status Buttons Above Table
    // ========================================================================

    function setupTableFilters() {
        const filterContainer = document.getElementById('table-custom-filters');
        if (!filterContainer) return;

        filterContainer.innerHTML = `
            <div class="flex gap-4 items-center flex-wrap">
            <div class="flex gap-2">
                    <span data-status="all" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 dark:hover:bg-blue-500 hover:text-white transition-colors duration-200">All</span>
                    <span data-status="ACTIVE" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 dark:hover:bg-blue-500 hover:text-white transition-colors duration-200">Active</span>
                    <span data-status="SUSPENDED" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 dark:hover:bg-blue-500 hover:text-white transition-colors duration-200">Suspended</span>
                    <span data-status="DISABLED" class="cursor-pointer text-sm px-2 py-1 rounded-lg hover:bg-blue-400 dark:hover:bg-blue-500 hover:text-white transition-colors duration-200">Disabled</span>
                </div>    
            <div class="w-100">
                    <input id="admins-global-search" 
                        class="w-full border dark:border-gray-600 rounded-lg px-3 py-1 text-sm dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400 outline-none transition-all" 
                        placeholder="Search admins..." />
                </div>
            </div>
        `;

        const globalSearch = document.getElementById('admins-global-search');
        if (globalSearch) {
            globalSearch.addEventListener('keyup', (e) => {
                const value = e.target.value.trim();

                // Clear previous timeout
                clearTimeout(globalSearch.searchTimeout);

                // Set new timeout with longer delay (1 second)
                globalSearch.searchTimeout = setTimeout(() => {
                    handleGlobalSearch(value);
                }, 1000); // ✅ Increased to 1000ms (1 second) to allow finishing typing
            });

            // Optional: Show loading indicator while typing
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

            // ✅ Restore active state based on currentStatusFilter
            if (status === currentStatusFilter) {
                btn.classList.add('active', 'bg-blue-600', 'dark:bg-blue-500', 'text-white');
                btn.classList.remove('hover:bg-blue-400', 'dark:hover:bg-blue-500', 'hover:text-white');
            }

            btn.addEventListener('click', () => {
                console.log("📘 Clicked status:", status);

                // ✅ Update current filter
                currentStatusFilter = status;

                // ✅ Remove active from all buttons
                statusBtns.forEach(b => {
                    b.classList.remove('active', 'bg-blue-600', 'dark:bg-blue-500', 'text-white');
                    b.classList.add('hover:bg-blue-400', 'dark:hover:bg-blue-500', 'hover:text-white');
                });

                // ✅ Add active to clicked button
                btn.classList.add('active', 'bg-blue-600', 'dark:bg-blue-500', 'text-white');
                btn.classList.remove('hover:bg-blue-400', 'dark:hover:bg-blue-500', 'hover:text-white');

                handleStatusFilter(status);
            });
        });
    }

    function handleGlobalSearch(searchValue) {
        console.log("🔍 Global search:", searchValue);
        const params = buildParams(1, 10);

        if (searchValue && searchValue.trim()) {
            if (!params.search) {
                params.search = {};
            }
            params.search.global = searchValue.trim();
        }

        loadAdminsWithParams(params);
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

        loadAdminsWithParams(params);
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

        // Build column search
        if (inputAdminId && inputAdminId.value.trim()) {
            searchColumns.id = inputAdminId.value.trim();
        }
        if (inputEmail && inputEmail.value.trim()) {
            searchColumns.email = inputEmail.value.trim(); // ✅ Blind-index search
        }
        if (inputDisplayName && inputDisplayName.value.trim()) {
            searchColumns.display_name = inputDisplayName.value.trim();
        }

        if (Object.keys(searchColumns).length > 0) {
            params.search = { columns: searchColumns };
        }

        // Build date filter (must be atomic pair)
        const dateFrom = inputDateFrom?.value;
        const dateTo = inputDateTo?.value;

        if (dateFrom && dateTo) {
            params.date = {
                from: dateFrom,
                to: dateTo
            };
        }

        return params;
    }

    // ========================================================================
    // Pagination Info Callback
    // ========================================================================

    /**
     * Custom pagination info - Admins page business logic
     * Returns what should be displayed based on filtered/total
     */
    function getAdminsPaginationInfo(pagination, params) {
        console.log("🎯 getAdminsPaginationInfo called with:", pagination);

        const { page = 1, per_page = 10, total = 0, filtered = total } = pagination;

        // Check if we're filtering
        const hasFilter = (params.search &&
                (params.search.global ||
                    (params.search.columns && Object.keys(params.search.columns).length > 0))) ||
            (params.date && params.date.from && params.date.to);

        const isFiltered = hasFilter && filtered !== total;

        console.log("🔍 Filter status - hasFilter:", hasFilter, "isFiltered:", isFiltered);

        // Calculate based on filtered when applicable
        const displayCount = isFiltered ? filtered : total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem = Math.min(page * per_page, displayCount);

        // Build info text
        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
        if (isFiltered) {
            infoText += ` <span class="text-gray-500 dark:text-gray-500 text-xs">(filtered from ${total} total)</span>`;
        }

        console.log("📤 Returning:", { total: displayCount, info: infoText });

        return {
            total: displayCount,  // Use filtered count for pagination calculations
            info: infoText
        };
    }

    // ========================================================================
    // Load Admins
    // ========================================================================

    async function loadAdmins(pageNumber = 1) {
        const params = buildParams(pageNumber, 10);
        await loadAdminsWithParams(params);
    }

    async function loadAdminsWithParams(params) {
        console.log("🚀 Admins sending:", JSON.stringify(params, null, 2));

        if (typeof createTable === 'function') {
            try {
                // ✅ Build renderers object based on capabilities
                const canViewAdmin = window.adminsCapabilities?.can_view_admin ?? false;

                const renderers = {
                    id: idRenderer,
                    display_name: displayNameRenderer,
                    status: statusRenderer,
                    created_at: createdAtRenderer
                };

                // Only add actions renderer if column exists
                if (canViewAdmin) {
                    renderers.actions = actionsRenderer;
                }

                const result = await createTable(
                    "admins/query",
                    params,
                    headers,
                    rows,
                    false, // ✅ No selection for admins (read-only list)
                    'id',
                    null, // No selection callback
                    renderers,
                    null, // No selectable IDs
                    getAdminsPaginationInfo // ✅ Pass callback
                );

                if (result && result.success) {
                    console.log("✅ Admins loaded:", result.data.length);
                    console.log("📊 Pagination:", result.pagination);
                    setupTableFiltersAfterRender();
                }
            } catch (error) {
                console.error("❌ Error:", error);
                showAlert('d', 'Failed to load admins');
            }
        } else {
            console.error("❌ createTable not found");
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
});