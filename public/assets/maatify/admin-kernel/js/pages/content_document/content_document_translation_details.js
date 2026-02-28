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

    // Normalize: trim + lowercase, fallback to 'ltr'
    const direction = (ctx.languageDirection || 'ltr').trim().toLowerCase();
    const langCode  = (ctx.languageCode || 'en').trim().toLowerCase();
    const isDark    = document.documentElement.classList.contains('dark');

    console.log(`↔️  [TranslationDetails] direction="${direction}" | dark=${isDark}`);

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
    // 3. Force direction on every layer of the Jodit DOM
    // ================================================================

    /**
     * Jodit's `direction` option targets the toolbar wrapper.
     * The actual contenteditable body needs to be patched manually
     * after init — this is the reliable cross-version approach.
     *
     * Layers patched:
     *   1. .jodit-container  (outer wrapper)
     *   2. editor.editor     (contenteditable div)
     *   3. editorDocument.body (iframe body, when Jodit uses iframe mode)
     *
     * @param {Jodit}  editor
     * @param {string} dir  'rtl' | 'ltr'
     */

    function applyEditorDirection(editor, dir) {
        const align = dir === 'rtl' ? 'right' : 'left';

        try {
            const doc = editor.editorDocument;

            // ✅ مهم جدًا: نتأكد إن ده مش document الرئيسي
            if (doc && doc !== document && doc.body) {
                doc.body.style.direction = dir;
                doc.body.style.textAlign = align;
            }

            // لو inline mode
            if (editor.editor && doc === document) {
                editor.editor.style.direction = dir;
                editor.editor.style.textAlign = align;
            }

        } catch (_) {}

        console.log(`↔️ Editor direction applied safely → ${dir}`);
    }

    // ================================================================
    // 4. Jodit config factory
    // ================================================================

    function buildJoditConfig(dark) {
        return {
            // ⚠️  Do NOT pass `direction` here — Jodit applies it to
            //     document.body which flips the whole admin page.
            //     Direction is applied manually via applyEditorDirection()
            //     inside the afterInit event (editor container + body only).
            theme:      dark ? 'dark' : '',
            height:     520,
            minHeight:  320,
            language:   langCode ?? 'auto',

            buttons: [
                'undo', 'redo', '|',
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'ul', 'ol', 'outdent', 'indent', '|',
                'font', 'fontsize', 'brush', 'paragraph', '|',
                'align', '|',
                'link', 'image', 'table', '|',
                'hr', 'eraser', 'copyformat', '|',
                'source', 'fullsize', '|',
                'left', 'right',
            ],

            enableDragAndDropFileToEditor: false,
            uploader:   { insertImageAsBase64URI: true },
            cleanHTML:  { fillEmptyParagraph: false },
            enter:      'p',

            showCharsCounter:     true,
            showWordsCounter:     true,
            showXPathInStatusbar: false,
            spellcheck:           false,
            disablePlugins:       'about',

            // Patch direction on every layer right after the editor is ready
            events: {
                afterInit: function (editor) {
                    applyEditorDirection(editor, direction);
                },
            },
        };
    }

    // ================================================================
    // 5. Jodit init + re-init helper
    // ================================================================

    let jodit   = null;
    let isDirty = false;

    /**
     * (Re)initializes Jodit.
     *  - On first call  : reads content from the raw textarea.
     *  - On theme toggle: snapshots current editor HTML, destroys the
     *                     old instance, then creates a fresh one with
     *                     the correct skin — no page reload needed.
     *
     * @param {boolean} dark
     */
    function initJodit(dark) {
        const savedContent = jodit ? jodit.value : null;

        if (jodit) {
            jodit.destruct();
            jodit = null;
        }

        jodit = Jodit.make('#field-content', buildJoditConfig(dark));

        if (savedContent !== null) {
            jodit.value = savedContent;
        }

        jodit.events.on('change', function () {
            isDirty = true;
            const btn = document.getElementById('btn-save-translation');
            if (btn) btn.classList.add('ring-2', 'ring-blue-400');
        });
    }

    // First render
    initJodit(isDark);

    // ================================================================
    // 6. Watch <html class="dark"> toggling — no page refresh needed
    // ================================================================

    const themeObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.attributeName !== 'class') return;

            const nowDark = document.documentElement.classList.contains('dark');

            if (nowDark !== (jodit.options.theme === 'dark')) {
                console.log(`🎨 [TranslationDetails] Theme → ${nowDark ? 'dark' : 'light'}, reinit Jodit`);
                initJodit(nowDark);
            }
        });
    });

    themeObserver.observe(document.documentElement, { attributes: true });

    // ================================================================
    // 7. Save button wiring
    // ================================================================

    const saveBtn = document.getElementById('btn-save-translation');

    if (!saveBtn || !capabilities.can_upsert) {
        console.log('ℹ️ [TranslationDetails] Save button not wired — no upsert capability or element missing.');
        return;
    }

    saveBtn.addEventListener('click', async function () {

        const content = jodit?.value
            ?? document.getElementById('field-content')?.value
            ?? '';

        const payload = {
            title:            document.getElementById('field-title')?.value?.trim()            ?? '',
            meta_title:       document.getElementById('field-meta-title')?.value?.trim()       ?? '',
            meta_description: document.getElementById('field-meta-description')?.value?.trim() ?? '',
            content:          content,
        };

        console.log('📝 [TranslationDetails] Payload:', payload);

        if (!payload.title) {
            ApiHandler.showAlert('warning', '⚠️ Title is required.');
            document.getElementById('field-title')?.focus();
            return;
        }

        if (!payload.content || payload.content.replace(/<[^>]*>/g, '').trim() === '') {
            ApiHandler.showAlert('warning', '⚠️ Content cannot be empty.');
            jodit.focus();
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
            isDirty = false;
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
