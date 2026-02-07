# 07. Runtime Reads

This chapter explains how to fetch translations efficiently in your application.

## 1. Fail-Soft Philosophy

The read services (`TranslationReadService` and `TranslationDomainReadService`) are designed to be **fail-soft**.

*   **Exceptions:**
    *   They rarely throw exceptions.
    *   `LanguageNotFoundException` is the only exception you might encounter if you request a language code that doesn't exist (e.g., `xx-YY`).
*   **Missing Data:**
    *   If a key is missing -> returns `null`.
    *   If a translation is missing -> returns `null` (after trying fallback).
    *   If a domain is empty -> returns empty DTO.

**Why?**
A missing translation should result in a blank string or a fallback in the UI, not a fatal error or a 500 page.

## 2. Single Value Read (`TranslationReadService`)

Use this service to fetch a specific translation string. It handles fallback logic automatically.

```php
$value = $readService->getValue(
    languageCode: 'en-US',
    scope: 'client',
    domain: 'auth',
    key: 'login.title'
);

// Returns "Log In" OR null
if ($value === null) {
    // Key doesn't exist or translation missing
}
```

**Performance Note:** This performs a query for every call. For bulk usage (e.g., rendering a full page), use the Domain Read service instead.

## 3. Bulk Domain Read (`TranslationDomainReadService`)

Use this service to fetch ALL translations for a specific `Scope` + `Domain`. This is highly optimized (1 query) and ideal for passing to frontend frameworks (React, Vue) or template engines.

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
*   Includes fallback values if the primary language is missing a key.
*   Returns an empty array `[]` if the domain has no keys or is invalid.

## 4. Caching Strategy

The library itself does **not** implement caching. It hits the database directly.

**Recommendation:**
Wrap the `TranslationDomainReadService` in a caching layer (Redis/Memcached). Cache by:
`i18n:domain:{scope}:{domain}:{lang_code}`

Invalidate this cache whenever `TranslationWriteService` performs an upsert or delete.
