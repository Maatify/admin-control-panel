document.addEventListener('DOMContentLoaded', function() {
    // State
    let currentPage = 1;
    let perPage = 20;
    let filters = {};

    // Elements
    const tableBody = document.querySelector('#sessions-table tbody');
    const paginationInfo = document.getElementById('pagination-info');
    const paginationControls = document.getElementById('pagination-controls');
    const searchForm = document.getElementById('sessions-search-form');
    const resetButton = document.getElementById('btn-reset');
    const perPageSelect = document.getElementById('per-page-select');

    // Init
    loadSessions();

    // Event Listeners
    perPageSelect.addEventListener('change', function() {
        perPage = parseInt(this.value, 10);
        currentPage = 1; // Reset to first page
        loadSessions();
    });

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        filters = {
            session_id: document.getElementById('filter-session-id').value,
            status: document.getElementById('filter-status').value
        };
        currentPage = 1; // Reset to first page on search
        loadSessions();
    });

    resetButton.addEventListener('click', function() {
        document.getElementById('filter-session-id').value = '';
        document.getElementById('filter-status').value = '';
        filters = {};
        currentPage = 1;
        loadSessions();
    });

    // Main Load Function
    async function loadSessions() {
        setLoading();

        try {
            const response = await fetch('/api/sessions/query', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': getAuthToken()
                },
                body: JSON.stringify({
                    page: currentPage,
                    per_page: perPage,
                    filters: filters
                })
            });

            if (!response.ok) {
                throw new Error('Failed to load sessions');
            }

            const result = await response.json();
            renderTable(result.data);
            renderPagination(result.pagination);

        } catch (error) {
            console.error('Error:', error);
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading data: ' + error.message + '</td></tr>';
        }
    }

    function setLoading() {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';
    }

    function renderTable(data) {
        if (!data || data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No sessions found</td></tr>';
            return;
        }

        tableBody.innerHTML = data.map(item => `
            <tr>
                <td><code>${escapeHtml(item.session_id)}</code></td>
                <td>${escapeHtml(item.created_at)}</td>
                <td>${escapeHtml(item.expires_at)}</td>
                <td>${getStatusBadge(item.status)}</td>
                <td>
                    ${getActionButtons(item)}
                </td>
            </tr>
        `).join('');
    }

    function getStatusBadge(status) {
        switch(status) {
            case 'active': return '<span class="badge bg-success">Active</span>';
            case 'revoked': return '<span class="badge bg-danger">Revoked</span>';
            case 'expired': return '<span class="badge bg-secondary">Expired</span>';
            default: return '<span class="badge bg-light text-dark">' + escapeHtml(status) + '</span>';
        }
    }

    function getActionButtons(item) {
        if (item.status === 'active') {
             return '<button class="btn btn-sm btn-outline-danger btn-revoke" data-id="' + item.session_id + '">Revoke</button>';
        }
        return '';
    }

    // Delegation for dynamic buttons
    tableBody.addEventListener('click', async function(e) {
        if (e.target.classList.contains('btn-revoke')) {
            const sessionId = e.target.getAttribute('data-id');
            if (confirm('Are you sure you want to revoke this session?')) {
                await revokeSession(sessionId);
            }
        }
    });

    async function revokeSession(sessionId) {
        try {
            const response = await fetch('/api/sessions/' + sessionId + '/revoke', {
                method: 'POST',
                 headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                loadSessions(); // Reload table
            } else {
                alert('Failed to revoke session');
            }
        } catch (e) {
            console.error(e);
            alert('Error revoking session');
        }
    }

    function renderPagination(pagination) {
        const { page, per_page, total } = pagination;
        const totalPages = Math.ceil(total / per_page);

        const start = (page - 1) * per_page + 1;
        const end = Math.min(page * per_page, total);

        paginationInfo.textContent = 'Showing ' + start + ' to ' + end + ' of ' + total + ' entries';

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
        html += '<li class="page-item ' + (page === totalPages || total === 0 ? 'disabled' : '') + '">';
        html += '<button class="page-link" onclick="changePage(' + (page + 1) + ')">Next</button></li>';

        paginationControls.innerHTML = html;

        // Expose changePage globally for onclick
        window.changePage = function(newPage) {
            if (newPage > 0 && newPage <= totalPages) {
                currentPage = newPage;
                loadSessions();
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

    function getAuthToken() {
        return '';
    }
});
