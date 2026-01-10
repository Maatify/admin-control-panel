document.addEventListener('DOMContentLoaded', function() {
    // State
    let currentPage = 1;
    let perPage = 10;

    let currentSearch = {
        global: '',
        id: '',
        email: ''
    };

    let currentDate = {
        from: '',
        to: ''
    };

    // Elements
    const tableBody = document.querySelector('#admins-table tbody');
    const paginationInfo = document.getElementById('pagination-info');
    const paginationControls = document.getElementById('pagination-controls');

    const searchForm = document.getElementById('admins-search-form');
    const searchGlobalInput = document.getElementById('search-global');
    const filterIdInput = document.getElementById('filter-id');
    const filterEmailInput = document.getElementById('filter-email');
    const dateFromInput = document.getElementById('date-from');
    const dateToInput = document.getElementById('date-to');

    const resetButton = document.getElementById('btn-reset');
    const perPageSelect = document.getElementById('per-page-select');

    // Init
    loadAdmins();

    // Event Listeners
    perPageSelect.addEventListener('change', function() {
        perPage = parseInt(this.value, 10);
        currentPage = 1; // Reset to first page
        loadAdmins();
    });

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();

        currentSearch = {
            global: searchGlobalInput.value,
            id: filterIdInput.value,
            email: filterEmailInput.value
        };

        currentDate = {
            from: dateFromInput.value,
            to: dateToInput.value
        };

        currentPage = 1; // Reset to first page
        loadAdmins();
    });

    resetButton.addEventListener('click', function() {
        searchGlobalInput.value = '';
        filterIdInput.value = '';
        filterEmailInput.value = '';
        dateFromInput.value = '';
        dateToInput.value = '';

        currentSearch = { global: '', id: '', email: '' };
        currentDate = { from: '', to: '' };

        currentPage = 1;
        loadAdmins();
    });

    // Main Load Function
    async function loadAdmins() {
        setLoading();

        // Build Payload
        const payload = {
            page: currentPage,
            per_page: perPage
        };

        const filters = {};
        const columns = {};

        // Global
        if (currentSearch.global.trim()) {
            filters.global = currentSearch.global.trim();
        }

        // Columns
        if (currentSearch.id.trim()) {
            columns.id = currentSearch.id.trim();
        }
        if (currentSearch.email.trim()) {
            columns.email = currentSearch.email.trim();
        }

        if (Object.keys(columns).length > 0) {
            filters.columns = columns;
        }

        // Date
        if (currentDate.from) filters.date_from = currentDate.from;
        if (currentDate.to) filters.date_to = currentDate.to;

        if (Object.keys(filters).length > 0) {
            payload.filters = filters;
        }

        try {
            const response = await fetch('/api/admins/query', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error('Failed to load admins');
            }

            const result = await response.json();
            renderTable(result.data);
            renderPagination(result.pagination);

        } catch (error) {
            console.error('Error:', error);
            tableBody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Error loading data: ' + escapeHtml(error.message) + '</td></tr>';
        }
    }

    function setLoading() {
        tableBody.innerHTML = '<tr><td colspan="3" class="text-center">Loading...</td></tr>';
    }

    function renderTable(data) {
        if (!data || data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="3" class="text-center">No admins found</td></tr>';
            return;
        }

        tableBody.innerHTML = data.map(admin => {
            return `
            <tr>
                <td>${escapeHtml(admin.id)}</td>
                <td>${escapeHtml(admin.email)}</td>
                <td>${escapeHtml(admin.created_at)}</td>
            </tr>
        `;
        }).join('');
    }

    function renderPagination(pagination) {
        if (!pagination) return;

        const { page, per_page, total, filtered } = pagination;
        const effectiveTotal = (typeof filtered !== 'undefined') ? filtered : total;
        const totalPages = Math.ceil(effectiveTotal / per_page);

        const start = effectiveTotal === 0 ? 0 : (page - 1) * per_page + 1;
        const end = Math.min(page * per_page, effectiveTotal);

        paginationInfo.textContent = 'Showing ' + start + ' to ' + end + ' of ' + effectiveTotal + ' entries';

        let html = '';

        // Prev
        html += '<li class="page-item ' + (page === 1 ? 'disabled' : '') + '">';
        html += '<button class="page-link" onclick="changePage(' + (page - 1) + ')">Previous</button></li>';

        // Simple pagination logic
        for (let i = 1; i <= totalPages; i++) {
             if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
                html += '<li class="page-item ' + (i === page ? 'active' : '') + '">';
                html += '<button class="page-link" onclick="changePage(' + i + ')">' + i + '</button></li>';
             } else if (i === page - 3 || i === page + 3) {
                 html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
             }
        }

        // Next
        html += '<li class="page-item ' + (page === totalPages || effectiveTotal === 0 ? 'disabled' : '') + '">';
        html += '<button class="page-link" onclick="changePage(' + (page + 1) + ')">Next</button></li>';

        paginationControls.innerHTML = html;

        // Expose changePage globally for onclick
        window.changePage = function(newPage) {
            if (newPage > 0 && newPage <= totalPages) {
                currentPage = newPage;
                loadAdmins();
            }
        }
    }

    function escapeHtml(text) {
        if (text == null) return '';
        return text.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
