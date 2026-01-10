document.addEventListener('DOMContentLoaded', function() {
    // State
    let currentPage = 1;
    let perPage = 20;

    let currentSearch = {
        global: '',
        sessionId: '',
        status: ''
    };

    let currentDate = {
        from: '',
        to: ''
    };

    let selectedSessions = new Set(); // Store hashes

    // Elements
    const tableBody = document.querySelector('#sessions-table tbody');
    const paginationInfo = document.getElementById('pagination-info');
    const paginationControls = document.getElementById('pagination-controls');

    const searchForm = document.getElementById('sessions-search-form');
    const searchGlobalInput = document.getElementById('search-global');
    const filterSessionIdInput = document.getElementById('filter-session-id');
    const filterStatusSelect = document.getElementById('filter-status');
    const dateFromInput = document.getElementById('date-from');
    const dateToInput = document.getElementById('date-to');

    const resetButton = document.getElementById('btn-reset');
    const perPageSelect = document.getElementById('per-page-select');

    const selectAllCheckbox = document.getElementById('select-all');
    const bulkRevokeBtn = document.getElementById('btn-bulk-revoke');
    const selectedCountBadge = document.getElementById('selected-count');

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

        currentSearch = {
            global: searchGlobalInput.value,
            sessionId: filterSessionIdInput.value,
            status: filterStatusSelect.value
        };

        currentDate = {
            from: dateFromInput.value,
            to: dateToInput.value
        };

        currentPage = 1; // Reset to first page on search
        selectedSessions.clear(); // Reset selection on filter change
        updateBulkUI();
        loadSessions();
    });

    resetButton.addEventListener('click', function() {
        searchGlobalInput.value = '';
        filterSessionIdInput.value = '';
        filterStatusSelect.value = '';
        dateFromInput.value = '';
        dateToInput.value = '';

        currentSearch = { global: '', sessionId: '', status: '' };
        currentDate = { from: '', to: '' };

        currentPage = 1;
        selectedSessions.clear();
        updateBulkUI();
        loadSessions();
    });

    selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.session-select:not(:disabled)');
        const isChecked = this.checked;

        checkboxes.forEach(cb => {
            cb.checked = isChecked;
            const hash = cb.value;
            if (isChecked) {
                selectedSessions.add(hash);
            } else {
                selectedSessions.delete(hash);
            }
        });
        updateBulkUI();
    });

    bulkRevokeBtn.addEventListener('click', async function() {
        if (selectedSessions.size === 0) return;

        if (confirm('Are you sure you want to revoke ' + selectedSessions.size + ' session(s)?')) {
            await revokeBulk();
        }
    });

    // Main Load Function
    async function loadSessions() {
        setLoading();
        // Reset header checkbox state for new page
        selectAllCheckbox.checked = false;

        // Build Payload
        const payload = {
            page: currentPage,
            per_page: perPage
        };

        const search = {};
        const columns = {};

        // Global
        if (currentSearch.global.trim()) {
            search.global = currentSearch.global.trim();
        }

        // Columns
        if (currentSearch.sessionId.trim()) {
            columns.session_id = currentSearch.sessionId.trim();
        }
        if (currentSearch.status.trim()) {
            columns.status = currentSearch.status.trim();
        }

        if (Object.keys(columns).length > 0) {
            search.columns = columns;
        }

        // Add search object if not empty
        if (Object.keys(search).length > 0) {
            payload.search = search;
        }

        // Date
        const date = {};
        if (currentDate.from) date.from = currentDate.from;
        if (currentDate.to) date.to = currentDate.to;

        // Add date object if not empty
        if (Object.keys(date).length > 0) {
            payload.date = date;
        }

        try {
            const response = await fetch('/api/sessions/query', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': getAuthToken()
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error('Failed to load sessions');
            }

            const result = await response.json();
            renderTable(result.data);
            renderPagination(result.pagination);

        } catch (error) {
            console.error('Error:', error);
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading data: ' + escapeHtml(error.message) + '</td></tr>';
        }
    }

    function setLoading() {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';
    }

    function renderTable(data) {
        if (!data || data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No sessions found</td></tr>';
            return;
        }

        tableBody.innerHTML = data.map(item => {
            const isRevocable = item.status === 'active' && !item.is_current;
            const checkboxHtml = isRevocable ? `
                <input type="checkbox"
                       class="form-check-input session-select"
                       value="${escapeHtml(item.session_id)}"
                       ${selectedSessions.has(item.session_id) ? 'checked' : ''}
                >
            ` : '';

            return `
            <tr class="${item.is_current ? 'table-info' : ''}">
                <td class="text-center">
                    ${checkboxHtml}
                </td>
                <td><code>${escapeHtml(item.session_id)}</code></td>
                <td>
                    ${escapeHtml(item.admin_identifier)}
                    ${item.is_current ? ' <span class="badge bg-primary ms-1">Current</span>' : ''}
                </td>
                <td>${escapeHtml(item.created_at)}</td>
                <td>${escapeHtml(item.expires_at)}</td>
                <td>${getStatusBadge(item.status)}</td>
                <td>
                    ${getActionButtons(item)}
                </td>
            </tr>
        `;
        }).join('');

        // Update select all checkbox state if all selectable items are selected
        updateSelectAllState();
    }

    function updateSelectAllState() {
        const checkboxes = document.querySelectorAll('.session-select:not(:disabled)');
        if (checkboxes.length > 0) {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            selectAllCheckbox.checked = allChecked;
        } else {
            selectAllCheckbox.checked = false;
        }
    }

    function updateBulkUI() {
        selectedCountBadge.textContent = selectedSessions.size;
        bulkRevokeBtn.disabled = selectedSessions.size === 0;
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
        if (item.is_current) {
            return '<button class="btn btn-sm btn-outline-secondary" disabled title="You cannot revoke the session you are currently using">Revoke</button>';
        }
        if (item.status === 'active') {
             return '<button class="btn btn-sm btn-outline-danger btn-revoke" data-id="' + item.session_id + '">Revoke</button>';
        }
        return '';
    }

    // Delegation for dynamic buttons/checkboxes
    tableBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('session-select')) {
            const hash = e.target.value;
            if (e.target.checked) {
                selectedSessions.add(hash);
            } else {
                selectedSessions.delete(hash);
            }
            updateSelectAllState();
            updateBulkUI();
        }
    });

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
            const response = await fetch('/api/sessions/' + sessionId, {
                method: 'DELETE',
                 headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                selectedSessions.delete(sessionId);
                updateBulkUI();
                loadSessions(); // Reload table
                showAlert('Session revoked successfully.');
            } else {
                try {
                    const data = await response.json();
                    alert('Failed to revoke session: ' + (data.error || 'Unknown error'));
                } catch (e) {
                    alert('Failed to revoke session');
                }
            }
        } catch (e) {
            console.error(e);
            alert('Error revoking session');
        }
    }

    async function revokeBulk() {
        try {
             const response = await fetch('/api/sessions/revoke-bulk', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    session_ids: Array.from(selectedSessions)
                })
            });

             if (response.ok) {
                const count = selectedSessions.size;
                selectedSessions.clear();
                updateBulkUI();
                loadSessions(); // Reload table
                showAlert(count + ' sessions revoked successfully.');
            } else {
                try {
                    const data = await response.json();
                    alert('Failed to bulk revoke: ' + (data.error || 'Unknown error'));
                } catch (e) {
                    alert('Failed to bulk revoke');
                }
            }
        } catch (e) {
             console.error(e);
             alert('Error performing bulk revoke');
        }
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

    function showAlert(message, type = 'success') {
        const alertContainer = document.getElementById('alert-container');
        if (alertContainer) {
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
        }
    }
});
