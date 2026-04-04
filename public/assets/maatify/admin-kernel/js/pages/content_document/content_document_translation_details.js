/**
 * ============================================================
 * Content Document Translation Details
 * ============================================================
 * Responsibilities:
 *  1. Initialize Jodit WYSIWYG on #field-content
 *     – Respects language direction (RTL / LTR)
 *     – Applies dark theme when <html> has class "dark"
 *  2. Wire the "Save Changes" button to ApiHandler.call()
 *
 * Window contract (set by the Twig template):
 *  - window.contentDocumentTranslationsCapabilities  { can_upsert: bool, … }
 *  - window.contentDocumentTranslationsApi           { update: '/…/{type_id}/…' }
 *  - window.contentDocumentTranslationsContext       { languageDirection: 'ltr'|'rtl', languages: […] }
 *  - window.typeId
 *  - window.documentId
 *  - window.languageId
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // ================================================================
    // 1. Read window context
    // ================================================================

    const capabilities = window.contentDocumentTranslationsCapabilities || {};
    const api          = window.contentDocumentTranslationsApi          || {};
    const ctx          = window.contentDocumentTranslationsContext      || {};

    const typeId     = window.typeId;
    const documentId = window.documentId;
    const languageId = window.languageId;

    // ================================================================
    // 2. Helper — resolve URL template
    // ================================================================

    function buildEndpoint() {
        return (api.update || '')
            .replace('{type_id}',     typeId)
            .replace('{document_id}', documentId)
            .replace('{language_id}', languageId)
            .replace(/^\/+/, '');
    }

    // ================================================================
    // 3. Initialize WYSIWYG Editor using admin-wysiwyg.js
    // ================================================================

    if (typeof window.initWysiwyg === 'function') {
        window.initWysiwyg({
            languageCode: ctx.languageCode,
            languageDirection: ctx.languageDirection
        });

        // Listen for custom wysiwygChange event to handle dirty state styling
        document.addEventListener('wysiwygChange', (e) => {
            const btn = document.getElementById('btn-save-translation');
            if (btn) btn.classList.add('ring-2', 'ring-blue-400');
        });
    } else {
        console.warn("⚠️ initWysiwyg wrapper is missing from the global scope.");
    }

    // ================================================================
    // 4. Save button wiring
    // ================================================================

    const saveBtn = document.getElementById('btn-save-translation');

    if (!saveBtn || !capabilities.can_upsert) {
        console.log('ℹ️ [TranslationDetails] Save button not wired — no upsert capability or element missing.');
        return;
    }

    saveBtn.addEventListener('click', async function () {

        const payload = {
            title:            document.getElementById('field-title')?.value?.trim()            ?? '',
            meta_title:       document.getElementById('field-meta-title')?.value?.trim()       ?? '',
            meta_description: document.getElementById('field-meta-description')?.value?.trim() ?? '',
        };

        // Extract WYSIWYG data via the global wrapper if available
        if (typeof window.getWysiwygData === 'function') {
            const wysiwygData = window.getWysiwygData();
            if (wysiwygData.content && wysiwygData.content.trim()) {
                payload.content = wysiwygData.content.trim();
            }
        } else {
            const contentInput = document.getElementById('field-content');
            if (contentInput && contentInput.value.trim()) {
                payload.content = contentInput.value.trim();
            }
        }

        console.log('📝 [TranslationDetails] Payload:', payload);

        if (!payload.title) {
            ApiHandler.showAlert('warning', '⚠️ Title is required.');
            document.getElementById('field-title')?.focus();
            return;
        }

        if (!payload.content || payload.content.replace(/<[^>]*>/g, '').trim() === '') {
            ApiHandler.showAlert('warning', '⚠️ Content cannot be empty.');
            return;
        }

        saveBtn.disabled  = true;
        saveBtn.innerHTML = '⏳ Saving…';
        saveBtn.classList.remove('ring-2', 'ring-blue-400');

        const result = await ApiHandler.call(
            buildEndpoint(),
            payload,
            'Upsert Translation',
            'POST'
        );

        saveBtn.disabled  = false;
        saveBtn.innerHTML = '💾 Save Changes';

        if (result.success) {
            if (window.AdminKernel && window.AdminKernel.Wysiwyg) {
                window.AdminKernel.Wysiwyg.resetDirtyState();
            }
            ApiHandler.showAlert('success', '✅ Translation saved successfully!');
        } else {
            ApiHandler.showAlert('danger', result.error || '❌ Failed to save translation.');

            if (result.data?.error?.fields) {
                ApiHandler.showFieldErrors(result.data.error.fields, 'translation-form-section');
            }
        }
    });

    // ================================================================
    // 8 Language Switcher
    // ✨ Language Switcher Dropdown (inline - small, self-contained)
    // ================================================================

    (function initLanguageSwitcher() {
        const container = document.getElementById('translation-filter-language-id');
        if (!container) return;

        const box      = container.querySelector('.js-select-box');
        const dropdown = container.querySelector('.js-dropdown');
        const arrow    = container.querySelector('.js-arrow');
        const items    = container.querySelectorAll('.js-select-list li');

        if (!box || !dropdown || !arrow) return;

        box.addEventListener('click', () => {
            dropdown.classList.toggle('hidden');
            arrow.classList.toggle('rotate-180');
        });

        items.forEach(item => {
            item.addEventListener('click', function () {
                const url = this.getAttribute('data-value');
                if (url) window.location.href = url;
            });
        });

        document.addEventListener('click', e => {
            if (!container.contains(e.target)) {
                dropdown.classList.add('hidden');
                arrow.classList.remove('rotate-180');
            }
        });
    })();
});
