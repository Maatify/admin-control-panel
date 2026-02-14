document.addEventListener('DOMContentLoaded', () => {
    // Check if context exists
    if (!window.scopeDetailsId) return;

    const scopeId = window.scopeDetailsId;
    const container = document.getElementById('scope-coverage-container');
    if (!container) return;

    const endpoint = `/api/i18n/scopes/${scopeId}/coverage`;

    // Reusing API Handler
    ApiHandler.get(endpoint)
        .then(response => {
            if (response.success) {
                renderTable(response.data);
            } else {
                container.innerHTML = `<div class="text-red-500 text-sm p-4">Failed to load coverage: ${response.error}</div>`;
            }
        })
        .catch(err => {
            console.error('Coverage load error', err);
            container.innerHTML = `<div class="text-red-500 text-sm p-4">Error loading coverage data.</div>`;
        });

    function renderTable(data) {
        if (!data || data.length === 0) {
            container.innerHTML = `<div class="text-gray-500 text-sm italic p-4">No languages found or no domains assigned.</div>`;
            return;
        }

        const html = `
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Language</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Completion</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Keys</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Missing</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        ${data.map(row => renderRow(row)).join('')}
                    </tbody>
                </table>
            </div>
        `;

        container.innerHTML = html;
    }

    function renderRow(row) {
        // row structure matches ScopeCoverageByLanguageItemDTO
        // language_id, language_code, language_name, language_icon, total_keys, translated_count, missing_count, completion_percent

        // Color coding for percentage
        let badgeColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        if (row.completion_percent >= 100) {
            badgeColor = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        } else if (row.completion_percent > 50) {
            badgeColor = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        }

        const icon = row.language_icon || '';
        const languageLabel = `
            <div class="flex items-center">
                <span class="mr-2 text-lg">${icon}</span>
                <div>
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">${row.language_name}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">${row.language_code}</div>
                </div>
            </div>
        `;

        const viewLink = `/i18n/scopes/${scopeId}/coverage/languages/${row.language_id}`;

        return `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">${languageLabel}</td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeColor}">
                        ${row.completion_percent}%
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500 dark:text-gray-400">
                    ${row.total_keys}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500 dark:text-gray-400">
                    ${row.missing_count > 0 ? `<span class="text-red-600 dark:text-red-400 font-medium">${row.missing_count}</span>` : '<span class="text-green-600 dark:text-green-400">0</span>'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <a href="${viewLink}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                        View Domains &rarr;
                    </a>
                </td>
            </tr>
        `;
    }
});
