document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('providers-table-body');
    const totalCount = document.getElementById('providers-total-count');

    // Modal elements
    const modal = document.getElementById('provider-modal');
    const modalTitle = document.getElementById('provider-modal-title');
    const modalError = document.getElementById('provider-modal-error');
    const form = document.getElementById('provider-form');
    const inputId = document.getElementById('provider-id');
    const inputCode = document.getElementById('provider-code');
    const inputName = document.getElementById('provider-name');
    const inputDesc = document.getElementById('provider-description');

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
                    ${window.providersCapabilities.can_update ? `<button class="text-indigo-500 hover:text-indigo-600 edit-btn" data-item='${JSON.stringify(item).replace(/'/g, "&apos;")}'>Edit</button>` : ''}
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
            modalTitle.innerText = 'Edit Provider';
            inputId.value = item.id;
            inputCode.value = item.code;
            inputCode.disabled = true; // Immutable
            inputName.value = item.name;
            inputDesc.value = item.description || '';
        } else {
            modalTitle.innerText = 'Create Provider';
            form.reset();
            inputId.value = '';
            inputCode.disabled = false;
        }
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    function saveProvider() {
        const isUpdate = inputId.value !== '';
        const endpoint = isUpdate ? window.providersCapabilities.endpoints.update : window.providersCapabilities.endpoints.create;

        const payload = {
            name: inputName.value,
            description: inputDesc.value
        };

        if (isUpdate) {
            payload.id = parseInt(inputId.value, 10);
        } else {
            payload.code = inputCode.value;
        }

        fetch(endpoint, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Error saving provider');
            }
            closeModal();
            loadProviders();
        })
        .catch(err => {
            modalError.innerText = err.message;
            modalError.classList.remove('hidden');
        });
    }

    if (document.getElementById('providers-create-btn')) {
        document.getElementById('providers-create-btn').addEventListener('click', () => openModal());
    }
    document.getElementById('provider-modal-close').addEventListener('click', closeModal);
    document.getElementById('provider-modal-cancel').addEventListener('click', closeModal);
    document.getElementById('provider-modal-save').addEventListener('click', saveProvider);

    loadProviders();
});
