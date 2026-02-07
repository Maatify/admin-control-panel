# 04. Language Lifecycle

This chapter covers the complete lifecycle of managing languages using the `LanguageManagementService`.

## 1. Creating a Language

Creating a language involves establishing both its immutable identity and its mutable settings.

```php
use Maatify\I18n\Enum\TextDirectionEnum;

// Create "English (US)"
$langId = $service->createLanguage(
    name: 'English (US)',        // Display Name
    code: 'en-US',              // Canonical Code (BCP 47)
    direction: TextDirectionEnum::LTR,
    icon: 'flags/us.png',       // Optional Icon Path
    isActive: true,             // Can be used immediately?
    fallbackLanguageId: null    // Base language has no fallback
);
```

**Constraints:**
*   `code` must be unique.
*   `name` cannot be empty.
*   `direction` must be a valid `TextDirectionEnum` case (`LTR` or `RTL`).

## 2. Managing Settings

Once created, you can update a language's UI properties without affecting its identity.

```php
// Update Direction & Icon
$service->updateLanguageSettings(
    languageId: $langId,
    direction: TextDirectionEnum::LTR,
    icon: 'flags/new-us.png'
);

// Update Sort Order
// Moves this language to position 1, shifting others down.
$service->updateLanguageSortOrder($langId, 1);
```

## 3. Activation & Deactivation

Languages can be enabled or disabled globally.

```php
// Disable (e.g., maintenance or incomplete translation)
$service->setLanguageActive($langId, false);

// Re-enable
$service->setLanguageActive($langId, true);
```

**Impact:**
*   Inactive languages are excluded from `LanguageRepository::listActive()`.
*   Runtime translation lookups for inactive languages will return `null` (unless the specific `TranslationReadService` implementation chooses to bypass this check for debugging).

## 4. Fallback Chains

The library supports a single-level fallback mechanism. This is useful for regional variants (e.g., `en-GB` falling back to `en-US`).

### Setting a Fallback

```php
// 1. Create Base Language
$usId = $service->createLanguage('English (US)', 'en-US', ...);

// 2. Create Variant
$gbId = $service->createLanguage('English (UK)', 'en-GB', ...);

// 3. Link Them
$service->setFallbackLanguage($gbId, $usId);
```

### How Fallback Works
When resolving a translation key:
1.  System checks the primary language (`en-GB`).
2.  If the key exists but the *translation value* is missing, it checks the fallback language (`en-US`).
3.  If found in fallback, that value is returned.

**Rules:**
*   **No Circular References:** `A -> B -> A` is strictly forbidden. The service checks `id !== fallbackId`.
*   **One Level Deep:** The current read implementation (`TranslationReadService`) supports one level of fallback. Multi-level chains (A -> B -> C) are technically possible in the database but resolution behavior depends on the service implementation (standard `TranslationReadService` does `Language -> Fallback` only).

### Removing a Fallback

```php
$service->clearFallbackLanguage($gbId);
```
