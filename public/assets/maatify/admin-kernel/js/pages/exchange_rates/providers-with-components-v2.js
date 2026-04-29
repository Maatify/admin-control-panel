document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('providers-table-body');
    const totalCount = document.getElementById('providers-total-count');

    function loadProviders() {
        fetch(window.providersCapabilities.endpoints.query, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ page: 1, per_page: 100 })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success && data.data && data.data.data) {
                renderTable(data.data.data);
                totalCount.innerText = data.data.pagination.total;
            }
        });
    }

    function renderTable(items) {
        tableBody.innerHTML = items.map(item => `
            <tr>
                <td class="px-2 py-3">${item.display_order}</td>
                <td class="px-2 py-3 font-medium">${item.code}</td>
                <td class="px-2 py-3">${item.name}</td>
                <td class="px-2 py-3 text-center">${item.is_active ? '<span class="text-emerald-500">Active</span>' : '<span class="text-rose-500">Inactive</span>'}</td>
                <td class="px-2 py-3 text-right">
                    ${window.providersCapabilities.can_update ? `<button class="text-indigo-500 hover:text-indigo-600" onclick="alert('Update provider ${item.id} not fully wired in stub')">Edit</button>` : ''}
                </td>
            </tr>
        `).join('');
    }

    loadProviders();
});
