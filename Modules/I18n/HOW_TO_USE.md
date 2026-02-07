# How To Use: Modules/I18n

This guide provides practical examples for integrating and using the `Modules/I18n` library. It covers setup, language management, governance, and translation lifecycle.

---

## 1. Setup & Wiring

The library relies on `PDO` for database access. You must instantiate the repositories and inject them into the services.

```php
<?php

use Maatify\I18n\Infrastructure\Mysql\LanguageRepository;
use Maatify\I18n\Infrastructure\Mysql\LanguageSettingsRepository;
use Maatify\I18n\Infrastructure\Mysql\ScopeRepository;
use Maatify\I18n\Infrastructure\Mysql\DomainRepository;
use Maatify\I18n\Infrastructure\Mysql\DomainScopeRepository;
use Maatify\I18n\Infrastructure\Mysql\TranslationKeyRepository;
use Maatify\I18n\Infrastructure\Mysql\TranslationRepository;
use Maatify\I18n\Service\I18nGovernancePolicyService;
use Maatify\I18n\Service\LanguageManagementService;
use Maatify\I18n\Service\TranslationWriteService;
use Maatify\I18n\Service\TranslationReadService;
use Maatify\I18n\Service\TranslationDomainReadService;
use Maatify\I18n\Enum\I18nPolicyModeEnum;

// 1. Database Connection
$pdo = new PDO('mysql:host=localhost;dbname=test', 'user', 'pass');

// 2. Repositories
$langRepo       = new LanguageRepository($pdo);
$settingsRepo   = new LanguageSettingsRepository($pdo);
$scopeRepo      = new ScopeRepository($pdo);
$domainRepo     = new DomainRepository($pdo);
$domainScopeRepo= new DomainScopeRepository($pdo);
$keyRepo        = new TranslationKeyRepository($pdo);
$transRepo      = new TranslationRepository($pdo);

// 3. Services

// Governance (STRICT mode is default)
$governanceService = new I18nGovernancePolicyService(
    $scopeRepo,
    $domainRepo,
    $domainScopeRepo,
    I18nPolicyModeEnum::STRICT
);

// Language Management
$langService = new LanguageManagementService($langRepo, $settingsRepo);

// Write Operations (Keys & Translations)
$writeService = new TranslationWriteService(
    $langRepo,
    $keyRepo,
    $transRepo,
    $governanceService
);

// Read Operations (Runtime)
$readService = new TranslationReadService($langRepo, $keyRepo, $transRepo);
$domainReadService = new TranslationDomainReadService(
    $langRepo,
    $keyRepo,
    $transRepo,
    $governanceService
);
```

---

## 2. Managing Languages

Use `LanguageManagementService` to handle language lifecycle.

### Create a Language
```php
use Maatify\I18n\Enum\TextDirectionEnum;

$langId = $langService->createLanguage(
    name: 'English (US)',
    code: 'en-US',
    direction: TextDirectionEnum::LTR,
    icon: 'flags/us.png',
    isActive: true,
    fallbackLanguageId: null // No fallback for the base language
);
```

### Configure Fallback
Set a regional language to fall back to a base language (e.g., `en-GB` -> `en-US`).

```php
$gbId = $langService->createLanguage(
    name: 'English (UK)',
    code: 'en-GB',
    direction: TextDirectionEnum::LTR,
    icon: 'flags/gb.png'
);

// Set fallback: if a key is missing in en-GB, look in en-US
$langService->setFallbackLanguage($gbId, $langId);
```

### Update Settings
```php
$langService->updateLanguageSettings(
    languageId: $gbId,
    direction: TextDirectionEnum::LTR,
    icon: 'flags/gb-new.png'
);

// Reorder languages (affects UI lists)
$langService->updateLanguageSortOrder($gbId, 1);
```

---

## 3. Governance & Policy

The `I18nGovernancePolicyService` ensures that translation keys adhere to strict structural rules.

### Rules (STRICT Mode)
1.  **Scope** must exist and be active.
2.  **Domain** must exist and be active.
3.  **Domain** must be explicitly allowed for the **Scope** (via `i18n_domain_scopes` table).

If any rule is violated during a write operation (e.g., creating a key), the service throws:
*   `ScopeNotAllowedException`
*   `DomainNotAllowedException`
*   `DomainScopeViolationException`

