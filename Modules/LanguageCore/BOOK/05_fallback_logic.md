# 05. Fallback Logic

This chapter explains the single-level fallback mechanism provided by `LanguageCore`.

## 1. The Concept

Fallback logic ensures that if content is missing for a specific regional variant (e.g., `en-GB`), the system can gracefully degrade to a base language (e.g., `en-US`).

### Implementation
The `languages` table contains a self-referencing Foreign Key `fallback_language_id` pointing to another language ID.

## 2. Configuration

### Setting a Fallback

```php
// 1. Create Base Language
$usId = $service->createLanguage('English (US)', 'en-US', ...);

// 2. Create Variant
$gbId = $service->createLanguage('English (UK)', 'en-GB', ...);

// 3. Link Them
$service->setFallbackLanguage($gbId, $usId);
```

### Removing a Fallback

```php
$service->clearFallbackLanguage($gbId);
```

## 3. Rules & Constraints

### Single Level Support
While the database structure technically allows infinite chains (`A -> B -> C`), the `LanguageCore` design and typical consumer implementation (like `I18n`) are optimized for **Single Level Fallback**.

*   **Supported:** `Region -> Base` (e.g., `fr-CA` -> `fr-FR`).
*   **Discouraged:** `Region -> Base -> Default` (e.g., `fr-CA` -> `fr-FR` -> `en-US`).

### Circular Reference Prevention
The service logic explicitly prevents circular references (e.g., `A -> B -> A`) which would cause infinite loops during resolution.
*   **Exception:** `LanguageInvalidFallbackException` is thrown if a loop is detected.
