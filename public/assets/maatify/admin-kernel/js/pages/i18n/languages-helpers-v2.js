/**
 * 🛠️ Languages Helpers V2
 * Bridge-first helper layer for languages v2 modules.
 */
(function() {
    'use strict';

    console.log('🛠️ LanguagesHelpersV2 loading...');

    if (!window.AdminPageBridge || !window.I18nHelpersV2) {
        console.error('❌ Missing dependencies for LanguagesHelpersV2');
        return;
    }

    const Bridge = window.AdminPageBridge;
    const I18nHelpers = window.I18nHelpersV2;

    function setupButtonHandler(selector, callback, options = {}) {
        const {
            preventDefault = true,
            stopPropagation = false,
            dataAttribute = 'data-language-id',
            requireData = true
        } = options;

        return Bridge.Events.onClick(selector, async function(event, btn) {
            if (preventDefault && event) event.preventDefault();
            if (stopPropagation && event) event.stopPropagation();

            const languageId = btn.getAttribute(dataAttribute);
            if (requireData && !languageId) {
                console.warn(`⚠️ Button ${selector} clicked but no ${dataAttribute} found`);
                return;
            }

            await callback(languageId, btn, event);
        });
    }

    function openModal(modalId) {
        return Bridge.Modal.open('#' + modalId);
    }

    function closeModal(modalId) {
        return Bridge.Modal.close('#' + modalId);
    }

    function closeAllModals() {
        document.querySelectorAll('[id$="-modal"]').forEach(function(modal) {
            Bridge.Modal.close(modal);
        });
    }

    function setupModalCloseHandlers() {
        Bridge.Events.onClick('.close-modal', function(event, btn) {
            const modal = btn.closest('[id$="-modal"]');
            if (modal) Bridge.Modal.close(modal);
        });

        document.addEventListener('click', function(event) {
            if (event.target && event.target.id && event.target.id.endsWith('-modal')) {
                Bridge.Modal.close('#' + event.target.id);
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeAllModals();
        });
    }

    function clearFormInputs(formId) {
        const form = document.getElementById(formId);
        if (form && typeof form.reset === 'function') form.reset();
    }

    function setFormDisabled(formId, disabled) {
        const form = document.getElementById(formId);
        if (!form) return;
        form.querySelectorAll('input, select, textarea, button').forEach(function(el) {
            el.disabled = !!disabled;
        });
    }

    function isValidLanguageCode(code) {
        return /^[a-z]{2,5}$/.test((code || '').trim());
    }

    function isNonEmpty(value) {
        return typeof value === 'string' ? value.trim().length > 0 : !!value;
    }

    function showElement(elementId) {
        const element = document.getElementById(elementId);
        if (element) element.classList.remove('hidden');
    }

    function hideElement(elementId) {
        const element = document.getElementById(elementId);
        if (element) element.classList.add('hidden');
    }

    function toggleElement(elementId) {
        const element = document.getElementById(elementId);
        if (element) element.classList.toggle('hidden');
    }

    window.LanguagesHelpersV2 = {
        setupButtonHandler,
        openModal,
        closeModal,
        closeAllModals,
        setupModalCloseHandlers,
        clearFormInputs,
        setFormDisabled,
        isValidLanguageCode,
        isNonEmpty,
        showElement,
        hideElement,
        toggleElement,
        createResetPageReload: I18nHelpers.createResetPageReload,
        bindTableActionState: I18nHelpers.bindTableActionState,
        withTableContainerTarget: I18nHelpers.withTableContainerTarget,
        wireModalDismiss: I18nHelpers.wireModalDismiss
    };

    console.log('✅ LanguagesHelpersV2 loaded');
})();
