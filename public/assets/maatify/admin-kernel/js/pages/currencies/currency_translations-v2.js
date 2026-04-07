/**
 * 🌍 Currency Translations V2 (Bridge-first)
 */

(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
    if (!window.AdminPageBridge) {
      console.error('❌ AdminPageBridge missing for currency_translations-v2');
      return;
    }

    const Bridge = window.AdminPageBridge;
    let languageSelect;

    if (!window.currencyTranslationsContext) {
      console.error('❌ Missing window.currencyTranslationsContext');
      return;
    }

    const apiEndpoints = window.currencyTranslationsApi || {};
    const context = window.currencyTranslationsContext;
    const capabilities = window.currencyTranslationsCapabilities || {};
    const currencyId = context.currency_id;
    const apiUrl = (apiEndpoints.query || '').replace('{currency_id}', currencyId);

    const headers = ['ID', 'Language Code', 'Language Id', 'Language Name', 'Translated Name', 'Has Translation', 'Actions'];
    const rowKeys = ['id', 'language_code', 'language_id', 'language_name', 'translated_name', 'has_translation', 'actions'];

    const customRenderers = {
      id: function(value) {
        return value ? '<span class="text-gray-500 text-xs font-mono">#' + value + '</span>' : '<span class="text-gray-300 text-xs italic">N/A</span>';
      },
      language_code: function(value) {
        return AdminUIComponents.renderCodeBadge(value, { color: 'blue', uppercase: true });
      },
      language_name: function(value) {
        return '<span class="font-medium text-gray-900 dark:text-gray-200">' + value + '</span>';
      },
      translated_name: function(value) {
        return value ? '<span class="font-medium text-gray-900 dark:text-gray-200">' + value + '</span>' : '<span class="text-gray-400 italic text-sm">Base name fallback</span>';
      },
      has_translation: function(value) {
        if (value) return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Translated</span>';
        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Untranslated</span>';
      },
      actions: function(value, row) {
        const actions = [];

        if (capabilities.can_upsert) {
          const safeValue = (row.translated_name || '').replace(/"/g, '&quot;');
          actions.push('<button class="btn-edit-translation text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 mr-2" title="Edit" data-language-id="' + row.language_id + '" data-language-name="' + row.language_name + '" data-language-code="' + row.language_code + '" data-translated-name="' + safeValue + '"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>');
        }

        if (capabilities.can_delete && row.has_translation) {
          actions.push('<button class="btn-delete-translation text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300" title="Delete" data-language-id="' + row.language_id + '"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>');
        }

        if (!actions.length) return '<span class="text-gray-400 text-xs">No actions</span>';
        return '<div class="flex items-center gap-1">' + actions.join('') + '</div>';
      }
    };

    const containerId = 'translations-table-container';
    let currentPage = 1;
    let currentPerPage = 20;
    const reloadCurrencyTranslationsTableV2 = function() {
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
      const page = pagination.page || 1;
      const perPage = pagination.per_page || 20;
      const total = pagination.total || 0;
      const filtered = pagination.filtered === undefined ? total : pagination.filtered;
      const displayCount = filtered;
      const startItem = displayCount === 0 ? 0 : (page - 1) * perPage + 1;
      const endItem = Math.min(page * perPage, displayCount);
      let infoText = '<span>' + startItem + ' to ' + endItem + '</span> of <span>' + displayCount + '</span>';
      if (filtered !== total) infoText += ' <span class="text-gray-500 dark:text-gray-400">(filtered from ' + total + ' total)</span>';
      return { total: displayCount, info: infoText };
    }

    function buildParams() {
      const filters = getFilters();
      const globalSearch = Bridge.DOM.value('#translations-search-global', '').trim();

      const params = { page: currentPage, per_page: currentPerPage };
      const search = {};
      if (globalSearch) search.global = globalSearch;
      if (Object.keys(filters).length > 0) search.columns = filters;
      if (Object.keys(search).length > 0) params.search = search;
      return params;
    }

    function withTableContainerTarget(run) {
      const container = document.getElementById(containerId);
      if (!container) return Promise.resolve();

      const original = document.getElementById('table-container');
      let tempId = null;
      if (original && original !== container) {
        tempId = 'table-container-original-' + Date.now();
        original.id = tempId;
      }

      const containerOriginalId = container.id;
      container.id = 'table-container';

      const finish = function() {
        container.id = containerOriginalId;
        if (tempId && original) original.id = 'table-container';
      };

      return Promise.resolve(run()).finally(finish);
    }

    function loadTable() {
      const params = buildParams();

      return withTableContainerTarget(function() {
        return createTable(
          apiUrl,
          params,
          headers,
          rowKeys,
          false,
          'language_id',
          null,
          customRenderers,
          null,
          getPaginationInfo
        ).catch(function(err) {
          console.error('Table creation failed', err);
        });
      });
    }

    function initLanguageDropdown() {
      const languages = (window.currencyTranslationsContext || {}).languages || [];
      if (!languages.length) {
        console.warn('⚠️ No languages found in context.');
        return;
      }

      const options = languages.map(function(lang) {
        return { value: String(lang.id), label: lang.name + ' (' + lang.code + ')', search: lang.code };
      });

      languageSelect = Select2('#translation-filter-language-id', options, {
        defaultValue: null,
        onChange: function() {
          currentPage = 1;
          loadTable();
        }
      });
    }

    const modal = document.getElementById('edit-translation-modal');
    const modalLanguageId = document.getElementById('edit-language-id');
    const modalCurrencyDisplay = document.getElementById('edit-currency-display');
    const modalLanguageNameDisplay = document.getElementById('edit-language-name-display');
    const modalTranslationValue = document.getElementById('edit-translation-value');
    const btnSaveTranslation = document.getElementById('btn-save-translation');

    function openEditModal(languageId, languageName, languageCode, translatedName) {
      if (!modal) return;
      modalLanguageId.value = languageId;
      modalCurrencyDisplay.textContent = context.currency_code;
      modalLanguageNameDisplay.textContent = languageName + ' (' + languageCode + ')';
      modalTranslationValue.value = translatedName || '';
      Bridge.Modal.open(modal);
    }

    function closeEditModal() {
      if (!modal) return;
      Bridge.Modal.close(modal);
    }

    if (modal) {
      modal.querySelectorAll('.close-modal').forEach(function(btn) {
        btn.addEventListener('click', closeEditModal);
      });
      modal.addEventListener('click', function(e) {
        if (e.target === modal) closeEditModal();
      });
    }

    Bridge.Events.onClick('.btn-edit-translation', function(event, editBtn) {
      openEditModal(
        editBtn.getAttribute('data-language-id'),
        editBtn.getAttribute('data-language-name'),
        editBtn.getAttribute('data-language-code'),
        editBtn.getAttribute('data-translated-name')
      );
    });

    Bridge.Events.onClick('.btn-delete-translation', function(event, deleteBtn) {
      const languageId = deleteBtn.getAttribute('data-language-id');
      Bridge.API.runMutation({
        operation: 'Delete Translation',
        endpoint: 'currencies/' + currencyId + '/translations/delete',
        method: 'POST',
        payload: { language_id: Bridge.normalizeInt(languageId, 0) },
        confirmMessage: 'Are you sure you want to delete this translation?',
        successMessage: 'Translation deleted successfully',
        reloadHandler: reloadCurrencyTranslationsTableV2
      });
    });

    if (btnSaveTranslation) {
      btnSaveTranslation.addEventListener('click', function() {
        const newValue = Bridge.DOM.value(modalTranslationValue, '');
        if (!newValue.trim()) {
          Bridge.UI.warning('Value cannot be empty. Use delete to remove translation.');
          return;
        }

        const payload = {
          language_id: Bridge.normalizeInt(Bridge.DOM.value(modalLanguageId, 0), 0),
          translated_name: newValue
        };

        Bridge.API.runMutation({
          operation: 'Upsert Translation',
          endpoint: 'currencies/' + currencyId + '/translations/upsert',
          method: 'POST',
          payload,
          successMessage: 'Translation saved successfully',
          reloadHandler: reloadCurrencyTranslationsTableV2,
          modal
        });
      });
    }

    Bridge.Events.bindFilterForm({
      form: '#translations-filter-form',
      resetButton: '#btn-reset-filters',
      onSubmit: function() {
        currentPage = 1;
        loadTable();
      },
      onReset: function() {
        currentPage = 1;
        if (languageSelect) {
          languageSelect.destroy();
          initLanguageDropdown();
        }
        loadTable();
      }
    });

    const searchBtn = document.getElementById('btn-search-global');
    if (searchBtn) {
      searchBtn.addEventListener('click', function() {
        currentPage = 1;
        loadTable();
      });
    }

    const clearBtn = document.getElementById('btn-clear-search');
    if (clearBtn) {
      clearBtn.addEventListener('click', function() {
        const input = document.getElementById('translations-search-global');
        if (input) input.value = '';
        currentPage = 1;
        loadTable();
      });
    }

    Bridge.Events.bindDebouncedInput({
      input: '#translations-search-global',
      delay: 400,
      eventName: 'input',
      onFire: function() {
        currentPage = 1;
        loadTable();
      }
    });

    const globalSearchInput = document.getElementById('translations-search-global');
    if (globalSearchInput) {
      globalSearchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          currentPage = 1;
          loadTable();
        }
      });
    }

    const hasTranslationSelect = document.getElementById('filter-has-translation');
    if (hasTranslationSelect) {
      hasTranslationSelect.addEventListener('change', function() {
        currentPage = 1;
        loadTable();
      });
    }

    document.addEventListener('tableAction', function(e) {
      const detail = e.detail || {};
      const next = Bridge.Table.applyActionParams(buildParams(), { action: detail.action, value: detail.value });
      currentPage = next.page ?? currentPage;
      currentPerPage = next.per_page ?? currentPerPage;
      loadTable();
    });

    initLanguageDropdown();
    loadTable();

    window.reloadCurrencyTranslationsTableV2 = reloadCurrencyTranslationsTableV2;
    window.CurrencyTranslationsV2 = {
      reload: reloadCurrencyTranslationsTableV2
    };
  });
})();
