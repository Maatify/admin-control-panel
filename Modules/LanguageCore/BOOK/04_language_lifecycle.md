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
*   Inactive languages are excluded from `LanguageManagementService::listActive()`.
*   Modules consuming this service (like `I18n`) generally respect this flag.

## 4. Deletion

The system generally discourages hard deletion of languages to preserve referential integrity. However, if supported by the implementation, it would cascade delete settings but might be blocked by foreign keys in other modules (like `i18n_translations`).
