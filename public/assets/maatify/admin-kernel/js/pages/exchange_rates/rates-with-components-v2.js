document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('rates-table-body');
    const totalCount = document.getElementById('rates-total-count');

    // Modal elements
    const modal = document.getElementById('rate-modal');
    const modalTitle = document.getElementById('rate-modal-title');
    const modalError = document.getElementById('rate-modal-error');
    const form = document.getElementById('rate-form');
    const inputId = document.getElementById('rate-id');
    const inputProvider = document.getElementById('rate-provider');
    const inputBase = document.getElementById('rate-base');
    const inputTarget = document.getElementById('rate-target');
    const inputRate = document.getElementById('rate-value');

    function loadProvidersForDropdown(selectedId = null) {
        fetch(window.ratesCapabilities.endpoints.providers_dropdown, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({})
        })
        .then(res => res.json())
        .then(data => {
            if(data.success && data.data && data.data.data) {
                inputProvider.innerHTML = data.data.data.map(p => `<option value="${p.id}">${p.name} (${p.code})</option>`).join('');
                if (selectedId) {
                    inputProvider.value = selectedId;
                }
            }
        });
    }

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
                    ${window.ratesCapabilities.can_update ? `<button class="text-indigo-500 hover:text-indigo-600 mr-2 edit-btn" data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}'>Edit</button>` : ''}
                </td>
            </tr>
        `).join('');

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const item = JSON.parse(e.target.getAttribute('data-item'));
                openModal(item);
            });
        });
    }

    function openModal(item = null) {
        modalError.classList.add('hidden');
        if (item) {
            modalTitle.innerText = 'Edit Rate';
            inputId.value = item.id;
            loadProvidersForDropdown(item.provider_id);
            inputProvider.disabled = true; // Immutable pair
            inputBase.value = item.base_currency_code;
            inputBase.disabled = true; // Immutable pair
            inputTarget.value = item.target_currency_code;
            inputTarget.disabled = true; // Immutable pair
            inputRate.value = item.rate;
        } else {
            modalTitle.innerText = 'Create Rate';
            form.reset();
            inputId.value = '';
            inputProvider.disabled = false;
            inputBase.disabled = false;
            inputTarget.disabled = false;
            loadProvidersForDropdown();
        }
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    function saveRate() {
        const isUpdate = inputId.value !== '';
        const endpoint = isUpdate ? window.ratesCapabilities.endpoints.update : window.ratesCapabilities.endpoints.create;

        const payload = {
            rate: inputRate.value
        };

        if (isUpdate) {
            payload.id = parseInt(inputId.value, 10);
        } else {
            payload.provider_id = parseInt(inputProvider.value, 10);
            payload.base_currency_code = inputBase.value.toUpperCase();
            payload.target_currency_code = inputTarget.value.toUpperCase();
        }

        fetch(endpoint, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Error saving rate');
            }
            closeModal();
            loadRates();
        })
        .catch(err => {
            modalError.innerText = err.message;
            modalError.classList.remove('hidden');
        });
    }

    if (document.getElementById('rates-create-btn')) {
        document.getElementById('rates-create-btn').addEventListener('click', () => openModal());
    }
    document.getElementById('rate-modal-close').addEventListener('click', closeModal);
    document.getElementById('rate-modal-cancel').addEventListener('click', closeModal);
    document.getElementById('rate-modal-save').addEventListener('click', saveRate);

    loadRates();
});
