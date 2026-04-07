/**
 * I18n Scope Coverage Management V2
 * =================================
 * Read-only coverage table by language for a scope.
 */
(function() {
    'use strict';

    if (typeof AdminUIComponents === 'undefined' || !window.AdminPageBridge) {
        console.error('❌ Missing dependencies for i18n-scope-coverage-v2');
        return;
    }

    if (typeof window.scopeDetailsId === 'undefined') {
        console.error('❌ Scope ID not found (window.scopeDetailsId)');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const scopeId = window.scopeDetailsId;
    const containerId = 'scope-coverage-container';

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderError(container, error) {
        container.innerHTML = `
            <div class="p-4 text-center border border-red-200 bg-red-50 rounded-lg dark:bg-red-900/20 dark:border-red-800">
                <div class="text-red-600 dark:text-red-400 text-sm font-semibold mb-2">⚠️ Error Loading Coverage</div>
                <div class="text-gray-600 dark:text-gray-300 text-xs">${escapeHtml(error)}</div>
                <button onclick="window.reloadScopeCoverageTableV2()" class="mt-3 px-4 py-1.5 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">Retry</button>
            </div>
        `;
    }

    function renderRow(row) {
        let badgeColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        if (row.completion_percent >= 100) badgeColor = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        else if (row.completion_percent > 50) badgeColor = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';

        const icon = AdminUIComponents.renderIcon(row.language_icon, { size: 'sm' });
        const viewLink = `/i18n/scopes/${scopeId}/coverage/languages/${row.language_id}`;

        return `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <span class="mr-2">${icon}</span>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">${escapeHtml(row.language_name)}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">${AdminUIComponents.renderCodeBadge(escapeHtml(row.language_code), { color: 'blue', uppercase: true })}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeColor}">${row.completion_percent}%</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500 dark:text-gray-400">${row.total_keys}</td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500 dark:text-gray-400">
                    ${row.missing_count > 0 ? `<span class="text-red-600 dark:text-red-400 font-medium">${row.missing_count}</span>` : '<span class="text-green-600 dark:text-green-400">0</span>'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <a href="${viewLink}" class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                        ${AdminUIComponents.SVGIcons.view}
                        View Domains
                    </a>
                </td>
            </tr>
        `;
    }

    function renderTable(container, data) {
        if (!data || data.length === 0) {
            container.innerHTML = '<div class="text-gray-500 text-sm italic p-4 text-center">No languages found or no domains assigned.</div>';
            return;
        }

        container.innerHTML = `
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
                        ${data.map(renderRow).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    async function loadCoverage() {
        const container = document.getElementById(containerId);
        if (!container) return;

        const result = await Bridge.API.execute({
            endpoint: `i18n/scopes/${scopeId}/coverage`,
            payload: {},
            operation: 'Load Scope Coverage V2',
            method: 'GET',
            showErrorMessage: false
        });

        if (!result.success) {
            Bridge.UI.error(result.error || 'Failed to load coverage');
            renderError(container, result.error || 'Failed to load coverage');
            return;
        }

        renderTable(container, result.data);
    }

    function init() {
        window.reloadScopeCoverageTableV2 = loadCoverage;
        loadCoverage();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
