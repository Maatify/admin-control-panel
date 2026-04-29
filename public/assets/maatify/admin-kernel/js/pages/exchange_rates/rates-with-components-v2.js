document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('rates-table-body');
    const totalCount = document.getElementById('rates-total-count');

    function loadRates() {
        fetch(window.ratesCapabilities.endpoints.query, {
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
                <td class="px-2 py-3 font-medium">${item.provider_name}</td>
                <td class="px-2 py-3">${item.base_currency_code}</td>
                <td class="px-2 py-3">${item.target_currency_code}</td>
                <td class="px-2 py-3 font-mono">${item.rate}</td>
                <td class="px-2 py-3 text-center">${item.is_active ? '<span class="text-emerald-500">Active</span>' : '<span class="text-rose-500">Inactive</span>'}</td>
                <td class="px-2 py-3 text-right">
                    ${window.ratesCapabilities.can_update ? `<button class="text-indigo-500 hover:text-indigo-600 mr-2" onclick="alert('Update rate ${item.id} not fully wired in stub')">Edit</button>` : ''}
                </td>
            </tr>
        `).join('');
    }

    loadRates();
});
