# Derived Layer Consumption Audit (i18n_domain_language_summary + i18n_key_stats)

## 1. SUMMARY CONSUMPTION MAP

| Reader | Current Logic | Replaceable with Summary? | Replacement Strategy | Risk Level |
| :--- | :--- | :--- | :--- | :--- |
| **PdoI18nScopesQueryReader** | Selects scope columns (`id`, `code`, `name`, `description`, `is_active`, `sort_order`) from `i18n_scopes`. | **No (Granularity Mismatch)** | The summary table aggregates by `(scope, domain, language_id)`. To use it here, we would need to sum `total_keys` across all domains in a scope for a specific language. The UI currently requests no stats. Adding stats would be an enhancement, not a replacement. | Low (Enhancement Only) |
| **PdoI18nDomainsQueryReader** | Selects domain columns (`id`, `code`, `name`, `description`, `is_active`, `sort_order`) from `i18n_domains`. | **No (Granularity Mismatch)** | Similar to scopes. Summary is by `(scope, domain, language_id)`. Domains can belong to multiple scopes. Aggregating `total_keys` requires summing across scopes/languages. UI requests no stats. | Low (Enhancement Only) |
| **PdoI18nScopeDomainsQueryReader** | Selects domain columns + `assigned` status via `LEFT JOIN i18n_domain_scopes`. | **No (Granularity Mismatch)** | Summary is by `language_id`. UI requests no stats. | Low (Enhancement Only) |
| **PdoI18nScopeDomainKeysSummaryQueryReader** | Computes `missing_count` via `CROSS JOIN languages` and `LEFT JOIN i18n_translations` with `GROUP BY key_id`. | **PARTIAL (Specific Filters Only)** | See Section 2. | Medium (Conditional Logic) |
| **PdoI18nScopeDomainTranslationsQueryReader** | Selects translation values. | **No (Values Needed)** | Summary stores counts, not values. | N/A |

## 2. KEY_STATS CONSUMPTION MAP

| Reader | Current Logic | Replaceable with Key Stats? | Replacement Strategy | Risk Level |
| :--- | :--- | :--- | :--- | :--- |
| **PdoI18nScopeDomainKeysSummaryQueryReader** | `translated_count` comes from `COUNT(t.id)` grouped by key. | **YES (Conditional)** | If no language filters are applied (`$langWhere` is empty), `translated_count` = `i18n_key_stats.translated_count`. | Low |

### Strategy Detail for `PdoI18nScopeDomainKeysSummaryQueryReader`

-   **Condition:** `$langWhere` is empty.
    -   This means we are not filtering by `language_id` or `is_active`.
    -   We want "Total Translations" for the key across ALL languages.
-   **Optimization:**
    -   Instead of `LEFT JOIN (SELECT key_id, COUNT(*) ... GROUP BY key_id) ta`,
    -   Use `LEFT JOIN i18n_key_stats eks ON eks.key_id = k.id`.
    -   Select `COALESCE(eks.translated_count, 0)`.
-   **Benefit:** Avoids scanning `i18n_translations` table for every key in the scope/domain.
-   **Fallback:** If `$langWhere` is NOT empty (e.g., `is_active=1`), we MUST fallback to the raw aggregation because `i18n_key_stats` does not support filtering.

## 3. REQUIRED MODIFICATIONS

### File: `app/Modules/AdminKernel/Infrastructure/Repository/I18n/Translations/PdoI18nScopeDomainKeysSummaryQueryReader.php`

**Class:** `PdoI18nScopeDomainKeysSummaryQueryReader`

**Method:** `query(...)`

**Changes:**

1.  **Detect Language Filters:**
    ```php
    $hasLanguageFilters = !empty($langWhere);
    ```

2.  **Conditional Join Construction:**
    ```php
    if (!$hasLanguageFilters) {
        // OPTIMIZED PATH: Use i18n_key_stats
        $baseSelect = "
            SELECT
                k.id,
                k.key_part,
                k.description,
                al.total_languages AS total_languages,
                (al.total_languages - COALESCE(eks.translated_count, 0)) AS missing_count
            FROM i18n_keys k
            CROSS JOIN (
                SELECT COUNT(*) AS total_languages
                FROM languages l
                {$langWhereSql} -- This is empty in this branch
            ) al
            LEFT JOIN i18n_key_stats eks
                ON eks.key_id = k.id
            {$whereSql}
        ";
    } else {
        // RAW PATH: Use existing subquery logic
        // ... (keep existing code)
    }
    ```

**Note:** The `total_languages` subquery remains necessary because `i18n_key_stats` does not store the denominator.

## 4. DO NOT MODIFY ZONES

-   **Scopes / Domains List Readers:** Do not inject stats unless explicitly requested by product requirements.
-   **Translations Reader:** Must fetch values.

## 5. FINAL VERDICT

-   **Safe to integrate summary?**
    -   **i18n_domain_language_summary:** **NO**. No current reader matches its granularity (Domain+Language). It remains a backend-only optimization waiting for a "Domain Dashboard" feature.
    -   **i18n_key_stats:** **YES**. Can replace the heavy aggregation in `PdoI18nScopeDomainKeysSummaryQueryReader` when listing keys without language filters.

-   **Architectural Side Effects:**
    -   **Positive:** Significant read performance improvement for the default "All Keys" view in the UI.
    -   **Negative:** Adds a conditional branch to the SQL generation logic in the Reader. This is acceptable complexity for the performance gain.
