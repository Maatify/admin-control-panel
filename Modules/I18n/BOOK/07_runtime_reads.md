# 07. Runtime Reads

This chapter details the fail-soft behavior of runtime translation reads.

## 1. Fail-Soft Philosophy

All read services (`TranslationReadService`, `TranslationDomainReadService`) implement a strict **fail-soft** strategy.

*   **Exceptions:**
    *   `LanguageNotFoundException` is the **only** exception thrown during reads (e.g., if an invalid language code is provided).
*   **Missing Data:**
    *   Missing Key → Returns `null`.
    *   Missing Translation → Returns `null` (after attempting fallback).
    *   Invalid Domain → Returns empty DTO.

**Rationale:**
A missing translation must not cause a fatal application error.

## 2. Single Value Read (`TranslationReadService`)

Fetches a specific translation string, resolving fallback logic automatically.

```php
$value = $readService->getValue(
    languageCode: 'en-US',
    scope: 'client',
    domain: 'auth',
    key: 'login.title'
);

// Returns "Log In" OR null
if ($value === null) {
    // Key or translation missing
}
```

**Performance:** Execute one query per call. Not recommended for bulk operations.

## 3. Bulk Domain Read (`TranslationDomainReadService`)

Fetches all translations for a specific `Scope` + `Domain`. Optimized for high performance (single query).

```php
$dto = $domainReadService->getDomainValues(
    languageCode: 'en-US',
    scope: 'client',
    domain: 'auth'
);

// $dto is strictly typed: TranslationDomainValuesDTO
$translations = $dto->translations;

// Result: ['login.title' => 'Log In', 'register.btn' => 'Sign Up']
```

**Behavior:**
*   Returns strictly typed `TranslationDomainValuesDTO`.
*   Includes fallback values if primary language key is missing.
*   Returns empty array `[]` if domain has no keys or is invalid.

## 4. Caching Strategy

The library implementation does **not** cache data. It queries the database directly.

**Integration Requirement:**
You **must** wrap `TranslationDomainReadService` in a caching layer (e.g., Redis).
*   **Key Pattern:** `i18n:domain:{scope}:{domain}:{lang_code}`
*   **Invalidation:** Must occur on `TranslationWriteService` upsert/delete.
