# I18n System Closure & Consistency Audit Report

## 1. BACKEND STATUS
**Status:** COMPLETE (Functionally)

**Findings:**
- **Services:** `TranslationWriteService`, `MissingCounterService`, `I18nGovernancePolicyService` are implemented and used.
- **Repositories:** All repositories (Scope, Domain, Key, Translation, Summary, Stats) are implemented.
- **Dead Logic:**
    - `i18n_domain_language_summary` table: Maintained synchronously by `MissingCounterService` but NEVER READ by any controller/reader.
    - `i18n_key_stats` table: Maintained synchronously by `MissingCounterService` but NEVER READ by any controller/reader.
    - `I18nStatsRebuilder` and `MissingCounterRebuilder`: Exist and are reachable but their output is ignored by the read path.
- **Wiring:** Event-driven updates via `MissingCounterService` are correctly wired in `TranslationWriteService`.

## 2. ROUTING STATUS
**Status:** COMPLETE

**Findings:**
- **Routes:** All routes in `AdminRoutes.php` map to existing controllers.
- **Controllers:** All controllers in `app/Modules/AdminKernel/Http/Controllers/Api/I18n` are reachable via routes.
- **Middleware:** `AuthorizationGuardMiddleware` and `ScopeGuardMiddleware` are consistently applied.

## 3. UI/UX STATUS
**Status:** COMPLETE

**Findings:**
- **Templates:** `scopes.list.twig`, `scope_details.twig`, `scope_domain_keys_summary.twig` exist and correctly inject capabilities (`window.capabilities`, `window.i18nScopeDomainKeysContext`).
- **Navigation:** Scope-First workflow (Scope List -> Scope Details -> Domain Keys Summary) is implemented.
- **Context Binding:** Scope and Domain IDs/Codes are correctly propagated via URL parameters and context objects.
- **Scripts:** Referenced JS files exist in `public/assets/maatify/admin-kernel/js/pages/i18n/`.

## 4. DERIVED LAYER STATUS
**Status:** DRIFT DETECTED

**Findings:**
- **Drift:** The backend maintains synchronous consistency for `i18n_domain_language_summary` and `i18n_key_stats`, but the frontend/API read path (`PdoI18nScopesQueryReader`, `PdoI18nScopeDomainKeysSummaryQueryReader`) uses raw SQL aggregations (COUNT/JOIN) instead of consuming these pre-calculated stats.
- **Impact:** While functional correctness is maintained, the system incurs write-time overhead for derived data that is currently wasted.

## 5. BLOCKERS BEFORE I18N CLOSURE
**Status:** NONE

The system is functionally complete and consistent from a user perspective. The backend "Dead Logic" (unused derived tables) is an optimization concern, not a blocker for closure.

## 6. NON-BLOCKING IMPROVEMENTS
1.  **Consume `i18n_domain_language_summary`**: Update `PdoI18nScopesQueryReader` and `PdoI18nDomainsQueryReader` to join with the summary table and expose translation progress (e.g., "% Translated") in the list views.
2.  **Consume `i18n_key_stats`**: Update `PdoI18nScopeDomainKeysSummaryQueryReader` to use `i18n_key_stats` for `translated_count` when no language filter is applied, avoiding expensive `COUNT(*)` over `i18n_translations`.
