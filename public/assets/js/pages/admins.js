document.addEventListener('DOMContentLoaded', function() {
    // List State
    let currentPage = 1;
    let perPage = 20;
    let filters = {};

    // List Elements
    const tableBody = document.querySelector('#admins-table tbody');
    const paginationInfo = document.getElementById('pagination-info');
    const paginationControls = document.getElementById('pagination-controls');
    const searchForm = document.getElementById('admins-search-form');
    const resetButton = document.getElementById('btn-reset');
    const perPageSelect = document.getElementById('per-page-select');

    // Forms
    const createForm = document.getElementById('admin-create-form');
    const editForm = document.getElementById('admin-edit-form');

    // Init List
    if (tableBody) {
        // Check for actions in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action') === 'created') {
            showAlert('Admin created successfully.', 'success');
            // Clean URL
            window.history.replaceState({}, document.title, '/admins');
        } else if (urlParams.get('action') === 'updated') {
            showAlert('Admin updated successfully.', 'success');
            window.history.replaceState({}, document.title, '/admins');
        }

        loadAdmins();

        // Event Listeners for List
        perPageSelect.addEventListener('change', function() {
            perPage = parseInt(this.value, 10);
            currentPage = 1;
            loadAdmins();
        });

        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            filters = {
                id: document.getElementById('filter-id').value
            };
            currentPage = 1;
            loadAdmins();
        });

        resetButton.addEventListener('click', function() {
            document.getElementById('filter-id').value = '';
            filters = {};
            currentPage = 1;
            loadAdmins();
        });
    }

    // Init Create
    if (createForm) {
        createForm.addEventListener('submit', handleCreate);
    }

    // Init Edit
    if (editForm) {
        editForm.addEventListener('submit', handleEdit);
    }

    // --- List Functions ---

    async function loadAdmins() {
        setLoading();

        try {
            const response = await fetch('/api/admins/query', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    page: currentPage,
                    per_page: perPage,
                    filters: filters
                })
            });

            if (!response.ok) {
                if (response.status === 401) window.location.href = '/login';
                throw new Error('Failed to load admins');
            }

            const result = await response.json();
            renderTable(result.data);
            renderPagination(result.pagination);

        } catch (error) {
            console.error('Error:', error);
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading data: ' + escapeHtml(error.message) + '</td></tr>';
        }
    }

    function setLoading() {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';
    }

    function renderTable(data) {
        if (!data || data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No admins found</td></tr>';
            return;
        }

        tableBody.innerHTML = data.map(admin => {
            return `
            <tr>
                <td>${admin.id}</td>
                <td>${escapeHtml(admin.identifier)}</td>
                <td>${getStatusBadge(admin.verification_status)}</td>
                <td>${escapeHtml(admin.roles.join(', '))}</td>
                <td>${escapeHtml(admin.created_at)}</td>
                <td>
                    <a href="/admins/${admin.id}/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
                    <button class="btn btn-sm btn-outline-danger btn-disable" data-id="${admin.id}">Disable</button>
                </td>
            </tr>
        `;
        }).join('');
    }

    function getStatusBadge(status) {
        switch(status) {
            case 'verified': return '<span class="badge bg-success">Verified</span>';
            case 'pending': return '<span class="badge bg-warning text-dark">Pending</span>';
            case 'failed': return '<span class="badge bg-danger">Disabled</span>';
            default: return '<span class="badge bg-light text-dark">' + escapeHtml(status) + '</span>';
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

        // Pages
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

        // Expose changePage globally
        window.changePage = function(newPage) {
            if (newPage > 0 && newPage <= totalPages) {
                currentPage = newPage;
                loadAdmins();
            }
        }
    }

    // Global Event Delegation for Disable Button
    if (tableBody) {
        tableBody.addEventListener('click', async function(e) {
            if (e.target.classList.contains('btn-disable')) {
                const id = e.target.getAttribute('data-id');
                if (confirm('Are you sure you want to disable this admin? This will revoke all sessions.')) {
                    await disableAdmin(id);
                }
            }
        });
    }

    async function disableAdmin(id) {
        try {
            const response = await fetch('/api/admins/' + id + '/disable', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });

            if (response.ok) {
                loadAdmins();
                showAlert('Admin disabled successfully.');
            } else {
                alert('Failed to disable admin');
            }
        } catch (e) {
            console.error(e);
            alert('Error disabling admin');
        }
    }

    // --- Create Function ---
    async function handleCreate(e) {
        e.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const rolesInput = document.getElementById('roles').value;
        const roleIds = rolesInput ? rolesInput.split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n)) : [];

        try {
            const response = await fetch('/api/admins/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password, role_ids: roleIds })
            });

            if (response.ok) {
                window.location.href = '/admins?action=created';
            } else {
                const json = await response.json();
                showAlert(json.error || 'Failed to create admin', 'danger');
            }
        } catch (e) {
            console.error(e);
            showAlert('Error creating admin', 'danger');
        }
    }

    // --- Edit Function ---
    async function handleEdit(e) {
        e.preventDefault();
        const adminId = document.getElementById('admin-id').value;
        const rolesInput = document.getElementById('roles').value;
        const roleIds = rolesInput ? rolesInput.split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n)) : [];

        try {
            const response = await fetch('/api/admins/' + adminId + '/update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ role_ids: roleIds })
            });

            if (response.ok) {
                window.location.href = '/admins?action=updated';
            } else {
                const json = await response.json();
                showAlert(json.error || 'Failed to update admin', 'danger');
            }
        } catch (e) {
            console.error(e);
            showAlert('Error updating admin', 'danger');
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
