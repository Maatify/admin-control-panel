/**
 * 🎨 Admin Kernel - Reusable UI Components Library
 * ================================================
 * Shared components and utilities for ALL admin modules
 *
 * Use this across: Languages, Translations, Admins, Roles, Permissions, etc.
 *
 * File: admin-ui-components.js
 * Location: /assets/maatify/admin-kernel/js/admin-ui-components.js
 */

(function() {
    'use strict';

    console.log('🎨 Admin UI Components Library Loading...');

    // ========================================================================
    // 1. STATUS BADGE RENDERER (Reusable!)
    // ========================================================================

    /**
     * Render status badge with icon
     * Use in: Languages, Admins, Roles, any module with active/inactive status
     *
     * @param {boolean|number|string} value - Status value
     * @param {object} options - Configuration
     * @returns {string} HTML
     */
    function renderStatusBadge(value, options = {}) {
        const {
            clickable = false,
            entityId = null,
            activeText = 'Active',
            inactiveText = 'Inactive',
            buttonClass = 'toggle-status-btn',
            dataAttribute = 'data-entity-id'  // ✅ Support custom attribute
        } = options;

        const isActive = value === true || value === 1 || value === "1";

        // Active badge
        if (isActive) {
            const badge = `
                <span class="inline-flex items-center gap-1 bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-400 border border-green-300 dark:border-green-800 px-3 py-1 rounded-lg text-xs font-semibold">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    ${activeText}
                </span>
            `;

            if (clickable && entityId) {
                return `<button class="${buttonClass} hover:opacity-75 transition-opacity cursor-pointer"
                            ${dataAttribute}="${entityId}"
                            data-current-status="1"
                            title="Click to deactivate">
                    ${badge}
                </button>`;
            }
            return badge;
        }

        // Inactive badge
        const badge = `
            <span class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400 border border-gray-300 dark:border-gray-700 px-3 py-1 rounded-lg text-xs font-semibold">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                ${inactiveText}
            </span>
        `;

        if (clickable && entityId) {
            return `<button class="${buttonClass} hover:opacity-75 transition-opacity cursor-pointer"
                        ${dataAttribute}="${entityId}"
                        data-current-status="0"
                        title="Click to activate">
                ${badge}
            </button>`;
        }
        return badge;
    }

    // ========================================================================
    // 2. CODE/TAG BADGE RENDERER (Reusable!)
    // ========================================================================

    /**
     * Render code/tag badge
     * Use in: Languages (code), Roles (slug), Tags, Categories
     *
     * @param {string} value - Code value
     * @param {object} options - Configuration
     * @returns {string} HTML
     */
    function renderCodeBadge(value, options = {}) {
        const {
            color = 'blue',
            uppercase = true,
            dataField = null
        } = options;

        if (!value) return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';

        const displayValue = uppercase ? value.toUpperCase() : value;
        const badge = `<span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-medium bg-${color}-100 dark:bg-${color}-900/40 text-${color}-800 dark:text-${color}-400 uppercase">${displayValue}</span>`;

        if (dataField) {
            return `<div data-field="${dataField}">${badge}</div>`;
        }
        return badge;
    }

    // ========================================================================
    // 3. DIRECTION BADGE RENDERER (Reusable!)
    // ========================================================================

    /**
     * Render text direction badge
     * Use in: Languages, Content modules
     *
     * @param {string} value - Direction ('ltr' or 'rtl')
     * @returns {string} HTML
     */
    function renderDirectionBadge(value) {
        const isRTL = value?.toLowerCase() === 'rtl';
        const arrow = isRTL ? '←' : '→';
        const bgColor = isRTL ? 'bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-400';

        return `<span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium ${bgColor}">
            <span class="mr-1">${arrow}</span>
            ${value?.toUpperCase() || 'N/A'}
        </span>`;
    }

    // ========================================================================
    // 4. SORT ORDER BADGE RENDERER (Reusable!)
    // ========================================================================

    /**
     * Render sort order badge
     * Use in: Languages, Menu items, Categories, any sortable list
     *
     * @param {number} value - Sort order number
     * @param {object} options - Configuration
     * @returns {string} HTML
     */
    function renderSortBadge(value, options = {}) {
        const {
            size = 'md', // sm, md, lg
            color = 'indigo'
        } = options;

        if (value === null || value === undefined) {
            return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';
        }

        const sizeClasses = {
            sm: 'w-7 h-7 text-xs',
            md: 'w-9 h-9 text-sm',
            lg: 'w-11 h-11 text-base'
        };

        return `<span class="inline-flex items-center justify-center ${sizeClasses[size]} rounded-lg bg-gradient-to-br from-${color}-50 to-${color}-100 dark:from-${color}-900/30 dark:to-${color}-800/30 border border-${color}-200 dark:border-${color}-700 text-${color}-700 dark:text-${color}-400 font-bold shadow-sm">${value}</span>`;
    }

    // ========================================================================
    // 5. ICON RENDERER (Reusable!)
    // ========================================================================

    /**
     * Render icon with fallback placeholder
     * Use in: Languages, Categories, Menu items
     *
     * @param {string} icon - Icon/emoji
     * @param {object} options - Configuration
     * @returns {string} HTML
     */
    function renderIcon(icon, options = {}) {
        const {
            size = 'md', // sm, md, lg
            fallbackSvg = null
        } = options;

        const sizeClasses = {
            sm: 'w-5 h-5',
            md: 'w-7 h-7',
            lg: 'w-9 h-9'
        };

        if (icon) {
            return `<span class="flex items-center justify-center ${sizeClasses[size]} rounded-md bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-base">${icon}</span>`;
        }

        // Fallback placeholder
        const defaultSvg = fallbackSvg || `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
            </svg>
        `;

        return `<span class="flex items-center justify-center ${sizeClasses[size]} rounded-md bg-gray-100 dark:bg-gray-800/50 border border-gray-300 dark:border-gray-700 text-gray-400 dark:text-gray-500 text-xs">
            ${defaultSvg}
        </span>`;
    }

    // ========================================================================
    // 6. ACTION BUTTON BUILDER (Reusable!)
    // ========================================================================

    /**
     * Build action button with icon
     * Use in: All modules for action buttons
     *
     * @param {object} config - Button configuration
     * @returns {string} HTML
     */
    function buildActionButton(config) {
        const {
            cssClass = '',
            icon = '',
            text = '',
            color = 'blue', // blue, green, amber, red, purple, indigo
            entityId = null,
            title = '',
            dataAttributes = {}
        } = config;

        const colorClasses = {
            blue: 'text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-200 dark:hover:border-blue-800',
            green: 'text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 hover:border-green-200 dark:hover:border-green-800',
            amber: 'text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:border-amber-200 dark:hover:border-amber-800',
            red: 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:border-red-200 dark:hover:border-red-800',
            purple: 'text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 hover:border-purple-200 dark:hover:border-purple-800',
            indigo: 'text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:border-indigo-200 dark:hover:border-indigo-800',
            gray: 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-200 dark:hover:border-gray-600'
        };

        let dataAttrs = entityId ? `data-entity-id="${entityId}"` : '';
        for (const [key, value] of Object.entries(dataAttributes)) {
            dataAttrs += ` data-${key}="${value}"`;
        }

        return `
            <button class="${cssClass} inline-flex items-center gap-1 px-2 py-1 ${colorClasses[color]} rounded transition-colors text-xs font-medium border border-transparent" 
                    ${dataAttrs}
                    title="${title}">
                ${icon}
                ${text}
            </button>
        `;
    }

    // ========================================================================
    // 7. COMMON SVG ICONS (Reusable!)
    // ========================================================================

    const SVGIcons = {
        settings: `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>`,

        edit: `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>`,

        delete: `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>`,

        view: `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>`,

        sort: `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
        </svg>`,

        link: `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
        </svg>`,

        tag: `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
        </svg>`,

        plus: `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>`,

        check: `<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
        </svg>`,

        x: `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>`
    };

    // ========================================================================
    // 8. MODAL TEMPLATE BUILDER (Reusable!)
    // ========================================================================

    /**
     * Build modal HTML template
     * Use in: All modules
     *
     * @param {object} config - Modal configuration
     * @returns {string} HTML
     */
    function buildModalTemplate(config) {
        const {
            id = '',
            title = '',
            content = '',
            footer = '',
            size = 'md', // sm, md, lg, xl
            icon = ''
        } = config;

        const sizeClasses = {
            sm: 'max-w-sm',
            md: 'max-w-md',
            lg: 'max-w-lg',
            xl: 'max-w-2xl',
            '2xl': 'max-w-4xl'
        };

        return `
            <div id="${id}" class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 z-50 flex items-center justify-center hidden">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl ${sizeClasses[size]} w-full mx-4 border border-transparent dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">${icon} ${title}</h3>
                        <button class="close-modal text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        ${content}
                    </div>
                    
                    ${footer ? `<div class="modal-footer px-6 py-4 border-t border-gray-200 dark:border-gray-700">${footer}</div>` : ''}
                </div>
            </div>
        `;
    }

    // ========================================================================
    // 9. STANDARD MODAL FOOTER (Reusable!)
    // ========================================================================

    /**
     * Build standard modal footer with Cancel/Submit buttons
     *
     * @param {object} options - Footer configuration
     * @returns {string} HTML
     */
    function buildModalFooter(options = {}) {
        const {
            cancelText = 'Cancel',
            submitText = 'Submit',
            submitColor = 'blue'
        } = options;

        const submitColorClasses = {
            blue: 'bg-blue-600 hover:bg-blue-700',
            green: 'bg-green-600 hover:bg-green-700',
            red: 'bg-red-600 hover:bg-red-700'
        };

        return `
            <div class="flex justify-end gap-3">
                <button type="button" class="close-modal px-4 py-2 text-gray-700 dark:text-gray-200 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    ${cancelText}
                </button>
                <button type="submit" class="px-4 py-2 text-white ${submitColorClasses[submitColor]} rounded-lg transition-colors shadow-sm">
                    ${submitText}
                </button>
            </div>
        `;
    }

    // ========================================================================
    // 10. DATE FORMATTER (Reusable!)
    // ========================================================================

    /**
     * Format date string
     * Use in: All modules with timestamps
     *
     * @param {string} dateString - ISO date string
     * @param {object} options - Format options
     * @returns {string} Formatted date
     */
    function formatDate(dateString, options = {}) {
        const {
            format = 'full', // full, date, time, relative
            locale = 'en-US'
        } = options;

        if (!dateString) return '<span class="text-gray-400 dark:text-gray-500 italic">N/A</span>';

        const date = new Date(dateString);

        switch (format) {
            case 'date':
                return date.toLocaleDateString(locale);
            case 'time':
                return date.toLocaleTimeString(locale);
            case 'relative':
                return getRelativeTime(date);
            case 'full':
            default:
                return date.toLocaleString(locale);
        }
    }

    /**
     * Get relative time (e.g., "2 hours ago")
     */
    function getRelativeTime(date) {
        const now = new Date();
        const diff = now - date;
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);

        if (days > 7) return date.toLocaleDateString();
        if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
        if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        return 'Just now';
    }

    // ========================================================================
    // 11. EVENT HANDLER HELPER (Reusable!)
    // ========================================================================

    /**
     * Setup button click handler (from languages-helpers-optimized)
     * Use in: All modules
     */
    function setupButtonHandler(selector, callback, options = {}) {
        const {
            preventDefault = true,
            stopPropagation = false,
            dataAttribute = 'data-entity-id',
            requireData = true
        } = options;

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest(selector);
            if (!btn) return;

            if (preventDefault) e.preventDefault();
            if (stopPropagation) e.stopPropagation();

            const entityId = btn.getAttribute(dataAttribute);

            if (requireData && !entityId) {
                console.warn(`⚠️ Button ${selector} clicked but no ${dataAttribute} found`);
                return;
            }

            try {
                await callback(entityId, btn, e);
            } catch (error) {
                console.error(`❌ Error in ${selector} handler:`, error);
            }
        });
    }

    // ========================================================================
    // 12. CONFIRMATION DIALOG (Reusable!)
    // ========================================================================

    /**
     * Show confirmation dialog
     * Use in: Delete actions, destructive operations
     *
     * @param {object} config - Configuration
     * @returns {Promise<boolean>} User's choice
     */
    async function showConfirmation(config) {
        const {
            title = 'Confirm Action',
            message = 'Are you sure?',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            type = 'warning' // warning, danger, info
        } = config;

        // Simple browser confirm for now
        // Can be enhanced with custom modal later
        return confirm(`${title}\n\n${message}`);
    }

    // ========================================================================
    // Export to Window
    // ========================================================================

    window.AdminUIComponents = {
        // Renderers
        renderStatusBadge,
        renderCodeBadge,
        renderDirectionBadge,
        renderSortBadge,
        renderIcon,

        // Builders
        buildActionButton,
        buildModalTemplate,
        buildModalFooter,

        // Icons
        SVGIcons,

        // Utilities
        formatDate,
        setupButtonHandler,
        showConfirmation
    };

    console.log('✅ Admin UI Components Library loaded');
    console.log('   ↳ 12 reusable components available project-wide!');

})();