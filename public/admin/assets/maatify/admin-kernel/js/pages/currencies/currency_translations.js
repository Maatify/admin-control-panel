/**
 * 🌍 Currency Translations Page - Context-Driven Monolith
 * ===========================================================
 * Handles the Translations List table logic for a specific Currency.
 *
 * Pattern D: Context-Driven
 * - Relies on window.currencyTranslationsContext
 */

document.addEventListener("DOMContentLoaded", () => {
  "use strict";

  console.log("🌍 Currency Translations Module Initialized");
  let languageSelect;


  // ========================================================================
  // 1. Validate Context & Capabilities
  // ========================================================================

  if (!window.currencyTranslationsContext) {
    console.error("❌ Missing window.currencyTranslationsContext");
    return;
  }
  const apiEndpoints = window.currencyTranslationsApi || {};

  const context = window.currencyTranslationsContext;
  const capabilities = window.currencyTranslationsCapabilities || {};

  const currencyId = context.currency_id;

  console.log("🚀 Context Currency ID:", currencyId);

  const endpoint = apiEndpoints.query.replace("{currency_id}", currencyId);
  
  // ========================================================================
  // 2. Initialize DataTable Configuration
  // ========================================================================

  const headers = [
    "ID",
    "Language Code",
    "Language Id",
    "Language Name",
    "Translated Name",
    "Has Translation",
    "Actions",
  ];
  const rowKeys = [
    "id",
    "language_code",
    "language_id",
    "language_name",
    "translated_name",
    "has_translation",
    "actions",
  ];

  const customRenderers = {
      id: (value) =>
      value
        ? `<span class="text-gray-500 text-xs font-mono">#${value}</span>`
        : '<span class="text-gray-300 text-xs italic">N/A</span>',
    language_code: (value) =>
      AdminUIComponents.renderCodeBadge(value, {
        color: "blue",
        uppercase: true,
      }),
    language_name: (value, row) =>
      `<span class="font-medium text-gray-900 dark:text-gray-200">${value}</span>`,
    translated_name: (value) =>
      value
        ? `<span class="font-medium text-gray-900 dark:text-gray-200">${value}</span>`
        : '<span class="text-gray-400 italic text-sm">Base name fallback</span>',
    has_translation: (value) => {
      if (value) {
        return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Translated</span>`;
      }
      return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Untranslated</span>`;
    },
    
    actions: (value, row) => {
      const actions = [];

      if (capabilities.can_upsert)
         { console.log(row)
        const safeValue = (row.translated_name || "").replace(/"/g, "&quot;");
        actions.push(`
                    <button class="btn-edit-translation text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 mr-2"
                            title="Edit"
                            data-language-id="${row.language_id}"
                            
                            data-language-name="${row.language_name}"
                            data-language-code="${row.language_code}"
                            data-translated-name="${safeValue}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                `);
      }

      if (capabilities.can_delete && row.has_translation) {
        actions.push(`
                    <button class="btn-delete-translation text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                            title="Delete"
                            data-language-id="${row.language_id}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                `);
      }

      if (actions.length === 0) {
        return '<span class="text-gray-400 text-xs">No actions</span>';
      }

      return `<div class="flex items-center gap-1">${actions.join("")}</div>`;
    },
  };

  const containerId = "translations-table-container";
  // Use the endpoint defined in the context
  const apiUrl = endpoint;

  const getFilters = () => {
    const filters = {};

    
    const filterTranslatedName = document
      .getElementById("filter-translated-name")
      ?.value?.trim();
    if (filterTranslatedName) filters.name = filterTranslatedName;

    if (languageSelect) {
      const selectedLang = languageSelect.getValue();
      if (selectedLang) {
        filters.language_id = selectedLang;
      }
    }

    const filterHasTranslation = document.getElementById("filter-has-translation")?.value;
    if (filterHasTranslation !== "" && filterHasTranslation !== undefined) {
      filters.has_translation = filterHasTranslation;
    }

    
    return filters;
  };

  const getPaginationInfo = (pagination) => {
    const { page = 1, per_page = 20, total = 0, filtered = total } = pagination;
    const displayCount = filtered !== undefined ? filtered : total;
    const startItem = displayCount === 0 ? 0 : (page - 1) * per_page + 1;
    const endItem = Math.min(page * per_page, displayCount);

    let infoText = `<span>${startItem} to ${endItem}</span> of <span>${displayCount}</span>`;

    if (typeof filtered !== "undefined" && filtered !== total) {
      infoText += ` <span class="text-gray-500 dark:text-gray-400">(filtered from ${total} total)</span>`;
    }

    return { total: displayCount, info: infoText };
  };

  let currentPage = 1;
  let currentPerPage = 20;

  const loadTable = () => {
    const filters = getFilters();
    const globalSearch =
      document.getElementById("translations-search-global")?.value?.trim() ||
      "";

    const params = {
      page: currentPage,
      per_page: currentPerPage,
    };

    const search = {};
    if (globalSearch) search.global = globalSearch;
    if (Object.keys(filters).length > 0) search.columns = filters;

    if (Object.keys(search).length > 0) {
      params.search = search;
    }

    const container = document.getElementById(containerId);
    if (!container) return;

    const originalTableContainer = document.getElementById("table-container");
    let tempId = null;

    if (originalTableContainer && originalTableContainer !== container) {
      tempId = "table-container-original-" + Date.now();
      originalTableContainer.id = tempId;
    }

    const originalContainerId = container.id;
    container.id = "table-container";

    createTable(
      apiUrl,
      params,
      headers,
      rowKeys,
      false,
      "language_id",
      null,
      customRenderers,
      null,
      getPaginationInfo,
    )
      .then(() => {
        container.id = originalContainerId;
        if (tempId && originalTableContainer)
          originalTableContainer.id = "table-container";
      })
      .catch((err) => {
        console.error("Table creation failed", err);
        container.id = originalContainerId;
        if (tempId && originalTableContainer)
          originalTableContainer.id = "table-container";
      });
  };

  initLanguageDropdown();
  loadTable();

  // ========================================================================
  // 4. Setup Filters & Events
  // ========================================================================

  const filterForm = document.getElementById("translations-filter-form");
  if (filterForm) {
    filterForm.addEventListener("submit", (e) => {
      e.preventDefault();
      currentPage = 1;
      loadTable();
    });

    document
      .getElementById("btn-reset-filters")
      ?.addEventListener("click", () => {
        filterForm.reset();
        currentPage = 1;
        loadTable();
        if (languageSelect) {
          languageSelect.destroy();
          initLanguageDropdown(); // Re-init with default null
        }
      });
  }

  document
    .getElementById("btn-search-global")
    ?.addEventListener("click", () => {
      currentPage = 1;
      loadTable();
    });

  document.getElementById("btn-clear-search")?.addEventListener("click", () => {
    const searchInput = document.getElementById("translations-search-global");
    if (searchInput) searchInput.value = "";
    currentPage = 1;
    loadTable();
  });

  const globalSearchInput = document.getElementById(
    "translations-search-global",
  );
  if (globalSearchInput) {
    globalSearchInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        currentPage = 1;
        loadTable();
      }
    });
  }

  document
    .getElementById("filter-has-translation")
    ?.addEventListener("change", () => {
      currentPage = 1;
      loadTable();
    });

  document.addEventListener("tableAction", (e) => {
    const { action, value } = e.detail;
    if (action === "pageChange") {
      currentPage = value;
      loadTable();
    } else if (action === "perPageChange") {
      currentPerPage = value;
      currentPage = 1;
      loadTable();
    }
  });

  // ========================================================================
  // 5. Language Dropdown Initialization
  // ========================================================================

  function initLanguageDropdown() {
    // Use injected languages list if available
    // The contract says: "Languages list is injected at page render time."
    // window.currencyTranslationsContext.languages

    const context = window.currencyTranslationsContext || {};
    const languages = context.languages || [];

    if (languages.length > 0) {
      const options = languages.map((lang) => ({
        value: String(lang.id),
        label: `${lang.name} (${lang.code})`,
        search: lang.code, // Allow searching by code
      }));

      languageSelect = Select2("#translation-filter-language-id", options, {
        defaultValue: null,
        onChange: (value) => {
          currentPage = 1;
          loadTable();
        },
        
      });
    } else {
      console.warn("⚠️ No languages found in context.");
    }
  }
  // ========================================================================
  // 6. Actions & Modals (Delegation)
  // ========================================================================

  const modal = document.getElementById("edit-translation-modal");
  const modalLanguageId = document.getElementById("edit-language-id");
  const modalCurrencyDisplay = document.getElementById("edit-currency-display");
  const modalLanguageNameDisplay = document.getElementById(
    "edit-language-name-display",
  );
  const modalTranslationValue = document.getElementById(
    "edit-translation-value",
  );
  const btnSaveTranslation = document.getElementById("btn-save-translation");

  document.addEventListener("click", (e) => {
    const editBtn = e.target.closest(".btn-edit-translation");
    if (editBtn) {
      openEditModal(
        editBtn.getAttribute("data-language-id"),
        editBtn.getAttribute("data-language-name"),
        editBtn.getAttribute("data-language-code"),
        editBtn.getAttribute("data-translated-name"),
      );
      return;
    }

    const deleteBtn = e.target.closest(".btn-delete-translation");
    if (deleteBtn) {
      handleDelete(deleteBtn.getAttribute("data-language-id"));
      return;
    }
  });

  function openEditModal(
    languageId,
    languageName,
    languageCode,
    translatedName,
  ) {
    if (!modal) return;

    modalLanguageId.value = languageId;
    modalCurrencyDisplay.textContent = context.currency_code;
    modalLanguageNameDisplay.textContent = `${languageName} (${languageCode})`;
    modalTranslationValue.value = translatedName;

    modal.classList.remove("hidden");
  }

  function closeEditModal() {
    if (!modal) return;
    modal.classList.add("hidden");
  }

  if (modal) {
    modal.querySelectorAll(".close-modal").forEach((btn) => {
      btn.addEventListener("click", closeEditModal);
    });

    modal.addEventListener("click", (e) => {
      if (e.target === modal) closeEditModal();
    });
  }

  if (btnSaveTranslation) {
    btnSaveTranslation.addEventListener("click", () => {
      const languageId = modalLanguageId.value;
      const newValue = modalTranslationValue.value;

      if (newValue.trim() === "") {
        ApiHandler.showAlert(
          "warning",
          "Value cannot be empty. Use delete to remove translation.",
        );
        return;
      }

      upsertTranslation(languageId, newValue);
    });
  }

  function handleDelete(languageId) {
    if (confirm("Are you sure you want to delete this translation?")) {
      deleteTranslation(languageId);
    }
  }

  async function upsertTranslation(languageId, value) {
    const endpoint = `currencies/${currencyId}/translations/upsert`;
    const payload = {
      language_id: parseInt(languageId, 10),
      translated_name: value,
    };

    try {
      const result = await ApiHandler.call(
        endpoint,
        payload,
        "Upsert Translation",
      );
      if (result.success) {
        ApiHandler.showAlert("success", "Translation saved successfully");
        closeEditModal();
        loadTable();
      } else {
        ApiHandler.showAlert(
          "danger",
          result.error || "Failed to save translation",
        );
      }
    } catch (error) {
      console.error("Upsert error:", error);
      ApiHandler.showAlert("danger", "An error occurred while saving");
    }
  }

  async function deleteTranslation(languageId) {
    const endpoint = `currencies/${currencyId}/translations/delete`;
    const payload = {
      language_id: parseInt(languageId, 10),
    };

    try {
      const result = await ApiHandler.call(
        endpoint,
        payload,
        "Delete Translation",
      );
      if (result.success) {
        ApiHandler.showAlert("success", "Translation deleted successfully");
        loadTable();
      } else {
        ApiHandler.showAlert(
          "danger",
          result.error || "Failed to delete translation",
        );
      }
    } catch (error) {
      console.error("Delete error:", error);
      ApiHandler.showAlert("danger", "An error occurred while deleting");
    }
  }
});
