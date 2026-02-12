# How To Use: Maatify/I18n

[![Maatify I18N](https://img.shields.io/badge/Maatify-I18n-blue?style=for-the-badge)](README.md)
[![Maatify Ecosystem](https://img.shields.io/badge/Maatify-Ecosystem-9C27B0?style=for-the-badge)](https://github.com/Maatify)

This guide provides practical integration examples for the `Maatify/I18n` library.

**Note:** For Language creation and settings (Flags, Direction), refer to the [Maatify/LanguageCore Documentation](../LanguageCore/README.md).

---

## 1. Setup & Wiring

The library requires `PDO` for database access. You **must** instantiate all repositories and inject them into the services.

```php
<?php

use Maatify\I18n\Enum\I18nPolicyModeEnum;
use Maatify\I18n\Infrastructure\Mysql\DomainRepository;
use Maatify\I18n\Infrastructure\Mysql\DomainScopeRepository;
use Maatify\I18n\Infrastructure\Mysql\ScopeRepository;
use Maatify\I18n\Infrastructure\Mysql\TranslationKeyRepository;
use Maatify\I18n\Infrastructure\Mysql\TranslationRepository;
use Maatify\I18n\Service\I18nGovernancePolicyService;
use Maatify\I18n\Service\TranslationDomainReadService;
use Maatify\I18n\Service\TranslationReadService;
use Maatify\I18n\Service\TranslationWriteService;
use Maatify\LanguageCore\Infrastructure\Mysql\LanguageRepository;
use Maatify\LanguageCore\Infrastructure\Mysql\LanguageSettingsRepository;
use Maatify\LanguageCore\Service\LanguageManagementService;

// 1. Database Connection
$pdo = new PDO('mysql:host=localhost;dbname=test', 'user', 'pass');

// 2. Repositories (I18n & LanguageCore)
$langRepo       = new LanguageRepository($pdo);
$settingsRepo   = new LanguageSettingsRepository($pdo); // Required by LanguageCore
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

// Language Management (From maatify/language-core)
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

## 2. Governance & Policy

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

---

## 3. Translation Keys Lifecycle

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

## 4. Translations Lifecycle

Manage the text values for keys.

### Upsert (Insert or Update)
```php
// Assuming $langId and $arId come from LanguageCore lookups

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

## 5. Runtime Reads (Fail-Soft)

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
```

> **Exception:** `getDomainValues` **throws** `LanguageNotFoundException` if the requested language code is invalid.

---

## 6. Error Handling

### Write Exceptions (Fail-Hard)
Write operations enforce data integrity and throw typed exceptions.

| Exception Class                        | Reason                                 |
|:---------------------------------------|:---------------------------------------|
| `TranslationKeyNotFoundException`      | Key ID does not exist.                 |
| `TranslationKeyAlreadyExistsException` | Key `scope.domain.key` already exists. |
| `ScopeNotAllowedException`             | Scope invalid or inactive.             |
| `DomainNotAllowedException`            | Domain invalid or inactive.            |
| `DomainScopeViolationException`        | Domain not allowed for this Scope.     |

### Read Behavior (Fail-Soft)
*   **Missing Key:** Returns `null`.
*   **Missing Translation:** Returns `null` (after trying fallback).
*   **Invalid Scope/Domain:** Returns empty `TranslationDomainValuesDTO`.

---

## 7. Troubleshooting

### "Why is my translation returning null?"
1.  **Check Language:** Is the language code correct and active? (See LanguageCore)
2.  **Check Key:** Does the key `scope` + `domain` + `key_part` exist exactly?
3.  **Check Translation:** Is there a row in `i18n_translations`?
4.  **Check Fallback:** If the translation is missing, does the language have a `fallback_language_id`? (See LanguageCore)

### "Why can't I create a key?"
1.  **Check Governance:** Ensure the `scope` and `domain` are defined in `i18n_scopes` and `i18n_domains`.
2.  **Check Mapping:** Ensure `i18n_domain_scopes` links the domain to the scope.
3.  **Active Status:** Ensure both scope and domain are `is_active = 1`.

### "Why are my changes not appearing?"
*   This library does **not** implement caching internally. If you utilize a caching layer (Redis/Memcached), you **must** invalidate it after write operations.
