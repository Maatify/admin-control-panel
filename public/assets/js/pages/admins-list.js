document.addEventListener('DOMContentLoaded', function() {
    // State
    let currentPage = 1;
    let perPage = 10;
    let currentAdminId = '';
    let currentEmail = '';

    // Elements
    const tableBody = document.querySelector('#admins-table tbody');
    const paginationInfo = document.getElementById('pagination-info');
    const paginationControls = document.getElementById('pagination-controls');
    const searchForm = document.getElementById('admins-search-form');
    const adminIdInput = document.getElementById('filter-admin-id');
    const emailInput = document.getElementById('filter-email');
    const resetButton = document.getElementById('btn-reset');
    const perPageSelect = document.getElementById('per-page-select');

    // Create Admin Elements
    const createBtn = document.getElementById('btn-create-admin');
    // @ts-ignore
    const createModal = new bootstrap.Modal(document.getElementById('createAdminModal'));
    const createForm = document.getElementById('create-admin-form');
    const createError = document.getElementById('create-admin-error');
    const saveBtn = document.getElementById('btn-save-admin');

    // Init
    loadAdmins();

    // Event Listeners
    if (createBtn) {
        createBtn.addEventListener('click', function() {
            createForm.reset();
            createError.classList.add('d-none');
            createError.textContent = '';
            createModal.show();
        });
    }

    if (createForm) {
        createForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            createError.classList.add('d-none');

            const email = document.getElementById('create-email').value;
            const password = document.getElementById('create-password').value;

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

            try {
                const response = await fetch('/api/admins/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.error || 'Failed to create admin');
                }

                createModal.hide();
                showAlert('Admin created successfully! ID: ' + result.id);
                loadAdmins();

            } catch (error) {
                createError.textContent = error.message;
                createError.classList.remove('d-none');
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Create Admin';
            }
        });
    }

    perPageSelect.addEventListener('change', function() {
        perPage = parseInt(this.value, 10);
        currentPage = 1; // Reset to first page
        loadAdmins();
    });

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        currentAdminId = adminIdInput.value;
        currentEmail = emailInput.value;
        currentPage = 1; // Reset to first page
        loadAdmins();
    });

    resetButton.addEventListener('click', function() {
        adminIdInput.value = '';
        emailInput.value = '';
        currentAdminId = '';
        currentEmail = '';
        currentPage = 1;
        loadAdmins();
    });

    // Main Load Function
    async function loadAdmins() {
        setLoading();

        // Build query string
        const params = new URLSearchParams({
            page: currentPage,
            per_page: perPage,
        });
        if (currentAdminId) {
            params.append('id', currentAdminId);
        }
        if (currentEmail) {
            params.append('email', currentEmail);
        }

        try {
            const response = await fetch(`/api/admins?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load admins');
            }

            const result = await response.json();
            renderTable(result.data);
            renderPagination(result.meta);

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

    function renderPagination(meta) {
        const { page, per_page, total, total_pages } = meta;

        const start = total === 0 ? 0 : (page - 1) * per_page + 1;
        const end = Math.min(page * per_page, total);

        paginationInfo.textContent = 'Showing ' + start + ' to ' + end + ' of ' + total + ' entries';

        let html = '';
        const totalPages = total_pages;

        // Prev
        html += '<li class="page-item ' + (page === 1 ? 'disabled' : '') + '">';
        html += '<button class="page-link" onclick="changePage(' + (page - 1) + ')">Previous</button></li>';

        // Simple pagination logic (matching Sessions JS)
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
