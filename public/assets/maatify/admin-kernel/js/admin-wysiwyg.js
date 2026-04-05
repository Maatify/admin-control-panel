/**
 * AdminKernel WYSIWYG Manager
 *
 * A scalable, multi-editor registry for managing Jodit WYSIWYG instances.
 * Handles dynamic initialization, RTL/LTR direction binding, global dark mode toggling,
 * and unified data extraction keyed by `data-field`.
 */
(function () {
    'use strict';

    window.AdminKernel = window.AdminKernel || {};

    const STATE = {
        registry: new Map(), // Map<data-field, { editor: Jodit, element: HTMLElement }>
        dirtyFields: new Set(),
        observerInitialized: false,
        globalContext: {}
    };

    /**
     * Safely applies direction directly into Jodit's iframe and content container
     */
    function applyEditorDirection(editor, dir) {
        const align = dir === 'rtl' ? 'right' : 'left';
        try {
            const doc = editor.editorDocument;
            if (doc && doc !== document && doc.body) {
                doc.body.style.direction = dir;
                doc.body.style.textAlign = align;
            }
            if (editor.editor && doc === document) {
                editor.editor.style.direction = dir;
                editor.editor.style.textAlign = align;
            }
        } catch (_) {}
    }

    /**
     * Generates a Jodit configuration object based on the context and theme
     */
    function buildJoditConfig(dark) {
        const direction = (STATE.globalContext.languageDirection || 'ltr').trim().toLowerCase();
        const langCode = (STATE.globalContext.languageCode || 'en').trim().toLowerCase();

        return {
            theme: dark ? 'dark' : '',
            height: 520,
            minHeight: 320,
            language: langCode ?? 'auto',
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
            uploader: { insertImageAsBase64URI: true },
            cleanHTML: { fillEmptyParagraph: false },
            enter: 'p',
            showCharsCounter: true,
            showWordsCounter: true,
            showXPathInStatusbar: false,
            spellcheck: false,
            disablePlugins: 'about',
            events: {
                afterInit: function (editor) {
                    applyEditorDirection(editor, direction);
                }
            }
        };
    }

    /**
     * Instantiates a single Jodit editor on a given element
     */
    function createInstance(element, fieldKey, isDark, savedContent = null) {
        const editor = Jodit.make(element, buildJoditConfig(isDark));

        if (savedContent !== null) {
            editor.value = savedContent;
        }

        editor.events.on('change', () => {
            STATE.dirtyFields.add(fieldKey);
            document.dispatchEvent(new CustomEvent('wysiwygChange', {
                detail: { field: fieldKey, value: editor.value }
            }));
        });

        STATE.registry.set(fieldKey, { editor, element });
    }

    /**
     * Initializes the singleton MutationObserver for theme toggling
     */
    function initThemeObserver() {
        if (STATE.observerInitialized) return;

        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (mutation.attributeName === 'class') {
                    const nowDark = document.documentElement.classList.contains('dark');
                    Wysiwyg.reinitTheme(nowDark);
                    break;
                }
            }
        });

        observer.observe(document.documentElement, { attributes: true });
        STATE.observerInitialized = true;
    }

    const Wysiwyg = {
        initAll: function (context = {}) {
            if (typeof Jodit === 'undefined') {
                console.error("❌ AdminKernel.Wysiwyg: Critical dependency 'Jodit' is missing from the global scope. Initialization aborted.");
                return; // Fail fast
            }

            STATE.globalContext = Object.assign({}, STATE.globalContext, context);
            const textareas = document.querySelectorAll('textarea.js-wysiwyg-editor');
            const isDark = document.documentElement.classList.contains('dark');

            textareas.forEach((textarea) => {
                const fieldKey = textarea.getAttribute('data-field');

                if (!fieldKey) {
                    console.error("❌ AdminKernel.Wysiwyg: Encountered a '.js-wysiwyg-editor' without a required 'data-field' attribute. Element skipped.", textarea);
                    return; // Skip invalid elements without halting valid ones
                }

                if (STATE.registry.has(fieldKey)) {
                    // Idempotency: Skip already initialized editors
                    return;
                }

                createInstance(textarea, fieldKey, isDark);
            });

            initThemeObserver();
        },

        destroy: function (field) {
            const entry = STATE.registry.get(field);
            if (entry) {
                try {
                    entry.editor.destruct();
                } catch (e) {
                    console.error(`⚠️ AdminKernel.Wysiwyg: Failed to cleanly destruct editor for field '${field}'.`, e);
                }
                STATE.registry.delete(field);
                STATE.dirtyFields.delete(field);
            }
        },

        destroyAll: function () {
            for (const field of STATE.registry.keys()) {
                this.destroy(field);
            }
        },

        reinitTheme: function (isDark) {
            for (const [fieldKey, entry] of STATE.registry.entries()) {
                const savedContent = entry.editor.value;
                const element = entry.element;
                const isDirty = STATE.dirtyFields.has(fieldKey); // Preserve dirty state across reinit

                try {
                    entry.editor.destruct();
                } catch (e) {}

                createInstance(element, fieldKey, isDark, savedContent);

                // If it was not dirty before, ensure it stays clean (Jodit change events might fire on init)
                if (!isDirty) {
                    STATE.dirtyFields.delete(fieldKey);
                }
            }
        },

        getInstance: function (field) {
            const entry = STATE.registry.get(field);
            return entry ? entry.editor : undefined;
        },

        has: function (field) {
            return STATE.registry.has(field);
        },

        getContent: function (field) {
            const entry = STATE.registry.get(field);
            return entry ? entry.editor.value : undefined;
        },

        getAllData: function (options = { by: 'field' }) {
            const data = {};
            for (const [fieldKey, entry] of STATE.registry.entries()) {
                data[fieldKey] = entry.editor.value;
            }
            return data;
        },

        isDirty: function (field = null) {
            if (field !== null) {
                return STATE.dirtyFields.has(field);
            }
            return STATE.dirtyFields.size > 0;
        },

        getDirtyFields: function () {
            return Array.from(STATE.dirtyFields);
        },

        resetDirtyState: function () {
            STATE.dirtyFields.clear();
        }
    };

    window.AdminKernel.Wysiwyg = Wysiwyg;

    // ========================================================================
    // Lightweight Usability Wrappers (Global Developer API)
    // ========================================================================
    window.initWysiwyg = function(context = {}) {
        window.AdminKernel.Wysiwyg.initAll(context);
    };

    window.getWysiwygData = function() {
        return window.AdminKernel.Wysiwyg.getAllData({ by: 'field' });
    };

    window.isWysiwygDirty = function(field = null) {
        return window.AdminKernel.Wysiwyg.isDirty(field);
    };

    console.log('✅ AdminKernel.Wysiwyg Module Loaded');
})();
