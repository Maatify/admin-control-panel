# 🔍 I18N FULL SYSTEM FORENSIC AUDIT (HARD MODE)

## 1️⃣ Entry Points Map

| UI Route                                    | Controller                              | Template                         | JS File                                            |
|:--------------------------------------------|:----------------------------------------|:---------------------------------|:---------------------------------------------------|
| `/i18n/scopes`                              | `ScopesListUiController`                | `scopes.list.twig`               | `i18n-scopes-core.js`                              |
| `/i18n/scopes/{id}`                         | `ScopeDetailsController`                | `scope_details.twig`             | `i18n_scopes_domains.js`, `i18n-scope-coverage.js` |
| `/i18n/scopes/{s}/domains/{d}/keys`         | `ScopeDomainKeysSummaryController`      | `scope_domain_keys_summary.twig` | `i18n_scope_domain_keys_coverage.js`               |
| `/i18n/scopes/{s}/domains/{d}/translations` | `ScopeDomainTranslationsUiController`   | `scope_domain_translations.twig` | `i18n_scope_domain_translations.js`                |
| `/i18n/scopes/{s}/coverage/languages/{l}`   | `I18nScopeLanguageCoverageUiController` | `scope_language_coverage.twig`   | `i18n-scope-language-coverage.js`                  |
| `/i18n/domains`                             | `DomainsListUiController`               | `domains.list.twig`              | `i18n-domains-core.js`                             |
| `/languages`                                | `LanguagesListController`               | `languages_list.twig`            | `languages-with-components.js`                     |

---

## 2️⃣ API Map

| API Route                                             | Method | Controller                                  | Reader/Service                              |
|:------------------------------------------------------|:-------|:--------------------------------------------|:--------------------------------------------|
| `/api/i18n/scopes/query`                              | POST   | `I18nScopesQueryController`                 | `PdoI18nScopesQueryReader`                  |
| `/api/i18n/scopes/{id}/domains/query`                 | POST   | `I18nScopeDomainsQueryController`           | `PdoI18nScopeDomainsQueryReader`            |
| `/api/i18n/scopes/{s}/domains/{d}/keys/query`         | POST   | `I18nScopeDomainKeysSummaryQueryController` | `PdoI18nScopeDomainKeysSummaryQueryReader`  |
| `/api/i18n/scopes/{s}/domains/{d}/translations/query` | POST   | `ScopeDomainTranslationsQueryController`    | `PdoI18nScopeDomainTranslationsQueryReader` |
| `/api/i18n/scopes/{id}/coverage`                      | GET    | `I18nScopeCoverageByLanguageController`     | `PdoI18nScopeCoverageReader`                |
| `/api/i18n/scopes/{s}/coverage/languages/{l}`         | GET    | `I18nScopeCoverageByDomainController`       | `PdoI18nScopeCoverageReader`                |
| `/api/i18n/domains/query`                             | POST   | `I18nDomainsQueryController`                | `PdoI18nDomainsQueryReader`                 |
| `/api/languages/query`                                | POST   | `LanguagesQueryController`                  | `PdoLanguageQueryReader`                    |

---

## 3️⃣ Table Usage Matrix

| Table                          | Read By                                                                                 | Written By                      | Used in UI | Derived Only | Dead    |
|:-------------------------------|:----------------------------------------------------------------------------------------|:--------------------------------|:-----------|:-------------|:--------|
| `i18n_scopes`                  | `PdoI18nScopesQueryReader`                                                              | `I18nScopeCreateWriter`         | YES        | NO           | NO      |
| `i18n_domains`                 | `PdoI18nDomainsQueryReader`, `PdoI18nScopeDomainsQueryReader`                           | `PdoI18nDomainCreate`           | YES        | NO           | NO      |
| `i18n_domain_scopes`           | `PdoI18nScopeDomainsQueryReader`, `PdoI18nScopeCoverageReader`                          | `PdoI18nScopeDomainsWriter`     | YES        | NO           | NO      |
| `i18n_keys`                    | `PdoI18nScopeDomainKeysSummaryQueryReader`, `PdoI18nScopeDomainTranslationsQueryReader` | `MysqlTranslationKeyRepository` | YES        | NO           | NO      |
| `i18n_translations`            | `PdoI18nScopeDomainKeysSummaryQueryReader`, `PdoI18nScopeDomainTranslationsQueryReader` | `MysqlTranslationRepository`    | YES        | NO           | NO      |
| `i18n_domain_language_summary` | `PdoI18nScopeCoverageReader`                                                            | `MissingCounterService`         | YES        | YES          | NO      |
| `i18n_key_stats`               | **NONE**                                                                                | `MissingCounterService`         | **NO**     | YES          | **YES** |

