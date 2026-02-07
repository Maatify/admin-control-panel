# How To Use: Modules/I18n

This guide provides practical integration examples for the `Modules/I18n` library. It covers mandatory setup steps, strict governance enforcement, and the definitive lifecycle for keys and translations.

---

## 1. Setup & Wiring

The library requires `PDO` for database access. You **must** instantiate all repositories and inject them into the services.

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

// Governance (STRICT mode is mandatory for production)
$governanceService = new I18nGovernancePolicyService(
    $scopeRepo,
    $domainRepo,
    $domainScopeRepo,
    I18nPolicyModeEnum::STRICT
);

// Language Management
$langService = new LanguageManagementService($langRepo, $settingsRepo);

// Write Operations (Keys & Translations) - Fail-Hard
$writeService = new TranslationWriteService(
    $langRepo,
    $keyRepo,
    $transRepo,
    $governanceService
);

// Read Operations (Runtime) - Fail-Soft
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

Use `LanguageManagementService` for all language lifecycle operations.

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

The `I18nGovernancePolicyService` enforces strict structural rules for all write operations.

### Mandatory Rules (STRICT Mode)
1.  **Scope** must exist and be active.
2.  **Domain** must exist and be active.
3.  **Domain** must be explicitly allowed for the **Scope** (via `i18n_domain_scopes` table).

**Violation Consequence:**
The service throws strict exceptions. The operation is aborted.

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

Keys must follow the structured format: `scope.domain.key_part`.

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

Manage the text values for keys.

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

Reading services implement a strictly fail-soft strategy. They return `null` or empty objects for missing data.

### Single Value Read
Fetches a specific translation string.

```php
$value = $readService->getValue('en-US', 'admin', 'dashboard', 'welcome.message');

if ($value === null) {
    // Key doesn't exist OR translation missing (and fallback failed)
    echo "Default Text";
} else {
    echo $value;
}
```

### Bulk Domain Read (Optimized for UI)
Fetches all translations for a specific `scope` + `domain` in one query.

```php
$dto = $domainReadService->getDomainValues('en-US', 'admin', 'dashboard');

// Access as array
$translations = $dto->translations;
// ['welcome.message' => 'Welcome...', 'logout' => 'Log Out']

// Non-existent keys or empty domains return an empty array, NOT an error.
```

> **Exception:** `getDomainValues` **throws** `LanguageNotFoundException` if the requested language code is invalid.

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
*   This library does **not** implement caching internally. If you utilize a caching layer (Redis/Memcached), you **must** invalidate it after write operations.
