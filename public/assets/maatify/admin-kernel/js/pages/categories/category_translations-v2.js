/**
 * 🌍 Category Translations V2 (Bridge-first)
 *
 * Mirrors the currency_translations-v2.js pattern exactly.
 * Context injected by CategoryTranslationsListUiController via Twig:
 *   window.categoryTranslationsContext  — { category_id, category_name, category_slug, languages }
 *   window.categoryTranslationsApi      — { query, upsert, delete }
 *   window.categoryTranslationsCapabilities — { can_upsert, can_delete }
 *   window.categoryTranslationsTableContainerId
 */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    if (!window.AdminPageBridge) {
      console.error('❌ AdminPageBridge missing for category_translations-v2');
      return;
    }

    const Bridge = window.AdminPageBridge;
    const Helpers = window.CategoriesHelpersV2;
    let languageSelect;

    if (!window.categoryTranslationsContext) {
      console.error('❌ Missing window.categoryTranslationsContext');
      return;
    }

    const apiEndpoints   = window.categoryTranslationsApi || {};
    const context        = window.categoryTranslationsContext;
    const capabilities   = window.categoryTranslationsCapabilities || {};
    const categoryId     = context.category_id;
    const apiUrl         = apiEndpoints.query || '';

    const headers  = ['ID', 'Language Code', 'Language Id', 'Language Name', 'Translated Name', 'Description', 'Has Translation', 'Actions'];
    const rowKeys  = ['id', 'language_code', 'language_id', 'language_name', 'translated_name', 'translated_description', 'has_translation', 'actions'];

    const customRenderers = {
      id: function (value) {
        return value
          ? '<span class="text-gray-500 text-xs font-mono">#' + value + '</span>'
          : '<span class="text-gray-300 text-xs italic">N/A</span>';
      },
      language_code: function (value) {
        return AdminUIComponents.renderCodeBadge(value, { color: 'blue', uppercase: true });
      },
      language_name: function (value) {
        return '<span class="font-medium text-gray-900 dark:text-gray-200">' + value + '</span>';
      },
      translated_name: function (value) {
        return value
          ? '<span class="font-medium text-gray-900 dark:text-gray-200">' + value + '</span>'
          : '<span class="text-gray-400 italic text-sm">Base name fallback</span>';
      },
      translated_description: function (value) {
        return value
          ? '<span class="text-gray-700 dark:text-gray-300 text-sm">' + value + '</span>'
          : '<span class="text-gray-300 dark:text-gray-600 italic text-xs">—</span>';
      },
      has_translation: function (value) {
        return value
          ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Translated</span>'
          : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Untranslated</span>';
      },
      actions: function (value, row) {
        const actions = [];

        if (capabilities.can_upsert) {
          const safeValue    = (row.translated_name        || '').replace(/"/g, '&quot;');
          const safeDesc     = (row.translated_description || '').replace(/"/g, '&quot;');
          const safeLangName = (row.language_name          || '').replace(/"/g, '&quot;');
          const safeLangCode = (row.language_code          || '').replace(/"/g, '&quot;');
          actions.push(
            '<button class="btn-edit-translation text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 mr-2" title="Edit"' +
            ' data-language-id="' + row.language_id + '"' +
            ' data-language-name="' + safeLangName + '"' +
            ' data-language-code="' + safeLangCode + '"' +
            ' data-translated-name="' + safeValue + '"' +
            ' data-translated-description="' + safeDesc + '">' +
            '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>' +
            '</button>'
          );
        }

        if (capabilities.can_delete && row.has_translation) {
          actions.push(
            '<button class="btn-delete-translation text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Delete"' +
            ' data-language-id="' + row.language_id + '">' +
            '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>' +
            '</button>'
          );
        }

        if (!actions.length) return '<span class="text-gray-400 text-xs">No actions</span>';
        return '<div class="flex items-center gap-1">' + actions.join('') + '</div>';
      }
    };

    const containerId = window.categoryTranslationsTableContainerId || 'translations-table-container';
    let currentPage    = 1;
    let currentPerPage = 20;

    const reloadTable = function () {
      return loadTable();
    };

    function getFilters() {
      const filters = {};

      const translatedName = Bridge.DOM.value('#filter-translated-name', '').trim();
      if (translatedName) filters.name = translatedName;

      if (languageSelect) {
        const selectedLang = languageSelect.getValue();
        if (selectedLang) filters.language_id = selectedLang;
      }

      const hasTranslation = Bridge.DOM.value('#filter-has-translation', '');
      if (hasTranslation !== '' && hasTranslation !== undefined) {
        filters.has_translation = hasTranslation;
      }

      return filters;
    }

    function getPaginationInfo(pagination) {
      const page     = pagination.page     || 1;
      const perPage  = pagination.per_page || 20;
      const total    = pagination.total    || 0;
      const filtered = pagination.filtered === undefined ? total : pagination.filtered;
      const start    = filtered === 0 ? 0 : (page - 1) * perPage + 1;
      const end      = Math.min(page * perPage, filtered);
      let info = '<span>' + start + ' to ' + end + '</span> of <span>' + filtered + '</span>';
      if (filtered !== total) info += ' <span class="text-gray-500 dark:text-gray-400">(filtered from ' + total + ' total)</span>';
      return { total: filtered, info };
    }

    function buildParams() {
      const filters      = getFilters();
      const globalSearch = Bridge.DOM.value('#translations-search-global', '').trim();
      const params       = { page: currentPage, per_page: currentPerPage };
      const search       = {};
      if (globalSearch)                      search.global  = globalSearch;
      if (Object.keys(filters).length > 0)   search.columns = filters;
      if (Object.keys(search).length > 0)    params.search  = search;
      return params;
    }

    function loadTable() {
      const params = buildParams();
      return Bridge.Table.withTargetContainer(containerId, function () {
        return createTable(
          apiUrl, params, headers, rowKeys,
          false, 'language_id', null, customRenderers, null, getPaginationInfo
        ).catch(function (err) {
          console.error('Table creation failed', err);
        });
      });
    }

    function initLanguageDropdown() {
      const languages = (window.categoryTranslationsContext || {}).languages || [];
      if (!languages.length) {
        console.warn('⚠️ No languages found in context.');
        return;
      }
      const options = languages.map(function (lang) {
        return { value: String(lang.id), label: lang.name + ' (' + lang.code + ')', search: lang.code };
      });
      languageSelect = Select2('#translation-filter-language-id', options, {
        defaultValue: null,
        onChange: resetPageAndReload
      });
    }

    // ── Modal ─────────────────────────────────────────────────────────────
    const modal                     = document.getElementById('edit-translation-modal');
    const modalLanguageId           = document.getElementById('edit-language-id');
    const modalCategoryDisplay      = document.getElementById('edit-category-display');
    const modalLanguageNameDisplay  = document.getElementById('edit-language-name-display');
    const modalTranslationValue     = document.getElementById('edit-translation-value');
    const modalTranslationDesc      = document.getElementById('edit-translation-description');
    const btnSaveTranslation        = document.getElementById('btn-save-translation');

    const resetPageAndReload = Helpers?.bindResetPageReload
      ? Helpers.bindResetPageReload({
        setPage: function (page) { currentPage = page; },
        reload:  function ()     { return loadTable(); }
      })
      : function () { currentPage = 1; return loadTable(); };

    function openEditModal(languageId, languageName, languageCode, translatedName, translatedDescription) {
      if (!modal) return;
      modalLanguageId.value = languageId;
      modalCategoryDisplay.textContent = context.category_name;
      modalLanguageNameDisplay.textContent = languageName + ' (' + languageCode + ')';
      modalTranslationValue.value = translatedName || '';
      if (modalTranslationDesc) modalTranslationDesc.value = translatedDescription || '';
      Bridge.Modal.open(modal);
    }

    function closeEditModal() {
      if (!modal) return;
      Bridge.Modal.close(modal);
    }

    if (Helpers?.setupModalCloseHandlers) {
      Helpers.setupModalCloseHandlers();
    } else if (modal) {
      modal.querySelectorAll('.close-modal').forEach(function (btn) {
        btn.addEventListener('click', closeEditModal);
      });
      modal.addEventListener('click', function (e) {
        if (e.target === modal) closeEditModal();
      });
    }

    Bridge.Events.onClick('.btn-edit-translation', function (event, editBtn) {
      openEditModal(
        editBtn.getAttribute('data-language-id'),
        editBtn.getAttribute('data-language-name'),
        editBtn.getAttribute('data-language-code'),
        editBtn.getAttribute('data-translated-name'),
        editBtn.getAttribute('data-translated-description')
      );
    });

    Bridge.Events.onClick('.btn-delete-translation', function (event, deleteBtn) {
      const languageId = deleteBtn.getAttribute('data-language-id');
      Bridge.API.runMutation({
        operation:      'Delete Translation',
        endpoint:       'categories/' + categoryId + '/translations/delete',
        method:         'POST',
        payload:        { language_id: Bridge.normalizeInt(languageId, 0) },
        confirmMessage: 'Are you sure you want to delete this translation?',
        successMessage: 'Translation deleted successfully',
        reloadHandler:  reloadTable
      });
    });

    if (btnSaveTranslation) {
      btnSaveTranslation.addEventListener('click', function () {
        const newValue = Bridge.DOM.value(modalTranslationValue, '');
        if (!newValue.trim()) {
          Bridge.UI.warning('Value cannot be empty. Use delete to remove translation.');
          return;
        }

        const payload = {
          language_id:            Bridge.normalizeInt(Bridge.DOM.value(modalLanguageId, 0), 0),
          translated_name:        newValue,
          translated_description: modalTranslationDesc ? Bridge.DOM.value(modalTranslationDesc, '') : undefined,
        };

        Bridge.API.runMutation({
          operation:      'Upsert Translation',
          endpoint:       'categories/' + categoryId + '/translations/upsert',
          method:         'POST',
          payload,
          successMessage: 'Translation saved successfully',
          reloadHandler:  reloadTable,
          modal
        });
      });
    }

    // ── Filter form ───────────────────────────────────────────────────────
    Bridge.Events.bindFilterForm({
      form:        '#translations-filter-form',
      resetButton: '#btn-reset-filters',
      onSubmit: resetPageAndReload,
      onReset: function () {
        if (languageSelect) {
          languageSelect.destroy();
          initLanguageDropdown();
        }
        resetPageAndReload();
      }
    });

    const searchBtn = document.getElementById('btn-search-global');
    if (searchBtn) searchBtn.addEventListener('click', resetPageAndReload);

    const clearBtn = document.getElementById('btn-clear-search');
    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        const input = document.getElementById('translations-search-global');
        if (input) input.value = '';
        resetPageAndReload();
      });
    }

    Bridge.Events.bindDebouncedInput({
      input:     '#translations-search-global',
      delay:     400,
      eventName: 'input',
      onFire:    resetPageAndReload
    });

    Bridge.Events.bindEnterAction({
      input:          '#translations-search-global',
      onEnter:        function (_, ctx) { resetPageAndReload(ctx.event); },
      ignoreInsideForm: false,
      preventDefault: true
    });

    const hasTranslationSelect = document.getElementById('filter-has-translation');
    if (hasTranslationSelect) {
      hasTranslationSelect.addEventListener('change', resetPageAndReload);
    }

    if (Helpers?.bindTableActionState) {
      Helpers.bindTableActionState({
        getParams:         buildParams,
        sourceContainerId: containerId,
        getState:  function () { return { page: currentPage, perPage: currentPerPage }; },
        setState:  function (state) {
          currentPage    = state.page    ?? currentPage;
          currentPerPage = state.perPage ?? currentPerPage;
        },
        reload: function () { return loadTable(); }
      });
    }

    initLanguageDropdown();
    loadTable();

    window.reloadCategoryTranslationsTableV2 = reloadTable;
    window.CategoryTranslationsV2 = { reload: reloadTable };
  });
})();

