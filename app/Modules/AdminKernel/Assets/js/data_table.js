/**
 * Generic Data Table Component
 * Completely agnostic - doesn't know business logic
 * Just displays what the API sends
 */

let tableData = [];
let headers = [];
let rows = [];
let per_page = 10;
let pageNo = 1;
let tableCount = 0;
let pagination = { page: 1, total: 0, per_page: 10 };
let apiUrlDefault = "/api/endpoint";
let paramsDefault = {};
let withSelectionDefault = false;
let primaryKeyDefault = 'id';
let selectedItems = new Set();
let onSelectionChangeDefault = null;
let customRenderersDefault = {};
let selectableIdsDefault = null;
let getPaginationInfoDefault = null; // ✅ Add this

/**
 * Generic API call - sends params as-is
 * @param {object} customRenderers - Optional: { columnKey: (value, row) => html }
 * @param {Set|Array} selectableIds - Optional: Set/Array of IDs that can be selected (if null, all are selectable)
 * @param {function} getPaginationInfo - Optional: (pagination, params) => { total, info } to customize display
 */
async function createTable(apiUrl, params, headersArg, rowsArg, withSelection = false, primaryKey = 'id', onSelectionChange = null, customRenderers = {}, selectableIds = null, getPaginationInfo = null) {
    apiUrlDefault = apiUrl;
    paramsDefault = JSON.parse(JSON.stringify(params));
    withSelectionDefault = withSelection;
    primaryKeyDefault = primaryKey;
    customRenderersDefault = customRenderers || {};
    selectableIdsDefault = selectableIds ? (selectableIds instanceof Set ? selectableIds : new Set(selectableIds)) : null;
    getPaginationInfoDefault = getPaginationInfo; // ✅ Store it
    if (onSelectionChange) onSelectionChangeDefault = onSelectionChange;

    if (headersArg) headers = headersArg;
    if (rowsArg) rows = rowsArg;

    if (!headers?.length || !rows?.length) {
        showAlert('danger', 'Table configuration error');
        return null;
    }

    // Extract per_page for internal use (but don't modify params)
    if (params.per_page) per_page = params.per_page;

    console.log("📤 TABLE SENDING:", JSON.stringify(params, null, 2));

    showLoadingIndicator();

    try {
        let cleanApiUrl = apiUrl.replace(/^\/+|\/+$/g, '');
        if (cleanApiUrl.startsWith('api/')) cleanApiUrl = cleanApiUrl.substring(4);
        const fullUrl = `/api/${cleanApiUrl}`;

        const response = await fetch(fullUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(params)
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();
        console.log("✅ TABLE RECEIVED:", data);

        let actualData = Array.isArray(data.data) ? data.data : data.data?.data || [];
        let actualPagination = data.pagination || data.data?.pagination;

        if (!Array.isArray(actualData)) throw new Error("Invalid data format");

        tableData = actualData;

        // ✅ Keep FULL pagination from API (including 'filtered')
        pagination = actualPagination || {
            page: params.page || 1,
            total: actualData.length,
            per_page: per_page
        };

        tableCount = pagination.total;
        pageNo = pagination.page;

        hideLoadingIndicator();
        TableComponent(tableData, headers, rows, actualPagination, "", withSelection, primaryKey, onSelectionChangeDefault, customRenderersDefault, selectableIdsDefault, getPaginationInfoDefault);

        return { success: true, data: actualData, pagination: actualPagination };

    } catch (error) {
        console.error("❌ TABLE ERROR:", error);
        hideLoadingIndicator();
        showAlert('danger', error.message || 'Failed to load');

        const container = document.querySelector("#table-container");
        if (container) {
            container.innerHTML = `<div class="bg-white rounded-lg p-8 shadow-lg text-center"><h3 class="text-lg font-medium text-gray-900">Error Loading Data</h3><p class="text-sm text-gray-500">${error.message}</p><button onclick="location.reload()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg">Retry</button></div>`;
        }
        return { success: false, error: error.message };
    }
}

// Helper functions
function getSelectedItems() { return Array.from(selectedItems); }
function getSelectedCount() { return selectedItems.size; }
function isItemSelected(id) { return selectedItems.has(String(id)); }

function clearSelectedItems() {
    selectedItems.clear();
    const cb = document.querySelector("#select-all");
    if (cb) { cb.checked = false; cb.indeterminate = false; }
    document.querySelectorAll(".row-checkbox").forEach(c => c.checked = false);
    if (onSelectionChangeDefault) onSelectionChangeDefault(selectedItems);
}

function selectItems(ids) {
    if (!Array.isArray(ids)) return;
    ids.forEach(id => selectedItems.add(String(id)));
    document.querySelectorAll(".row-checkbox").forEach(cb => {
        if (ids.includes(cb.value) || ids.includes(Number(cb.value))) cb.checked = true;
    });
    if (onSelectionChangeDefault) onSelectionChangeDefault(selectedItems);
}

function showLoadingIndicator() {
    const c = document.querySelector("#table-container");
    if (c) c.innerHTML = `<div class="bg-white rounded-lg p-12 shadow-lg flex flex-col items-center"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div><p class="mt-4 text-gray-600">Loading...</p></div>`;
}

function hideLoadingIndicator() {}

function showAlert(type, message) {
    const typeMap = { d: 'danger', s: 'success', w: 'warning', i: 'info' };
    const t = typeMap[type] || type;
    const colors = {
        success: 'bg-green-100 border-green-400 text-green-700',
        danger: 'bg-red-100 border-red-400 text-red-700',
        warning: 'bg-yellow-100 border-yellow-400 text-yellow-700',
        info: 'bg-blue-100 border-blue-400 text-blue-700'
    };
    const el = document.createElement('div');
    el.className = `fixed top-4 right-4 z-50 ${colors[t]} border px-4 py-3 rounded-lg shadow-lg max-w-md`;
    el.innerHTML = `<div class="flex items-center justify-between"><span>${message}</span><button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-lg">&times;</button></div>`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 5000);
}

