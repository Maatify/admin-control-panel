/**
 * Activity Logs Page
 * STRICT implementation aligned with sessions.js
 * Global search is BUILT here (table-level search)
 * Debounce: 750ms after last input
 */

document.addEventListener('DOMContentLoaded', () => {

    const headers = [
        'ID',
        'Action',
        'Actor Type',
        'Actor ID',
        'Entity Type',
        'Entity ID',
        'IP Address',
        'Request ID',
        'Metadata',
        'Occurred At'
    ];

    const rows = [
        'id',
        'action',
        'actor_type',
        'actor_id',
        'entity_type',
        'entity_id',
        'ip_address',
        'request_id',
        'metadata',
        'occurred_at'
    ];

    const searchForm = document.getElementById('activity-logs-search-form');
    const resetBtn   = document.getElementById('btn-reset');

    const inputActorId   = document.getElementById('filter-actor-id');
    const inputAction    = document.getElementById('filter-action');
    const inputIpAddress = document.getElementById('filter-ip-address');
    const inputDateFrom  = document.getElementById('filter-date-from');
    const inputDateTo    = document.getElementById('filter-date-to');

    let globalSearchTimer = null;

    // =========================================================================
    // Renderers
    // =========================================================================

    const metadataRenderer = (value) => {
        if (!value || typeof value !== 'object') {
            return `<span class="text-gray-400 italic">—</span>`;
        }

        return `
            <button
                class="text-xs px-3 py-1 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 dark:text-gray-200"
                data-action="view-metadata"
                data-metadata='${JSON.stringify(value)}'
            >
                View
            </button>
        `;
    };

    // =========================================================================
    // Init
    // =========================================================================

    init();

    function init() {
        loadActivityLogs();
        setupEventListeners();
        setupTableEventListeners();
    }

    // =========================================================================
    // Event Listeners
    // =========================================================================

    function setupEventListeners() {

        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                loadActivityLogs();
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                inputActorId.value   = '';
                inputAction.value    = '';
                inputIpAddress.value = '';
                inputDateFrom.value  = '';
                inputDateTo.value    = '';
                loadActivityLogs();
            });
        }

        // Metadata modal
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action="view-metadata"]');
            if (!btn) return;

            try {
                const metadata = JSON.parse(btn.getAttribute('data-metadata'));
                openMetadataModal(metadata);
            } catch (err) {
                console.error('Invalid metadata payload', err);
            }
        });

        document.getElementById('metadata-close')
            ?.addEventListener('click', closeMetadataModal);
    }

    // =========================================================================
    // Table Events (Pagination only)
    // =========================================================================

    function setupTableEventListeners() {

        document.addEventListener('tableAction', async (e) => {

            const { action, value, currentParams } = e.detail;
            let newParams = JSON.parse(JSON.stringify(currentParams));

            switch (action) {

                case 'pageChange':
                    newParams.page = value;
                    break;

                case 'perPageChange':
                    newParams.per_page = value;
                    newParams.page = 1;
                    break;
            }

            await loadActivityLogsWithParams(newParams);
        });
    }

    // =========================================================================
    // Params Builder (NO global here)
    // =========================================================================

    function buildParams(page = 1, perPage = 10) {

        const params = {
            page: page,
            per_page: perPage
        };

        const columns = {};

        if (inputActorId.value.trim()) {
            columns.actor_id = inputActorId.value.trim();
        }

        if (inputAction.value.trim()) {
            columns.action = inputAction.value.trim();
        }

        if (inputIpAddress.value.trim()) {
            columns.ip_address = inputIpAddress.value.trim();
        }

        if (Object.keys(columns).length > 0) {
            params.search = { columns };
        }

        const date = {};

        if (inputDateFrom.value) {
            date.from = inputDateFrom.value;
        }

        if (inputDateTo.value) {
            date.to = inputDateTo.value;
        }

        if (Object.keys(date).length > 0) {
            params.date = date;
        }

        return params;
    }

    // =========================================================================
    // Global Search UI (Table-level)
    // =========================================================================

    function setupGlobalSearchUI() {
        const container = document.getElementById('table-custom-filters');
        if (!container) return;

        container.innerHTML = `
            <div class="flex items-center">
                <div class="w-72">
                    <input
                        id="activity-global-search"
                        type="text"
                        class="w-full border border-gray-300 rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-100 dark:placeholder-gray-400"
                        placeholder="Search activity logs..."
                    />
                </div>
            </div>
        `;

        const input = document.getElementById('activity-global-search');
        if (!input) return;

        input.addEventListener('keyup', () => {
            clearTimeout(globalSearchTimer);

            globalSearchTimer = setTimeout(() => {
                handleGlobalSearch(input.value.trim());
            }, 750);
        });
    }

    function handleGlobalSearch(value) {

        const params = buildParams(1, 10);

        if (value) {
            params.search = params.search || {};
            params.search.global = value;
        }

        loadActivityLogsWithParams(params);
    }

    // =========================================================================
    // Pagination Info (same philosophy as sessions)
    // =========================================================================

    function getPaginationInfo(pagination, params) {

        const { page = 1, per_page = 10, total = 0, filtered = total } = pagination;

        const hasFilter =
            params.search &&
            (params.search.global ||
                (params.search.columns &&
                    Object.keys(params.search.columns).length > 0));

        const isFiltered = hasFilter && filtered !== total;

        const displayCount = isFiltered ? filtered : total;
        const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
        const endItem = Math.min(page * per_page, displayCount);

        let info = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;
        if (isFiltered) {
            info += ` <span class="text-gray-500 dark:text-gray-400 text-xs">(filtered from ${total} total)</span>`;
        }

        return {
            total: displayCount,
            info: info
        };
    }

    // =========================================================================
    // Load
    // =========================================================================

    async function loadActivityLogs(page = 1) {
        try{

            const params = buildParams(page, 10);
            await loadActivityLogsWithParams(params);
        }catch(e){
            console.log('Failed to load activity logs2222', e);
        }
    }

    async function loadActivityLogsWithParams(params) {

        try {
            
            await createTable(
                'activity-logs/query',
                params,
                headers,
                rows,
                false,
                'id',
                null,
                {
                    metadata: metadataRenderer
                },
                null,
                getPaginationInfo
            );

            // ⬅️ AFTER table render
            setupGlobalSearchUI();

        } catch (e) {
            console.log('Failed to load activity logs', e);
        }
    }

    // =========================================================================
    // Metadata Modal
    // =========================================================================

    function openMetadataModal(metadata) {
        const modal = document.getElementById('metadata-modal');
        const body  = document.getElementById('metadata-table-body');

        body.innerHTML = '';

        Object.entries(metadata).forEach(([key, value]) => {
            body.innerHTML += `
                <tr>
                    <td class="border px-3 py-2 font-mono dark:border-gray-700">${key}</td>
                    <td class="border px-3 py-2 dark:border-gray-700">${formatMetadataValue(value)}</td>
                </tr>
            `;
        });

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeMetadataModal() {
        const modal = document.getElementById('metadata-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function formatMetadataValue(value) {
        if (typeof value === 'boolean') return value ? 'true' : 'false';
        if (value === null) return 'null';
        if (typeof value === 'object') return JSON.stringify(value);
        return String(value);
    }

});