---

## 4️⃣ Flow Graphs

### 1. Scope → Coverage (Language)
```
UI Route: /i18n/scopes/{id}
  → Twig: scope_details.twig
      → JS: i18n-scope-coverage.js
          → API Route: GET /api/i18n/scopes/{id}/coverage
              → Controller: I18nScopeCoverageByLanguageController
                  → Reader: PdoI18nScopeCoverageReader::getScopeCoverageByLanguage
                      → Tables: i18n_domain_language_summary, i18n_domain_scopes, languages
```

### 2. Scope → Coverage → Domain
```
UI Route: /i18n/scopes/{id}/coverage/languages/{language_id}
  → Twig: scope_language_coverage.twig
      → JS: i18n-scope-language-coverage.js
          → API Route: GET /api/i18n/scopes/{id}/coverage/languages/{language_id}
              → Controller: I18nScopeCoverageByDomainController
                  → Reader: PdoI18nScopeCoverageReader::getScopeCoverageByDomain
                      → Tables: i18n_domain_language_summary, i18n_domain_scopes, i18n_domains
```

### 3. Translation Editing (Direct & via Coverage)
```
UI Route: /i18n/scopes/{s}/domains/{d}/translations
  → Twig: scope_domain_translations.twig
      → JS: i18n_scope_domain_translations.js
          → API Route: POST /api/i18n/scopes/{s}/domains/{d}/translations/query
              → Controller: ScopeDomainTranslationsQueryController
                  → Reader: PdoI18nScopeDomainTranslationsQueryReader
                      → Tables: i18n_translations, i18n_keys, languages
```

---

## 5️⃣ Dead / Unused / Broken

### 💀 Dead Code (Unused)
- **Table:** `i18n_key_stats`
    - **Reason:** Written synchronously by `MissingCounterService`, but **never read** by any reader. `PdoI18nScopeDomainKeysSummaryQueryReader` computes aggregation on the fly using `i18n_translations`.

### ⚠️ Optimization Opportunities (Not Broken)
- `PdoI18nScopeDomainKeysSummaryQueryReader` performs expensive `CROSS JOIN languages` and `LEFT JOIN i18n_translations` with `GROUP BY key_id`. It *could* use `i18n_key_stats` for the "total translations" column when no language filter is applied, but currently does not.

---

## 6️⃣ Runtime Risk Matrix

| Risk Category      | Level | Finding                                                                                                         |
|:-------------------|:------|:----------------------------------------------------------------------------------------------------------------|
| **Data Integrity** | Low   | `i18n_domain_language_summary` is strictly used with `i18n_domain_scopes` join, enforcing governance correctly. |
| **Security**       | Low   | All routes protected by `AuthorizationGuardMiddleware`. Scope IDs validated.                                    |
| **UX Break**       | Low   | Pre-selection logic in `i18n_scope_domain_translations.js` correctly handles URL params.                        |
| **JS Integration** | Low   | All JS files match API contracts. `ApiHandler` used consistently.                                               |
| **Dead Storage**   | High  | `i18n_key_stats` is maintained (write overhead) but provides zero read value.                                   |

---

# 🔒 FORENSIC VERDICT

The I18n system is **functionally complete** and **consistent**. The coverage feature is fully wired from DB to UI.

**Major Forensic Finding:**
The `i18n_key_stats` table is **DEAD WEIGHT**. It consumes write IO but serves no read queries.

**Recommendation:**
Merge the current implementation as it is safe and correct. Address the `i18n_key_stats` optimization (consumption or removal) in a separate performance pass.
