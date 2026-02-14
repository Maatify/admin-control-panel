/**
 * I18n Scope Language Coverage Management
 * ========================================
 * Manages the "Domain Breakdown" table on the Scope Language Coverage page.
 *
 * Features:
 * - List domain coverage for this scope and language
 * - View completion percentage
 * - Link to translation page
 *
 * API Endpoints:
 * - GET /api/i18n/scopes/{scope_id}/coverage/languages/{language_id}
 */

(function() {
    'use strict';

    console.log('🌍 I18n Scope Language Coverage Module Loading...');

    // ========================================================================
    // PREREQUISITES CHECK
    // ========================================================================

    if (typeof ApiHandler === 'undefined') {
        console.error('❌ ApiHandler not found!');
        return;
    }

    if (typeof window.scopeLanguageContext === 'undefined') {
        console.error('❌ Scope Language Context not found (window.scopeLanguageContext)!');
        return;
    }

    console.log('✅ Dependencies loaded: ApiHandler');

    // ========================================================================
    // STATE & CONFIGURATION
    // ========================================================================

    const context = window.scopeLanguageContext;
    const containerId = 'domain-coverage-container';

    // ========================================================================
    // UTILITY FUNCTIONS
    // ========================================================================

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // ========================================================================
    // DATA LOADING
    // ========================================================================

    async function loadCoverage() {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error(`❌ Container #${containerId} not found`);
            return;
        }

        const endpoint = `i18n/scopes/${context.scope_id}/coverage/languages/${context.language_id}`;
        
        // Use GET method for this endpoint
        const result = await ApiHandler.call(endpoint, {}, 'Load Scope Language Coverage', 'GET');

        if (result.success) {
            renderTable(container, result.data);
        } else {
            renderError(container, result.error);
        }
    }

    function renderError(container, error) {
        container.innerHTML = `
            <div class="p-4 text-center border border-red-200 bg-red-50 rounded-lg dark:bg-red-900/20 dark:border-red-800">
                <div class="text-red-600 dark:text-red-400 text-sm font-semibold mb-2">⚠️ Error Loading Coverage</div>
                <div class="text-gray-600 dark:text-gray-300 text-xs">${escapeHtml(error)}</div>
                <button onclick="window.reloadScopeLanguageCoverageTable()" class="mt-3 px-4 py-1.5 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">
                    Retry
                </button>
            </div>
        `;
    }

    function renderTable(container, data) {
        if (!data || data.length === 0) {
            container.innerHTML = `<div class="text-gray-500 text-sm italic p-4 text-center">No assigned domains found.</div>`;
            return;
        }

        const html = `
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Domain</th>
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
        // row structure matches ScopeCoverageByDomainItemDTO
        // domain_id, domain_code, domain_name, total_keys, translated_count, missing_count, completion_percent

        // Color coding for percentage
        let badgeColor = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        if (row.completion_percent >= 100) {
            badgeColor = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        } else if (row.completion_percent > 50) {
            badgeColor = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        }

        const domainLabel = `
            <div>
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">${escapeHtml(row.domain_name)}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">${escapeHtml(row.domain_code)}</div>
            </div>
        `;

        // Action link with preselect
        const actionLink = `/i18n/scopes/${context.scope_id}/domains/${row.domain_id}/translations?language_id=${context.language_id}`;

        return `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">${domainLabel}</td>
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
                    <a href="${actionLink}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 font-bold">
                        Go &rarr;
                    </a>
                </td>
            </tr>
        `;
    }

    // ========================================================================
    // INITIALIZATION
    // ========================================================================

    function init() {
        console.log('🎬 Initializing I18n Scope Language Coverage Module...');
        loadCoverage();

        // Export reload function
        window.reloadScopeLanguageCoverageTable = loadCoverage;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
