/**
 * Telemetry Page - Read-only telemetry traces
 * Handles filtering, pagination, and display of system telemetry data
 */

document.addEventListener('DOMContentLoaded', () => {
    const headers = ["Event Key", "Severity", "Actor", "Route", "Request ID", "IP Address", "Occurred At", "Metadata"];
    const rows = ["event_key", "severity", "actor_info", "route_name", "request_id", "ip_address", "occurred_at", "has_metadata"];

    const searchForm = document.getElementById('telemetry-search-form');
    const resetBtn = document.getElementById('btn-reset');
    const inputEventKey = document.getElementById('filter-event-key');
    const inputRouteName = document.getElementById('filter-route-name');
    const inputRequestId = document.getElementById('filter-request-id');
    const inputActorType = document.getElementById('filter-actor-type');
    const inputActorId = document.getElementById('filter-actor-id');
    const inputIpAddress = document.getElementById('filter-ip-address');
    const inputDateFrom = document.getElementById('filter-date-from');
    const inputDateTo = document.getElementById('filter-date-to');

    // ========================================================================
    // Custom Renderers
    // ========================================================================

    /**
     * Custom renderer for severity column
     */
    const severityRenderer = (value, row) => {
        const severity = value?.toUpperCase() || 'INFO';
        let severityClass = "bg-gray-600";

        switch(severity) {
            case 'DEBUG':
                severityClass = "bg-gray-500";
                break;
            case 'INFO':
                severityClass = "bg-blue-600";
                break;
            case 'WARNING':
                severityClass = "bg-yellow-600";
                break;
            case 'ERROR':
                severityClass = "bg-red-600";
                break;
            case 'CRITICAL':
                severityClass = "bg-red-800";
                break;
        }

        return `<span class="${severityClass} text-white px-3 py-1 rounded-lg text-xs font-medium uppercase tracking-wide">${severity}</span>`;
    };

    /**
     * Custom renderer for actor info
     * Combines actor_type and actor_id
     */
    const actorRenderer = (value, row) => {
        const actorType = row.actor_type || 'unknown';
        const actorId = row.actor_id;

        let badgeClass = actorType === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800';

        // Show ID only if it exists
        const idDisplay = actorId ? `<span class="text-xs text-gray-600">#${actorId}</span>` : '';

        return `
            <div class="flex items-center gap-2">
                <span class="${badgeClass} px-2 py-1 rounded text-xs font-medium">${actorType}</span>
                ${idDisplay}
            </div>
        `;
    };

    /**
     * Custom renderer for request_id column
     * Clickable to copy to clipboard
     */
    const requestIdRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 text-xs">-</span>';

        const displayValue = value.length > 12 ? value.substring(0, 12) + '...' : value;

        return `
            <span class="font-mono text-xs text-blue-600 hover:text-blue-800 cursor-pointer underline decoration-dotted relative request-id-copy" 
                  data-request-id="${value}"
                  title="Click to copy: ${value}">
                ${displayValue}
            </span>
        `;
    };

    /**
     * Custom renderer for event_key
     */
    const eventKeyRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 text-xs">-</span>';
        return `<span class="font-mono text-xs text-gray-800 font-semibold">${value}</span>`;
    };

    /**
     * Custom renderer for occurred_at timestamp
     */
    const occurredAtRenderer = (value, row) => {
        if (!value) return '<span class="text-gray-400 text-xs">-</span>';

        // Format: 2026-01-16 12:45:10
        const date = new Date(value);
        const formatted = date.toLocaleString('en-GB', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });

        return `<span class="text-xs text-gray-700">${formatted}</span>`;
    };

    /**
     * Custom renderer for has_metadata
     * Shows clickable link if metadata exists
     */
    const metadataRenderer = (value, row) => {
        const hasMetadata = value === true || value === 1 || value === "1";

        if (!hasMetadata) {
            return '<span class="text-gray-400 text-xs">-</span>';
        }

        const telemetryId = row.id;
        const metadataUrl = `/telemetry/${telemetryId}/metadata`;

        return `
            <a href="${metadataUrl}" 
               class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 font-medium hover:underline">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                View
            </a>
        `;
    };

    // ========================================================================
    // Initialize
    // ========================================================================

    init();

    function init() {
        loadTelemetry();
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
                loadTelemetry();
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (inputEventKey) inputEventKey.value = '';
                if (inputRouteName) inputRouteName.value = '';
                if (inputRequestId) inputRequestId.value = '';
                if (inputActorType) inputActorType.value = '';
                if (inputActorId) inputActorId.value = '';
                if (inputIpAddress) inputIpAddress.value = '';
                if (inputDateFrom) inputDateFrom.value = '';
                if (inputDateTo) inputDateTo.value = '';
                loadTelemetry();
            });
        }

        // Setup click handler for request ID copy
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('request-id-copy')) {
                const requestId = e.target.getAttribute('data-request-id');
                copyToClipboard(requestId, e.target);
            }
        });
    }

    function setupTableEventListeners() {
        document.addEventListener('tableAction', async (e) => {
            const { action, value, currentParams } = e.detail;
            console.log("🔨 Table event:", action, value);

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

            await loadTelemetryWithParams(newParams);
        });
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
                    <input id="telemetry-global-search" 
                        class="w-full border rounded-lg px-3 py-1 text-sm" 
                        placeholder="Search telemetry..." />
                </div>
            </div>
        `;

        const globalSearch = document.getElementById('telemetry-global-search');
        if (globalSearch) {
            globalSearch.addEventListener('keyup', (e) => {
                const value = e.target.value.trim();
                clearTimeout(globalSearch.searchTimeout);
                globalSearch.searchTimeout = setTimeout(() => {
                    handleGlobalSearch(value);
                }, 500);
            });
        }
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

        loadTelemetryWithParams(params);
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

        if (inputEventKey && inputEventKey.value.trim()) {
            searchColumns.event_key = inputEventKey.value.trim();
        }
        if (inputRouteName && inputRouteName.value.trim()) {
            searchColumns.route_name = inputRouteName.value.trim();
        }
        if (inputRequestId && inputRequestId.value.trim()) {
            searchColumns.request_id = inputRequestId.value.trim();
        }
        if (inputActorType && inputActorType.value) {
            searchColumns.actor_type = inputActorType.value;
        }
        if (inputActorId && inputActorId.value.trim()) {
            searchColumns.actor_id = inputActorId.value.trim();
        }
        if (inputIpAddress && inputIpAddress.value.trim()) {
            searchColumns.ip_address = inputIpAddress.value.trim();
        }

        if (Object.keys(searchColumns).length > 0) {
            params.search = { columns: searchColumns };
        }

        // Date filter
        if ((inputDateFrom && inputDateFrom.value) || (inputDateTo && inputDateTo.value)) {
            params.date = {};
            if (inputDateFrom && inputDateFrom.value) {
                params.date.from = inputDateFrom.value;
            }
            if (inputDateTo && inputDateTo.value) {
                params.date.to = inputDateTo.value;
            }
        }

        return params;
    }

    // ========================================================================
    // Pagination Info Callback
    // ========================================================================

    function getTelemetryPaginationInfo(pagination, params) {
        console.log("🎯 getTelemetryPaginationInfo called with:", pagination);

        const { page = 1, per_page = 10, total = 0, filtered = total } = pagination;

        const hasFilter = params.search &&
            (params.search.global ||
                (params.search.columns && Object.keys(params.search.columns).length > 0));
        const isFiltered = hasFilter && filtered !== total;

        const displayCount = isFiltered ? filtered : total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem = Math.min(page * per_page, displayCount);

        let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
        if (isFiltered) {
            infoText += ` <span class="text-gray-500 text-xs">(filtered from ${total} total)</span>`;
        }

        return {
            total: displayCount,
            info: infoText
        };
    }

    // ========================================================================
    // Load Telemetry
    // ========================================================================

    async function loadTelemetry(pageNumber = 1) {
        const params = buildParams(pageNumber, 10);
        await loadTelemetryWithParams(params);
    }

    async function loadTelemetryWithParams(params) {
        console.log("🚀 Telemetry sending:", JSON.stringify(params, null, 2));

        if (typeof createTable === 'function') {
            try {
                const result = await createTable(
                    "api/telemetry/query",
                    params,
                    headers,
                    rows,
                    false, // No selection for read-only telemetry
                    'id',
                    null, // No selection callback
                    {
                        severity: severityRenderer,
                        actor_info: actorRenderer,
                        request_id: requestIdRenderer,
                        event_key: eventKeyRenderer,
                        occurred_at: occurredAtRenderer,
                        has_metadata: metadataRenderer
                    },
                    null, // No selectable IDs
                    getTelemetryPaginationInfo
                );

                if (result && result.success) {
                    console.log("✅ Telemetry loaded:", result.data.length);
                    console.log("📊 Pagination:", result.pagination);
                    setupTableFiltersAfterRender();
                }
            } catch (error) {
                console.error("❌ Error:", error);
                showAlert('d', 'Failed to load telemetry');
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