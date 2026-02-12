# 04. Language Lifecycle

This chapter documents the lifecycle of languages managed by `LanguageManagementService`.

## 1. Creating a Language

Language creation establishes an immutable identity and mutable settings.

```php
use Maatify\LanguageCore\Enum\TextDirectionEnum;

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

**Validation Rules:**
*   `code` must be unique (case-insensitive).
*   `name` cannot be empty.
*   `direction` must be a valid `TextDirectionEnum` case (`LTR` or `RTL`).
*   `fallbackLanguageId` must point to an existing language ID or be `null`.

**Exceptions:**
*   `LanguageAlreadyExistsException`
*   `LanguageCreateFailedException`
*   `LanguageNotFoundException` (if fallback ID is invalid)

## 2. Managing Settings

Once created, settings can be updated without affecting identity.

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

**Exceptions:**
*   `LanguageNotFoundException`
*   `LanguageUpdateFailedException`

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
*   Runtime translation lookups for inactive languages return `null` (unless explicitly bypassed).

## 4. Fallback Chains

The library supports a single-level fallback mechanism.

### Setting a Fallback

```php
// 1. Create Base Language
$usId = $service->createLanguage('English (US)', 'en-US', ...);

// 2. Create Variant
$gbId = $service->createLanguage('English (UK)', 'en-GB', ...);

// 3. Link Them
$service->setFallbackLanguage($gbId, $usId);
```

### Fallback Resolution
When resolving a translation key:
1.  The system checks the primary language (`en-GB`).
2.  If the key exists but the *translation value* is missing, it checks the fallback language (`en-US`).
3.  If found in fallback, that value is returned.

**Rules:**
*   **No Circular References:** `A -> B -> A` throws `LanguageInvalidFallbackException`.
*   **One Level Deep:** The `TranslationReadService` implementation supports strictly one level of fallback.

### Removing a Fallback

```php
$service->clearFallbackLanguage($gbId);
```
