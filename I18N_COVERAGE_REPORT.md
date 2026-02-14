# Final Report: I18n Coverage UI & Summary Consumption

## 1. New Routes (UI + API)

### API Routes (GET)
- `GET /api/i18n/scopes/{scope_id}/coverage`
  - Controller: `Maatify\AdminKernel\Http\Controllers\Api\I18n\Coverage\I18nScopeCoverageByLanguageController`
  - Returns: Aggregated coverage stats per language for the scope.
- `GET /api/i18n/scopes/{scope_id}/coverage/languages/{language_id}`
  - Controller: `Maatify\AdminKernel\Http\Controllers\Api\I18n\Coverage\I18nScopeCoverageByDomainController`
  - Returns: List of assigned domains with stats for the specific language.

### UI Routes (GET)
- `GET /i18n/scopes/{scope_id}/coverage/languages/{language_id}`
  - Controller: `Maatify\AdminKernel\Http\Controllers\Ui\I18n\I18nScopeLanguageCoverageUiController`
  - Template: `pages/i18n/scope_language_coverage.twig`
  - Purpose: Detailed breakdown page showing domain completion for a language.

## 2. New Controllers & Classes

### Backend (Domain/Infrastructure)
- **Interface:** `Maatify\AdminKernel\Domain\I18n\Coverage\I18nScopeCoverageReaderInterface`
- **Implementation:** `Maatify\AdminKernel\Infrastructure\Repository\I18n\Coverage\PdoI18nScopeCoverageReader`
- **DTOs:**
  - `ScopeCoverageByLanguageItemDTO`
  - `ScopeCoverageByDomainItemDTO`

### API Controllers
- `I18nScopeCoverageByLanguageController`
- `I18nScopeCoverageByDomainController`

### UI Controller
- `I18nScopeLanguageCoverageUiController`

## 3. Frontend Assets

### Templates
- **Modified:** `app/Modules/AdminKernel/Templates/pages/i18n/scope_details.twig` (Added "Language Coverage" section).
- **New:** `app/Modules/AdminKernel/Templates/pages/i18n/scope_language_coverage.twig` (Breakdown page).

### JavaScript
- **New:** `public/assets/maatify/admin-kernel/js/pages/i18n/i18n-scope-coverage.js` (Handles fetching and rendering the coverage table in scope details).
- **New:** `public/assets/maatify/admin-kernel/js/pages/i18n/i18n-scope-language-coverage.js` (Handles fetching and rendering the domain table in breakdown page).
- **Modified:** `public/assets/maatify/admin-kernel/js/pages/i18n/i18n_scope_domain_translations.js` (Added URL query parameter parsing to preselect language in Select2).

## 4. Capabilities & Security
- Used existing capabilities pattern (`window.capabilities` injected from controller).
- Reused `i18n.scopes.list` and `i18n.scopes.details` permissions.
- All new API and UI routes are protected by `AuthorizationGuardMiddleware`, `ScopeGuardMiddleware`, etc. via the existing route group structure.

## 5. Preselection Implementation
- Modified `i18n_scope_domain_translations.js` to read `language_id` from `window.location.search`.
- Passed this value as `defaultValue` to the `Select2` initialization helper.
- Ensure the breakdown page links generate URLs like: `/i18n/scopes/.../domains/.../translations?language_id=123`.
