/**
 * Created by Maatify.dev
 * User: Maatify.dev
 * Date: 2025-10-06
 * Time: 12:21 PM
 * https://www.Maatify.dev
 */

//##################### Global State ##########################
let tableData = [];
let headers = [];
let rows = [];
let per_page;

let pageNo = 1;
let tableCount = "";
let pageLast = 5;
let pagination = {};
// const per_page = 10;
let apiUrlDefault;
let paramsDefault;
let withSelectionDefault = false; // New global for selection mode
let primaryKeyDefault = 'id';
let selectedItems = new Set(); // New global for selected items
let onSelectionChangeDefault; // Global for selection callback

/**
 * ==============================================================================
 * Create Table & Fetch Data
 * ==============================================================================
 * This function sends a POST request to the API to fetch data, 
 * then calls TableComponent to render it.
 */
async function createTable(apiUrl = apiUrlDefault, params = paramsDefault, headersArg, rowsArg, withSelection = false, primaryKey = 'id', onSelectionChange = null) {
    apiUrlDefault = apiUrl;
    paramsDefault = params;
    per_page = params.per_page;
    withSelectionDefault = withSelection; // Store selection mode
    primaryKeyDefault = primaryKey;
    if (onSelectionChange) onSelectionChangeDefault = onSelectionChange;

    // Update globals if arguments are provided
    if (headersArg) headers = headersArg;
    if (rowsArg) rows = rowsArg;

    try {
        // Use the same structure as callback_handler
        const response = await fetch(`/api/${apiUrl}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(params)
        });

        const data = await response.json();
        console.log("createTable response:", data);

        // The backend seems to wrap the response in a `data` object with `response` code
        const { response: value } = data.data || {};

        // if (value === 200) {
            tableData = data.data;
            pagination = data.pagination;
            tableCount = data.pagination.total;
            
            // Pass global headers and rows if locals are not provided (though we updated globals above)
            TableComponent(tableData, headers, rows, pagination, "", withSelection, primaryKey, onSelectionChangeDefault);
            return data;
        // } else {
        //      console.error("API returned error:", data);
        // }
    } catch (error) {
        console.error("createTable error:", error);
        showAlert('d',error)
    }
};

/**
 * Get the currently selected items
 * @returns {Array} Array of selected item IDs
 */
function getSelectedItems() {
    return Array.from(selectedItems);
}

/**
 * ==============================================================================
 * Main Table Component
 * ==============================================================================
 * This function handles building the table UI, pagination, search, filtering, and exporting.
 * It is split into smaller internal functions for better readability and maintenance.
 */
// function TableComponent(data, columns, rowNames, pagination, actions, withSelection, primaryKey, onSelectionChange)
function TableComponent(data = tableData, columns = headers, rowNames = rows, pagination = {
    count: 82, // total items
    page: 1
}, actions = "", withSelection = false, primaryKey = 'id', onSelectionChange = null) {
    headers = columns;
    rows = rowNames;
    primaryKeyDefault = primaryKey;
    if (onSelectionChange) onSelectionChangeDefault = onSelectionChange;
    
    // --- State Setup ---
    let tableContent = [...data]; // Local copy of data for filtering/sorting
    let currentSort = { key: null, asc: true }; // Sort state

    // Calculate item numbers for the footer
    const firstNumber = pagination.page === 1 ? 1 : 1 + per_page * (pagination.page - 1);
    const lastItemNumber = pagination.page === 1 ? (per_page <= pagination.total ? per_page : pagination.total) : per_page * pagination.page <= pagination.total ? per_page * pagination.page : pagination.total;

    // --- Generic Container ---
    const container = document.querySelector("#table-container");

    // ==============================================================================
    // 1. Render Structure
    // ==============================================================================
    const renderStructure = () => {
        // Checkbox column header logic
        const checkboxHeader = withSelection ? `
            <th class="w-10 pl-4 py-3 bg-white">
                <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer">
            </th>` : '';

        container.innerHTML = `
        <div class="table-container bg-white rounded-lg p-4 shadow-lg ">
            <!-- Top Bar: Export Buttons, Search, Filter -->
            <div class="tableHeader flex flex-wrap justify-between items-center mb-4 gap-4">
               
            
                <!-- Export Buttons -->
                <div class="flex gap-2 table-options">
                    <button class="text-sm px-3 py-1 bg-white hover:bg-blue-400 hover:text-white transition-all duration-300 ease-in-out text-gray-500 cursor-pointer border border-main rounded-2xl" id="export-csv">Export CSV</button>
                    <button class="text-sm px-3 py-1 bg-white hover:bg-blue-400 hover:text-white transition-all duration-300 ease-in-out text-gray-500 cursor-pointer border border-main rounded-2xl" id="export-excel">Export Excel</button>
                    <button class="text-sm px-3 py-1 bg-white hover:bg-blue-400 hover:text-white transition-all duration-300 ease-in-out text-gray-500 cursor-pointer border border-main rounded-2xl" id="export-pdf">Export PDF</button>
                </div>
           
                
                <!-- Search Input -->
                <div class="form-group w-full md:w-1/2 lg:w-1/3 p-0">
                    <input class="w-full border border-gray-300 rounded-lg px-3 py-1 focus:outline-none focus:ring-2 focus:ring-main/50 text-sm" placeholder="search..." />
                </div>

                <!-- Filter Buttons -->
                <div class="filterTypes flex gap-2">
                    <span value="all" class="cursor-pointer text-sm px-2 py-1 rounded-lg [&.active]:bg-blue-600 [&.active]:text-white active hover:bg-blue-400">All</span>
                    <span value="active" class="cursor-pointer text-sm px-2 py-1 rounded-lg [&.active]:bg-blue-600 [&.active]:text-white hover:bg-blue-400 hover:text-white">Active</span>
                    <span value="draft" class="cursor-pointer text-sm px-2 py-1 rounded-lg [&.active]:bg-blue-600 [&.active]:text-white hover:bg-blue-400 hover:text-white">Draft</span>
                </div>
                

            </div>
                    
            <!-- The Table -->
            <div class="overflow-x-auto">
                <table class="table min-w-full ">
                    <thead class="bg-gray-50">
                        <tr>
                            ${checkboxHeader}
                            ${columns.map(col => `
                                <th data-key="${col.toLowerCase()}" class="font-[Poppins] pr-6 py-3 text-left text-xs font-medium text-gray-500 capitalize tracking-wider cursor-pointer bg-white">
                                    <span class="flex items-center flex-row justify-start">
                                        ${col}
                                        <span class="ml-2 gap-0 flex items-center flex-col justify-center"> 
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2 stroke-[1.5px] stroke-gray-100 rotate-[270deg] up">
                                                <path d="M3 3.732a1.5 1.5 0 0 1 2.305-1.265l6.706 4.267a1.5 1.5 0 0 1 0 2.531l-6.706 4.268A1.5 1.5 0 0 1 3 12.267V3.732Z" />
                                            </svg>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2 stroke-[1.5px] stroke-gray-100 rotate-90 down">
                                                <path d="M3 3.732a1.5 1.5 0 0 1 2.305-1.265l6.706 4.267a1.5 1.5 0 0 1 0 2.531l-6.706 4.268A1.5 1.5 0 0 1 3 12.267V3.732Z" />
                                            </svg>
                                        </span>
                                    </span>
                                </th>`).join('')}
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200"></tbody>
                </table>
            </div>

            <!-- Footer & Pagination -->
            <footer class="pt-4 p-5 w-full flex flex-wrap justify-between items-center">
                <div class="w-full md:w-1/2 flex justify-start items-center gap-3 mb-2 md:mb-0">
                    <p class="table-count text-sm text-gray-700"> <span>${firstNumber} to ${lastItemNumber}</span> Items of <span>${pagination.total}</span></p>
                    <div class="flex gap-1 items-center">
                        <span class="text-sm text-gray-700">show</span>
                        <select class="form-group-select border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-main/50 bg-white cursor-pointer">
                            <option value="10" ${per_page == 10 ? "selected" : ""}>10</option>
                            <option value="25" ${per_page == 25 ? "selected" : ""}>25</option>
                            <option value="50" ${per_page == 50 ? "selected" : ""}>50</option>
                            <option value="100" ${per_page == 100 ? "selected" : ""}>100</option>
                        </select>
                    </div>
                </div>
                <nav aria-label="Page navigation" class="w-full md:w-1/2 flex justify-end">
                    <ul class="pagination flex items-center gap-[1px]" id="pagination"></ul>
                </nav>
            </footer>
        </div>
    `;
    };

    // Call renderStructure
    renderStructure();

    // --- DOM References ---
    const tbody = container.querySelector("tbody");
    const searchInput = container.querySelector("input[placeholder='search...']");
    const filterBtns = container.querySelectorAll(".filterTypes span");
    const headersElements = container.querySelectorAll("th[data-key]");
    const paginationContainer = document.getElementById("pagination");
    const per_pageSelect = document.querySelector(".form-group-select");

    // ==============================================================================
    // 2. Fetch & Update Logic
    // ==============================================================================
    
    // Mock API for example purposes
    const api = "/your/api/endpoint";
    const initialParams = {};

    async function fetchData(params) {
        const response = await fetch(api + "?" + new URLSearchParams(params));
        const result = await response.json();
        return result;
    }

    async function updatePage(pageNumber) {
        // Pass withSelection mode to createTable to preserve it during pagination updates
        await createTable(apiUrlDefault, params = { ...paramsDefault, per_page: per_page, page_no: pageNumber }, undefined, undefined, withSelectionDefault, primaryKeyDefault, onSelectionChangeDefault);
        pagination.page = pageNumber;
        renderPagination();

        // Note: fetchData is mock, not really used in this hybrid implementation where createTable does the fetch
        // const updatedParams = { ...initialParams, per_page, page_no: pageNumber };
        // const callBackRes = await fetchData(updatedParams);

        // console.log("Loaded data for page:", pageNumber, callBackRes);

        // if (callBackRes.result?.pagination) {
        //     pagination = callBackRes.result.pagination;
        // }
    }

    // ==============================================================================
    // 3. Render Pagination
    // ==============================================================================
    function renderPagination() {
       try{ console.log("Rendering pagination for page:", pagination);
        // Update per_page when select changes
        per_pageSelect.addEventListener("change", async function () {
            per_page = parseInt(this.value);
            console.log(per_page);
            // Pass withSelection mode to createTable
            await createTable(apiUrlDefault, params = { ...paramsDefault, per_page: per_page, page_no: 1 }, undefined, undefined, withSelectionDefault, primaryKeyDefault, onSelectionChangeDefault);
        });
        
        paginationContainer.innerHTML = "";
        const totalPages = Math.ceil(pagination.total / per_page) || 1;
        console.log("Total pages:", totalPages);
        
        // Prev button
        const prevLi = document.createElement("li");
        prevLi.className = `flex items-center ${pagination.page <= 1 ? "disabled" : ""}`;
        prevLi.innerHTML = `<span class="bg-gray-200 text-white  py-0 rounded hover:bg-blue-500 hover:text-white cursor-pointer arrow" aria-label="Previous"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg></span>`;
        prevLi.onclick = () => {
            if (pagination.page > 1) updatePage(pagination.page - 1);
        };
        paginationContainer.appendChild(prevLi);

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            // console.log( i > pagination.page - 4, i < pagination.page + 4, i <= (pagination.total/pagination.per_page));
            // console.log(pagination.total/pagination.per_page)
            let pages = pagination.total/pagination.per_page>1?pagination.total/pagination.per_page:1;

            if (i > pagination.page - 4 && i < pagination.page + 4 && i <= pages) {
                const li = document.createElement("li");
                li.className = `flex items-center ${pagination.page === i ? "active" : ""}`;
                li.innerHTML = `<span class="page-link bg-blue-400 text-white px-2 py-0 rounded hover:bg-blue-600 hover:text-white cursor-pointer [&.active]:bg-blue-600 [&.active]:text-white ${pagination.page === i ? "active" : ""}">${i}</span>`;
                li.onclick = () => updatePage(i);
                paginationContainer.appendChild(li);
            }
        }

        // Next button
        const nextLi = document.createElement("li");
        nextLi.className = `flex items-center ${pagination.page >= totalPages ? "disabled" : ""}`;
        nextLi.innerHTML = `<span class="bg-gray-200 text-white  py-0 rounded hover:bg-blue-500 hover:text-white cursor-pointer arrow" aria-label="Next"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
</svg>
</span>`;
        nextLi.onclick = () => {
            if (pagination.page < totalPages) updatePage(pagination.page + 1);
        };
        paginationContainer.appendChild(nextLi);
    }
    catch (error) {
        console.error("Error rendering pagination:", error);}
    }

    // ==============================================================================
    // 4. Render Rows
    // ==============================================================================
    function renderRows(rowsData) {
        tbody.innerHTML = rowsData
            .map((row) => {
                // Determine if this row is selected
                // Use the primaryKey passed to TableComponent
                const rowId = row[primaryKey];
                const isSelected = selectedItems.has(String(rowId));
                const checkboxCell = withSelection ? `
                    <td class="pl-4">
                        <input type="checkbox" class="row-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer" 
                            value="${rowId}" ${isSelected ? 'checked' : ''}>
                    </td>` : '';

                const cells = rowNames
                    .map((key) => {
                        let value = row[key];

                        // Status Badge Styling
                        if (key.toLowerCase().includes("status")) {
                            const statusClasses = {
                                "active": "bg-green-600 text-white p-1 m-2 rounded-lg",
                                "draft": "bg-red-600 text-white p-1 px-2 m-2 rounded-lg",
                                "pending": "bg-yellow-600 text-white p-1 px-2 m-2 rounded-lg",
                                "revoked": "bg-gray-600 text-white p-1 px-2 m-2 rounded-lg",
                                "expired": "bg-red-600 text-white p-1 px-2 m-2 rounded-lg"
                            };
                            const badgeClass = statusClasses[value] || "bg-gray-600 text-white p-1 px-2 m-2 rounded-lg";
                            value = `<span class="badge badge-sm ${badgeClass}">${value == "active" ? value : value}</span>`;
                        }

                        // Handle empty values
                        if (value === undefined || value === null || value === "") {
                            value = `<span class="badge badge-sm badge-soft-info">empty data</span>`;
                            return `<td>${value}</td>`;
                        } else if (key == "image" || key == "icon") {
                            return `<td><img src="${value}" alt="${key}" width="50" height="50"/></td>`;
                        } 
                        else if (key.toLowerCase().includes("session")) {
                            return `<td title="${value}" >${value.substring(0, 10)+"..."}</td>`;
                        }
                        else {
                            return `<td>${value}</td>`;
                        }
                    })
                    .join("");

                // Actions Dropdown
                let settingsDropdown = "";
                if (columns.map(c => c.toLowerCase()).includes("settings")) {
                    settingsDropdown = `
                    <td>
                        <div class="dropdown text-center">
                            <button class="btn btn-sm btn-light border dropdown-toggle p-1 py-0" 
                                type="button" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false">
                                <i class="hgi hgi-stroke hgi-more-vertical-circle-02"></i>             
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                              ${actions}
                            </ul>
                        </div>
                    </td>
                `;
                }
                return `<tr class="${isSelected ? 'bg-blue-50' : ''}">${checkboxCell}${cells}${settingsDropdown}</tr>`;
            })
            .join("");
        
        // Update "Select All" checkbox state
        updateSelectAllState();
    }

    // Helper to update "Select All" based on currently visible rows
    function updateSelectAllState() {
        if (!withSelection) return;
        const allCheckboxes = container.querySelectorAll(".row-checkbox");
        if (allCheckboxes.length === 0) return;

        const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
        const selectAllCheckbox = container.querySelector("#select-all");
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = !allChecked && Array.from(allCheckboxes).some(cb => cb.checked);
        }
    }

    // ==============================================================================
    // 5. Setup Event Listeners
    // ==============================================================================
    
    // --- Selection Listeners (Delegated) ---
    if (withSelection) {
        // Toggle Select All
        container.addEventListener("change", (e) => {
            if (e.target.id === "select-all") {
                const isChecked = e.target.checked;
                const rowCheckboxes = container.querySelectorAll(".row-checkbox");
                rowCheckboxes.forEach(cb => {
                    cb.checked = isChecked;
                    // Update global state
                    if (isChecked) {
                        selectedItems.add(cb.value);
                    } else {
                        selectedItems.delete(cb.value);
                    }
                });
                renderRows(tableContent); // Re-render to update row styling
                if (onSelectionChangeDefault) onSelectionChangeDefault(selectedItems); // Trigger Callback
            }
        });

        // Toggle Single Row
        container.addEventListener("change", (e) => {
            if (e.target.classList.contains("row-checkbox")) {
                const value = e.target.value;
                if (e.target.checked) {
                    selectedItems.add(value);
                } else {
                    selectedItems.delete(value);
                }
                renderRows(tableContent); // Re-render to update row styling/select-all state
                if (onSelectionChangeDefault) onSelectionChangeDefault(selectedItems); // Trigger Callback
            }
        });
    }

    // --- Close dropdowns when clicking outside ---
    document.addEventListener("click", (e) => {
        document.querySelectorAll(".dropdown-menu").forEach((menu) => {
            menu.style.display = "none";
        });
        if (e.target.closest(".dropdown-toggle")) {
            const dropdown = e.target.closest(".dropdown");
            const menu = dropdown.querySelector(".dropdown-menu");
            menu.style.display = menu.style.display === "none" ? "flex" : "flex";
            e.stopPropagation();
        }
        // Handle action button click
        const item = e.target.closest(".dropdown-item");
        if (item) {
            const action = item.dataset.action;
            console.log("Action clicked:", action);
        }
    });

    // --- Search ---
    searchInput.addEventListener("keyup", (e) => {
        const value = e.target.value.toLowerCase().trim();
        if (value === "") {
            tableContent = [...data];
            renderRows(tableContent);
            return;
        }
        const filtered = data.filter((row) => {
            return rowNames.some((key) => {
                const fieldValue = row[key];
                if (fieldValue == null) return false;
                return fieldValue.toString().toLowerCase().includes(value);
            });
        });
        tableContent = filtered;
        renderRows(tableContent);
    });

    // --- Filter ---
    filterBtns.forEach((btn) => {
        btn.addEventListener("click", () => {
            filterBtns.forEach((b) => b.classList.remove("active"));
            btn.classList.add("active");
            const value = btn.getAttribute("value");
            console.log(value);
            tableContent = value === "all" ? data : data.filter((item) => item.status == value);
            renderRows(tableContent);
        });
    });

    // --- Sort ---
    headersElements.forEach((header) => {
        header.style.cursor = "pointer";
        header.addEventListener("click", (e) => {
            const key = header.dataset.key;
            const isSameColumn = currentSort.key === key;
            currentSort.asc = isSameColumn ? !currentSort.asc : true;
            currentSort.key = key;

            tableContent.sort((a, b) => {
                let valA = a[rows[header.cellIndex-1]];
                let valB = b[rows[header.cellIndex-1]];

                const isNumeric = !isNaN(parseFloat(valA)) && !isNaN(parseFloat(valB));
                if (isNumeric) {
                    valA = parseFloat(valA);
                    valB = parseFloat(valB);
                } else {
                    valA = valA.toString().toLowerCase();
                    valB = valB.toString().toLowerCase();
                }

                if (valA < valB) return currentSort.asc ? -1 : 1;
                if (valA > valB) return currentSort.asc ? 1 : -1;
                return 0;
            });

            // Update Sort Icons
            if (currentSort.asc && e.target.closest('span').querySelector('.up')) {
                headersElements.forEach((h) => {
                    h.querySelector('.down').classList.remove('stroke-gray-600');
                    h.querySelector('.up').classList.remove('stroke-gray-600');
                });
                e.target.closest('span').querySelector('.up').classList.add('stroke-gray-600');
                e.target.closest('span').querySelector('.down').classList.remove('stroke-gray-600');
            } else {
                e.target.closest('span').querySelector('.down').classList.add('stroke-gray-600');
                e.target.closest('span').querySelector('.up').classList.remove('stroke-gray-600');
            }
            renderRows(tableContent);
        });
    });

    // ==============================================================================
    // 6. Export Functions
    // ==============================================================================
    
    function exportToCSV() {
        const csvRows = [];
        csvRows.push(columns.join(","));
        tableContent.forEach((row) => {
            const rowData = rowNames.map((key) => {
                let value = row[key];
                if (typeof value === "string")
                    value = `"${value.replace(/"/g, '""')}"`;
                return value ?? "";
            });
            csvRows.push(rowData.join(","));
        });

        const blob = new Blob([csvRows.join("\n")], { type: "text/csv" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = document.querySelector('head title').textContent + "-table-data.csv";
        a.click();
    }

    function exportToExcel() {
        let tableHTML = `
        <table>
            <tr>${columns.map((col) => `<th>${col}</th>`).join("")}</tr>
            ${tableContent.map((row) => {
                return `<tr>${rowNames.map((key) => `<td>${row[key] ?? ""}</td>`).join("")}</tr>`;
            }).join("")}
        </table>`;

        const blob = new Blob([tableHTML], { type: "application/vnd.ms-excel" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = document.querySelector('head title').textContent + "-table-data.xls";
        a.click();
    }

    function exportToPDF() {
        const printWindow = window.open("", "_blank");
        const tableHTML = `
        <table border="1" cellspacing="0" cellpadding="4">
            <thead>
                <tr>${columns.map((col) => `<th>${col}</th>`).join("")}</tr>
            </thead>
            <tbody>
                ${tableContent.map((row) => {
                    return `<tr>${rowNames.map((key) => `<td>${row[key] ?? ""}</td>`).join("")}</tr>`;
                }).join("")}
            </tbody>
        </table>`;

        printWindow.document.write(`
        <html>
        <head>
            <title>Exported ${document.querySelector('head title').textContent} Table PDF</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h2 { text-align: center; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #333; padding: 6px 10px; text-align: left; }
                th { background: #f4f4f4; }
            </style>
        </head>
        <body>
            <img width="150" style="margin-left:calc(50% - 75px)" src="../img/logo.png">
            <h2>Exported ${document.querySelector('head title').textContent} Of Data Table </h2>
            ${tableHTML}
        </body>
        </html>`);
        printWindow.document.close();
        printWindow.print();
    }

    // Attach Export Listeners
    container.querySelector("#export-csv").addEventListener("click", exportToCSV);
    container.querySelector("#export-excel").addEventListener("click", exportToExcel);
    container.querySelector("#export-pdf").addEventListener("click", exportToPDF);

    // ==============================================================================
    // 7. Initial Render
    // ==============================================================================
    renderPagination();
    renderRows(tableContent);
}
