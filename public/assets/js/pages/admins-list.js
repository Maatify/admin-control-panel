document.addEventListener('DOMContentLoaded', function() {
    // State
    let currentPage = 1;
    let perPage = 20;
    let searchQuery = '';

    // Elements
    const tableBody = document.querySelector('#admins-table tbody');
    const paginationInfo = document.getElementById('pagination-info');
    const paginationControls = document.getElementById('pagination-controls');
    const searchForm = document.getElementById('admins-search-form');
    const searchInput = document.getElementById('search-global');
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
        searchQuery = searchInput.value.trim();
        currentPage = 1; // Reset to first page
        loadAdmins();
    });

    resetButton.addEventListener('click', function() {
        searchInput.value = '';
        searchQuery = '';
        currentPage = 1;
        loadAdmins();
    });

    // Main Load Function
    async function loadAdmins() {
        setLoading();

        // Build canonical payload
        const payload = {
            page: currentPage,
            per_page: perPage
        };

        if (searchQuery !== '') {
            payload.search = {
                global: searchQuery
            };
        }

        console.log(payload);

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

            // Validate response shape per Canonical Contract (Read-Only)
            if (!result.data || !result.pagination) {
                 throw new Error('Invalid response format');
            }

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
        // Read-only access to pagination object
        const page = pagination.page;
        const total = pagination.total;
        const per_page = pagination.per_page;

        // Render Info Text (Allowed UI State)
        paginationInfo.textContent = 'Page ' + page + ' (Total: ' + total + ')';

        let html = '';

        // Prev Button
        const hasPrev = page > 1;
        html += '<li class="page-item ' + (hasPrev ? '' : 'disabled') + '">';
        html += '<button class="page-link" onclick="changePage(' + (page - 1) + ')">Previous</button></li>';

        // Current Page Indicator (Static)
        html += '<li class="page-item active"><span class="page-link">' + page + '</span></li>';

        // Next Button
        // We determine "Next" availability strictly by verifying if we have exhausted the total.
        // This is a rendering state decision, not "calculation of pages".
        const hasNext = (page * per_page) < total;

        html += '<li class="page-item ' + (hasNext ? '' : 'disabled') + '">';
        html += '<button class="page-link" onclick="changePage(' + (page + 1) + ')">Next</button></li>';

        paginationControls.innerHTML = html;

        // Expose changePage globally for onclick
        window.changePage = function(newPage) {
            if (newPage < 1) return;
            // We allow clicking next; the server will return empty if out of bounds, or we rely on the button disabled state.
            currentPage = newPage;
            loadAdmins();
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
