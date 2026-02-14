# I18n Module Closure Audit Report

## 1) MODULE STATUS
**Status:** **Complete**

- **Schema Alignment:**
  - `i18n_domain_language_summary` and `i18n_key_stats` tables are correctly defined.
  - Indexes are sufficient.
- **Domain Layer:**
  - All repository interfaces (`Contract/*`) have concrete MySQL implementations (`Infrastructure/Mysql/*`).
  - All services and repositories are correctly bound in `Container.php`.
- **Derived Layer:**
  - `MissingCounterService` handles synchronous updates for `summary` and `key_stats` tables via `TranslationWriteService`.
  - `MissingCounterRebuilder` provides a full rebuild strategy.
- **Repository Correctness:**
  - Repositories implement required methods.
  - Pagination contracts are consistent.

## 2) INTEGRATION STATUS
**Status:** **Incomplete**

**Missing Items:**
- **Dead Route (API):** `POST /api/i18n/keys/query` is missing. The UI page `/i18n/keys` exists (`TranslationKeysListController`) but has no API endpoint to fetch data from.
- **Missing Route (UI):** `/i18n/values` is linked in the main navigation but no route definition exists in `AdminRoutes.php`.
- **Broken Navigation:**
  - The "Keys" link (`/i18n/keys`) points to a non-functional page.
  - The "Values" link (`/i18n/values`) results in a 404 error.

## 3) UI/UX STATUS
**Status:** **Incomplete**

**Missing Items:**
- **Domain-First Workflow:**
  - `DomainsListUiController` lists domains but offers no way to drill down into a specific domain's keys or translations.
  - The `idRenderer` in `i18n-domains-core.js` explicitly states "Domain Contract: no 'details' navigation capability". This violates the "Domain-first workflow" requirement if it implies more than just a list.
- **Global Keys View:**
  - The `/i18n/keys` page is a shell with no JavaScript logic to populate the table.
- **Global Values View:**
  - The `/i18n/values` page is completely missing (controller, template, and JS).

## 4) BLOCKERS BEFORE CLOSING I18N
1.  **Fix or Remove `/i18n/keys`:** Either implement the `POST /api/i18n/keys/query` endpoint and wire up the JS, or remove the page and navigation link.
2.  **Fix or Remove `/i18n/values`:** The navigation link points to a non-existent route. Remove it or implement the missing `TranslationValuesListController` and associated API.
3.  **Implement Domain Drill-Down:** Add a "View Keys" or "View Translations" action to the Domain List to enable the Domain-First workflow (linking to `/i18n/scopes/{scope_id}/domains/{domain_id}/...` might be ambiguous as a domain can belong to multiple scopes, requiring a scope selection modal or a dedicated Domain-Centric view).

## 5) NON-BLOCKING IMPROVEMENTS
-   **Global Search:** Implement a global search across all keys/translations (currently only possible within a Scope).