async function refreshTable() {
    return await createTable(apiUrlDefault, paramsDefault, headers, rows, withSelectionDefault, primaryKeyDefault, onSelectionChangeDefault);
}

/**
 * Table Component - renders UI and handles interactions
 * Pure presentation - just displays what it receives
 * @param {object} customRenderers - Optional: { columnKey: (value, row) => html }
 * @param {Set} selectableIds - Optional: Set of IDs that are selectable
 * @param {function} getPaginationInfo - Optional: callback to customize pagination display
 */
function TableComponent(data, columns, rowNames, paginationData, actions = "", withSelection = false, primaryKey = 'id', onSelectionChange = null, customRenderers = {}, selectableIds = null, getPaginationInfo = null) {
    headers = columns;
    rows = rowNames;
    primaryKeyDefault = primaryKey;
    withSelectionDefault = withSelection;
    pagination = paginationData;
    if (onSelectionChange) onSelectionChangeDefault = onSelectionChange;
    if (typeof selectedItems === 'undefined') selectedItems = new Set();

    let tableContent = [...data];
    let currentSort = { key: null, asc: true };

    // ✅ Let parent decide what to display via callback
    let displayTotal, infoText;
    if (getPaginationInfo && typeof getPaginationInfo === 'function') {
        console.log("🎨 Using custom pagination info callback");
        const customInfo = getPaginationInfo(paginationData, paramsDefault);
        displayTotal = customInfo.total;
        infoText = customInfo.info;
        console.log("📊 Custom info:", customInfo);
    } else {
        console.log("📊 Using default pagination info");
        // Default behavior
        displayTotal = paginationData.total || data.length || 0;
        const safePage = paginationData.page || 1;
        const safePerPage = per_page || 10;
        const firstNumber = safePage === 1 ? 1 : 1 + safePerPage * (safePage - 1);
        const lastItemNumber = Math.min(safePerPage * safePage, displayTotal);
        infoText = `<span>${firstNumber} to ${lastItemNumber}</span> of <span>${displayTotal}</span>`;
    }

    const safeTotal = displayTotal;
    const safePage = paginationData.page || 1;
    const safePerPage = per_page || 10;

    const container = document.querySelector("#table-container");
    if (!container) return;

    const checkboxHeader = withSelection ? `<th class="w-10 pl-4 py-3 bg-white"><input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 w-4 h-4 cursor-pointer"></th>` : '';

    container.innerHTML = `
    <div class="table-container bg-white rounded-lg p-4 shadow-lg">
        <div class="tableHeader flex flex-wrap justify-between items-center mb-4 gap-4">
            <div class="flex gap-2">
                <button class="text-sm px-3 py-1 bg-white hover:bg-blue-400 hover:text-white transition-all text-gray-500 border rounded-2xl" id="export-csv">CSV</button>
                <button class="text-sm px-3 py-1 bg-white hover:bg-blue-400 hover:text-white transition-all text-gray-500 border rounded-2xl" id="export-excel">Excel</button>
                <button class="text-sm px-3 py-1 bg-white hover:bg-blue-400 hover:text-white transition-all text-gray-500 border rounded-2xl" id="export-pdf">PDF</button>
            </div>
            
            <!-- Optional: Container for custom filters (controlled by parent page) -->
            <div id="table-custom-filters" class="flex-1 flex justify-end"></div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        ${checkboxHeader}
                        ${columns.map(col => `<th data-key="${col.toLowerCase()}" class="pr-6 py-3 text-left text-xs font-medium text-gray-500 capitalize cursor-pointer bg-white"><span class="flex items-center">${col}<span class="ml-2 flex flex-col"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2 rotate-[270deg] up"><path d="M3 3.732a1.5 1.5 0 0 1 2.305-1.265l6.706 4.267a1.5 1.5 0 0 1 0 2.531l-6.706 4.268A1.5 1.5 0 0 1 3 12.267V3.732Z"/></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2 rotate-90 down"><path d="M3 3.732a1.5 1.5 0 0 1 2.305-1.265l6.706 4.267a1.5 1.5 0 0 1 0 2.531l-6.706 4.268A1.5 1.5 0 0 1 3 12.267V3.732Z"/></svg></span></span></th>`).join('')}
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200"></tbody>
            </table>
        </div>
        <footer class="pt-4 p-5 flex flex-wrap justify-between items-center">
            <div class="flex items-center gap-3">
                <p class="text-sm text-gray-700">${infoText}</p>
                <div class="flex gap-1 items-center">
                    <span class="text-sm">show</span>
                    <select class="form-group-select border rounded px-2 py-1 text-sm bg-white cursor-pointer">
                        <option value="10" ${safePerPage == 10 ? "selected" : ""}>10</option>
                        <option value="25" ${safePerPage == 25 ? "selected" : ""}>25</option>
                        <option value="50" ${safePerPage == 50 ? "selected" : ""}>50</option>
                        <option value="100" ${safePerPage == 100 ? "selected" : ""}>100</option>
                    </select>
                </div>
            </div>
            <nav><ul class="pagination flex items-center gap-[1px]" id="pagination"></ul></nav>
        </footer>
    </div>`;

    const tbody = container.querySelector("tbody");
    const headersElements = container.querySelectorAll("th[data-key]");
    const paginationContainer = document.getElementById("pagination");
    const per_pageSelect = document.querySelector(".form-group-select");

    /**
     * Generic event that parent can listen to
     * Emits custom event with action type and value
     */
    function triggerTableEvent(action, value) {
        const event = new CustomEvent('tableAction', {
            detail: { action, value, currentParams: paramsDefault }
        });
        document.dispatchEvent(event);
        console.log("🔔 Table Event:", action, value);
    }

    // Pagination
    async function updatePage(pageNumber) {
        triggerTableEvent('pageChange', pageNumber);
    }

    function renderPagination() {
        paginationContainer.innerHTML = "";
        const totalPages = Math.ceil(safeTotal / safePerPage) || 1;

        const prevLi = document.createElement("li");
        prevLi.className = `flex ${safePage <= 1 ? "opacity-50" : ""}`;
        prevLi.innerHTML = `<span class="bg-gray-200 text-gray-600 rounded hover:bg-blue-500 hover:text-white cursor-pointer"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg></span>`;
        if (safePage > 1) prevLi.onclick = () => updatePage(safePage - 1);
        paginationContainer.appendChild(prevLi);

        const maxVisible = 7;
        let start = Math.max(1, safePage - Math.floor(maxVisible / 2));
        let end = Math.min(totalPages, start + maxVisible - 1);
        if (end - start < maxVisible - 1) start = Math.max(1, end - maxVisible + 1);

        for (let i = start; i <= end; i++) {
            const li = document.createElement("li");
            li.innerHTML = `<span class="px-2 rounded cursor-pointer ${safePage === i ? 'bg-blue-600 text-white' : 'bg-blue-400 text-white hover:bg-blue-600'}">${i}</span>`;
            li.onclick = () => updatePage(i);
            paginationContainer.appendChild(li);
        }

        const nextLi = document.createElement("li");
        nextLi.className = `flex ${safePage >= totalPages ? "opacity-50" : ""}`;
        nextLi.innerHTML = `<span class="bg-gray-200 text-gray-600 rounded hover:bg-blue-500 hover:text-white cursor-pointer"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></span>`;
        if (safePage < totalPages) nextLi.onclick = () => updatePage(safePage + 1);
        paginationContainer.appendChild(nextLi);
    }

    function renderRows(rowsData) {
        if (!rowsData?.length) {
            tbody.innerHTML = `<tr><td colspan="${columns.length + (withSelection ? 1 : 0)}" class="text-center py-8 text-gray-500">No data</td></tr>`;
            return;
        }

        tbody.innerHTML = rowsData.map(row => {
            const rowId = row[primaryKey];
            const isSelected = selectedItems.has(String(rowId));

            // ✅ Check if this specific ID is in the selectable list
            const isSelectable = !selectableIds || selectableIds.has(String(rowId));

            const checkboxCell = withSelection ? `
                <td class="pl-4">
                    ${isSelectable
                ? `<input type="checkbox" class="row-checkbox rounded border-gray-300 text-blue-600 w-4 h-4 cursor-pointer" value="${rowId}" ${isSelected ? 'checked' : ''}>`
                : `<span class="text-gray-300 text-xs">—</span>`
            }
                </td>` : '';

            const cells = rowNames.map(key => {
                let v = row[key];

                // ✅ Check if there's a custom renderer for this column
                if (customRenderers && customRenderers[key]) {
                    const customHtml = customRenderers[key](v, row);
                    return `<td>${customHtml}</td>`;
                }

                // ⚠️ Default rendering (fallback only)

                // Status Badge - basic red/green only
                if (key.toLowerCase().includes("status")) {
                    const statusClass = v?.toLowerCase() === 'active' ? "bg-green-600" : "bg-red-600";
                    return `<td><span class="${statusClass} text-white px-3 py-1 rounded-lg text-xs font-medium">${v || 'N/A'}</span></td>`;
                }

                // Empty values
                if (v === undefined || v === null || v === "") {
                    return `<td><span class="text-gray-400 italic text-sm">empty</span></td>`;
                }

                // Images
                if (key === "image" || key === "icon") {
                    return `<td><img src="${v}" class="w-12 h-12 object-cover rounded"/></td>`;
                }

                // Long text (like session IDs)
                if (key.toLowerCase().includes("session") && v.length > 15) {
                    return `<td title="${v}" class="text-sm font-mono">${v.substring(0,12)}...</td>`;
                }

                return `<td class="text-sm">${v}</td>`;
            }).join("");

            return `<tr class="${isSelected ? 'bg-blue-50' : ''}">${checkboxCell}${cells}</tr>`;
        }).join("");

        if (withSelection) {
            const all = container.querySelectorAll(".row-checkbox");
            const allChecked = Array.from(all).every(c => c.checked);
            const some = Array.from(all).some(c => c.checked);
            const sel = container.querySelector("#select-all");
            if (sel) { sel.checked = allChecked; sel.indeterminate = some && !allChecked; }
        }
    }

    // Selection events
    if (withSelection) {
        container.addEventListener("change", e => {
            if (e.target.id === "select-all") {
                const checked = e.target.checked;
                container.querySelectorAll(".row-checkbox").forEach(cb => {
                    cb.checked = checked;
                    checked ? selectedItems.add(cb.value) : selectedItems.delete(cb.value);
                });
                renderRows(tableContent);
                if (onSelectionChangeDefault) onSelectionChangeDefault(selectedItems);
            } else if (e.target.classList.contains("row-checkbox")) {
                e.target.checked ? selectedItems.add(e.target.value) : selectedItems.delete(e.target.value);
                renderRows(tableContent);
                if (onSelectionChangeDefault) onSelectionChangeDefault(selectedItems);
            }
        });
    }

    // Dropdown handling
    document.addEventListener("click", e => {
        document.querySelectorAll(".dropdown-menu").forEach(m => m.style.display = "none");
        if (e.target.closest(".dropdown-toggle")) {
            e.target.closest(".dropdown").querySelector(".dropdown-menu").style.display = "flex";
            e.stopPropagation();
        }
    });

    // Sorting (client-side for now)
    headersElements.forEach((h, idx) => {
        h.addEventListener("click", e => {
            const key = h.dataset.key;
            currentSort.asc = currentSort.key === key ? !currentSort.asc : true;
            currentSort.key = key;
            const rowKey = rows[idx];

            tableContent.sort((a, b) => {
                let vA = a[rowKey], vB = b[rowKey];
                if (vA == null) return 1;
                if (vB == null) return -1;
                const isNum = !isNaN(parseFloat(vA)) && !isNaN(parseFloat(vB));
                if (isNum) { vA = parseFloat(vA); vB = parseFloat(vB); }
                else { vA = vA.toString().toLowerCase(); vB = vB.toString().toLowerCase(); }
                return vA < vB ? (currentSort.asc ? -1 : 1) : vA > vB ? (currentSort.asc ? 1 : -1) : 0;
            });

            headersElements.forEach(hdr => {
                hdr.querySelector('.down')?.classList.remove('stroke-gray-600');
                hdr.querySelector('.up')?.classList.remove('stroke-gray-600');
            });
            e.target.closest('span')?.querySelector(currentSort.asc ? '.up' : '.down')?.classList.add('stroke-gray-600');
            renderRows(tableContent);
        });
    });

    // Per-page change - trigger event
    if (per_pageSelect) {
        per_pageSelect.addEventListener("change", function() {
            per_page = parseInt(this.value);
            triggerTableEvent('perPageChange', per_page);
        });
    }

    // Export functions
    function exportCSV() {
        const rows = [columns.join(","), ...tableContent.map(r => rowNames.map(k => {
            let v = r[k];
            if (typeof v === "string") v = `"${v.replace(/"/g, '""')}"`;
            return v ?? "";
        }).join(","))];
        const blob = new Blob([rows.join("\n")], {type: "text/csv"});
        const a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = "table.csv";
        a.click();
        URL.revokeObjectURL(a.href);
    }

    function exportExcel() {
        const html = `<table><tr>${columns.map(c => `<th>${c}</th>`).join("")}</tr>${tableContent.map(r => `<tr>${rowNames.map(k => `<td>${r[k]??""}</td>`).join("")}</tr>`).join("")}</table>`;
        const blob = new Blob([html], {type: "application/vnd.ms-excel"});
        const a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = "table.xls";
        a.click();
        URL.revokeObjectURL(a.href);
    }

    function exportPDF() {
        const w = window.open("", "_blank");
        const html = `<table border="1"><thead><tr>${columns.map(c => `<th>${c}</th>`).join("")}</tr></thead><tbody>${tableContent.map(r => `<tr>${rowNames.map(k => `<td>${r[k]??""}</td>`).join("")}</tr>`).join("")}</tbody></table>`;
        w.document.write(`<html><head><title>PDF</title><style>body{font-family:Arial;padding:20px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #333;padding:6px}th{background:#f4f4f4}</style></head><body><h2>Table</h2>${html}</body></html>`);
        w.document.close();
        w.print();
    }

    container.querySelector("#export-csv")?.addEventListener("click", exportCSV);
    container.querySelector("#export-excel")?.addEventListener("click", exportExcel);
    container.querySelector("#export-pdf")?.addEventListener("click", exportPDF);

    renderPagination();
    renderRows(tableContent);
}