### Example
```php
// Fails if 'admin' scope or 'billing' domain are invalid/inactive/unlinked
try {
    $writeService->createKey('admin', 'billing', 'invoice.title');
} catch (DomainScopeViolationException $e) {
    // Handle violation
}
```

---

## 4. Translation Keys Lifecycle

Keys are structured as `scope.domain.key_part`.

### Create a Key
```php
$keyId = $writeService->createKey(
    scope: 'admin',
    domain: 'dashboard',
    key: 'welcome.message',
    description: 'Shown on the admin dashboard header'
);
```

### Rename a Key
Renaming a key preserves its ID and existing translations.
```php
$writeService->renameKey(
    keyId: $keyId,
    scope: 'admin',
    domain: 'dashboard',
    key: 'welcome.header' // New key part
);
```

---

## 5. Translations Lifecycle

Manage the actual text values for keys.

### Upsert (Insert or Update)
```php
// Set English value
$writeService->upsertTranslation(
    languageId: $langId, // en-US
    keyId: $keyId,
    value: 'Welcome back, Administrator!'
);

// Set Arabic value
$writeService->upsertTranslation(
    languageId: $arId,
    keyId: $keyId,
    value: 'مرحباً بعودتك، أيها المدير!'
);
```

### Delete
```php
$writeService->deleteTranslation($langId, $keyId);
```

---

## 6. Runtime Reads (Fail-Soft)

Reading services are designed to be safe for runtime use. They avoid throwing exceptions for missing data, returning `null` or empty objects instead.

### Single Value Read
Ideal for sparse usage or specific lookups.

```php
$value = $readService->getValue('en-US', 'admin', 'dashboard', 'welcome.message');

if ($value === null) {
    // Key doesn't exist OR translation missing (and fallback failed)
    echo "Default Text";
} else {
    echo $value;
}
```

### Bulk Domain Read (Recommended for UI)
Fetches all translations for a specific `scope` + `domain`. Efficient (1 query).

```php
$dto = $domainReadService->getDomainValues('en-US', 'admin', 'dashboard');

// Access as array
$translations = $dto->translations;
// ['welcome.message' => 'Welcome...', 'logout' => 'Log Out']

// Non-existent keys or empty domains return an empty array, NOT an error.
```

> **Note:** `getDomainValues` *will* throw `LanguageNotFoundException` if the requested language code is invalid, as this usually indicates a configuration error.

---

## 7. Error Handling

### Write Exceptions (Fail-Hard)
Write operations enforce data integrity and throw typed exceptions.

| Exception Class | Reason |
| :--- | :--- |
| `LanguageNotFoundException` | Language ID does not exist. |
| `LanguageAlreadyExistsException` | Language code already taken. |
| `TranslationKeyNotFoundException` | Key ID does not exist. |
| `TranslationKeyAlreadyExistsException` | Key `scope.domain.key` already exists. |
| `ScopeNotAllowedException` | Scope invalid or inactive. |
| `DomainNotAllowedException` | Domain invalid or inactive. |
| `DomainScopeViolationException` | Domain not allowed for this Scope. |

### Read Behavior (Fail-Soft)
*   **Missing Key:** Returns `null`.
*   **Missing Translation:** Returns `null` (after trying fallback).
*   **Invalid Scope/Domain:** Returns empty `TranslationDomainValuesDTO`.

---

## 8. Troubleshooting

### "Why is my translation returning null?"
1.  **Check Language:** Is the language code correct and active?
2.  **Check Key:** Does the key `scope` + `domain` + `key_part` exist exactly?
3.  **Check Translation:** Is there a row in `i18n_translations`?
4.  **Check Fallback:** If the translation is missing, does the language have a `fallback_language_id`? Is that fallback translated?

### "Why can't I create a key?"
1.  **Check Governance:** Ensure the `scope` and `domain` are defined in `i18n_scopes` and `i18n_domains`.
2.  **Check Mapping:** Ensure `i18n_domain_scopes` links the domain to the scope.
3.  **Active Status:** Ensure both scope and domain are `is_active = 1`.

### "Why are my changes not appearing?"
*   This library does **not** implement caching internally. If you have a caching layer (Redis/Memcached) above this, ensure you invalidate it after write operations.
