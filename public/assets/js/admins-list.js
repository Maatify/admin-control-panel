document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.querySelector('#admins-table-body');
    const searchInput = document.querySelector('#search-input');
    const paginationContainer = document.querySelector('#pagination-container');
    const emptyState = document.querySelector('#empty-state');
    const tableContainer = document.querySelector('#table-container');
    const errorState = document.querySelector('#error-state');

    let currentPage = 1;
    let perPage = 10;
    let currentSearch = '';

    const fetchAdmins = async () => {
        // Show loading state
        tableBody.style.opacity = '0.5';

        // Build query string
        const params = new URLSearchParams({
            page: currentPage,
            per_page: perPage,
        });
        if (currentSearch) {
            params.append('search', currentSearch);
        }

        try {
            // Updated to match route /api/admins
            const response = await fetch(`/api/admins?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const result = await response.json();
            renderTable(result.data);
            renderPagination(result.meta);

            // Toggle Empty/Table/Error
            if (result.data.length === 0) {
                tableContainer.classList.add('hidden');
                emptyState.classList.remove('hidden');
            } else {
                tableContainer.classList.remove('hidden');
                emptyState.classList.add('hidden');
            }
            errorState.classList.add('hidden');

        } catch (error) {
            console.error('Error fetching admins:', error);
            tableContainer.classList.add('hidden');
            emptyState.classList.add('hidden');
            errorState.classList.remove('hidden');
        } finally {
            tableBody.style.opacity = '1';
        }
    };

    const renderTable = (admins) => {
        tableBody.innerHTML = '';
        admins.forEach(admin => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${admin.id}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${escapeHtml(admin.email)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${admin.created_at}</td>
            `;
            tableBody.appendChild(row);
        });
    };

    const escapeHtml = (str) => {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    const renderPagination = (meta) => {
        paginationContainer.innerHTML = '';

        if (meta.total_pages <= 1) return;

        // Previous
        const prevBtn = document.createElement('button');
        prevBtn.innerText = 'Previous';
        prevBtn.className = `relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md ${meta.page === 1 ? 'text-gray-300 bg-gray-50 cursor-not-allowed' : 'text-gray-700 bg-white hover:bg-gray-50'}`;
        prevBtn.disabled = meta.page === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                fetchAdmins();
            }
        });
        paginationContainer.appendChild(prevBtn);

        // Page Info
        const info = document.createElement('span');
        info.className = 'mx-4 text-sm text-gray-700 self-center';
        info.innerText = `Page ${meta.page} of ${meta.total_pages}`;
        paginationContainer.appendChild(info);

        // Next
        const nextBtn = document.createElement('button');
        nextBtn.innerText = 'Next';
        nextBtn.className = `relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md ${meta.page === meta.total_pages ? 'text-gray-300 bg-gray-50 cursor-not-allowed' : 'text-gray-700 bg-white hover:bg-gray-50'}`;
        nextBtn.disabled = meta.page === meta.total_pages;
        nextBtn.addEventListener('click', () => {
            if (currentPage < meta.total_pages) {
                currentPage++;
                fetchAdmins();
            }
        });
        paginationContainer.appendChild(nextBtn);
    };

    // Event Listeners
    let debounceTimer;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            currentSearch = e.target.value;
            currentPage = 1; // Reset to page 1 on search
            fetchAdmins();
        }, 300);
    });

    // Initial Load
    fetchAdmins();
});
