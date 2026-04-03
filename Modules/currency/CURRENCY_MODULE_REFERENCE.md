# Maatify Currency Module — Developer Reference Map

> **Namespace:** `Maatify\Currency`  
> **Composer:** `maatify/currency`  
> **PHP:** 8.2+  
> **Dependencies:** `maatify/exceptions`, `psr/container`

---

## Setup

```bash
# 1. Database — run in order (language tables first)
mysql -u root -p db_name < schema.sql

# 2. DI Container (Slim 4 / PHP-DI)
$builder->addDefinitions(CurrenciesBindings::definitions());

# Prerequisite: PDO::class must already be registered in the container
# with PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
```

---

## Module Structure

```
src/
├── Bootstrap/     CurrenciesBindings          ← DI wiring only
├── Command/       5 command objects            ← immutable data carriers
├── Contract/      2 interfaces                 ← the only types callers depend on
├── DTO/           CurrencyDTO, CurrencyTranslationDTO
├── Exception/     5 exceptions + interface     ← all extend maatify/exceptions
├── Infrastructure/Repository/  2 PDO repos    ← SQL only, no business logic
└── Service/       CurrencyQueryService, CurrencyCommandService
```

---

## ⚠️ Rule: always use Services, not Repos directly

Controllers must depend on the two **Services**. The repositories are infrastructure details.

---

## CurrencyQueryService — Read Side

### Currency List (Admin)

```php
paginate(
    int     $page          = 1,
    int     $perPage       = 20,   // capped at 200
    ?string $globalSearch  = null, // searches: code, name, symbol
    array   $columnFilters = [],   // keys: 'is_active' (bool), 'code' (string)
    ?int    $languageId    = null, // null = no translation JOIN
): array{
    data:       list<CurrencyDTO>,
    pagination: array{ page: int, per_page: int, total: int, filtered: int }
}
```

### Currency List (Website)

```php
activeList(?int $languageId = null): list<CurrencyDTO>
// Returns active currencies only, ordered by display_order ASC
// Pass $languageId to get COALESCE-translated names
```

### Single-Record Lookups

```php
getById(int $id, ?int $languageId = null): CurrencyDTO   // throws CurrencyNotFoundException
getByCode(string $code, ?int $languageId = null): CurrencyDTO  // throws CurrencyNotFoundException
```

### Translation Management (Admin)

```php
// Simple list — all active languages, LEFT JOIN translations
// Returns languages with no translation too (translatedName = null)
listTranslations(int $currencyId): list<CurrencyTranslationDTO>

// Paginated + filterable version of the above
listTranslationsPaginated(
    int     $currencyId,
    int     $page          = 1,
    int     $perPage       = 20,
    ?string $globalSearch  = null,   // searches: language name, code, translated name
    array   $columnFilters = [],     // keys below
): array{
    data:       list<CurrencyTranslationDTO>,
    pagination: array{ page: int, per_page: int, total: int, filtered: int }
}

// columnFilters keys for listTranslationsPaginated:
//   'language_id'      (int)    — exact match
//   'language_code'    (string) — LIKE
//   'language_name'    (string) — LIKE
//   'name'             (string) — LIKE on translated name
//   'has_translation'  ('1'|'0') — filter translated / untranslated

// Check a specific (currency, language) pair
// Returns null if no translation exists yet (use to detect vs COALESCE fallback)
findTranslation(int $currencyId, int $languageId): ?CurrencyTranslationDTO
```

---

## CurrencyCommandService — Write Side

### Create

```php
create(CreateCurrencyCommand $command): CurrencyDTO
// throws CurrencyCodeAlreadyExistsException

// CreateCurrencyCommand:
//   code:         string  — ISO 4217 (normalised to UPPERCASE)
//   name:         string  — base name (English / default locale)
//   symbol:       string  — ISO glyph ($, €, …)
//   isActive:     bool    — default: true
//   displayOrder: int     — 0 = auto-append (atomic MAX+1 in SQL, no race)
```

### Update

```php
update(UpdateCurrencyCommand $command): CurrencyDTO
// throws CurrencyNotFoundException, CurrencyCodeAlreadyExistsException
// If displayOrder changed → surrounding rows re-sorted atomically (FOR UPDATE + transaction)

// UpdateCurrencyCommand:
//   id, code, name, symbol, isActive, displayOrder
```

### Update Status

```php
updateStatus(UpdateCurrencyStatusCommand $command): CurrencyDTO
// throws CurrencyNotFoundException
// Safe to call even when is_active is already the same value (no rowCount false-positive)

// UpdateCurrencyStatusCommand:
//   id: int, isActive: bool
```

### Reorder

```php
reorder(int $id, int $newOrder): void
// throws CurrencyNotFoundException, CurrencyInvalidArgumentException (if newOrder < 1)
// Gap-free algorithm: shifts only the rows between oldOrder and newOrder
// Moving DOWN (newOrder > oldOrder): rows in (old, new] slide UP   by 1
// Moving UP   (newOrder < oldOrder): rows in [new, old) slide DOWN by 1
```

### Translation Upsert

```php
upsertTranslation(UpsertCurrencyTranslationCommand $command): CurrencyTranslationDTO
// throws CurrencyNotFoundException
// INSERT … ON DUPLICATE KEY UPDATE — safe to call whether row exists or not

// UpsertCurrencyTranslationCommand:
//   currencyId: int, languageId: int, translatedName: string
```

### Translation Delete

```php
deleteTranslation(DeleteCurrencyTranslationCommand $command): void
// throws CurrencyNotFoundException
// Silent no-op if the translation row does not exist
// After deletion → queries automatically return the COALESCE base-name fallback

// DeleteCurrencyTranslationCommand:
//   currencyId: int, languageId: int
```

---

## DTOs

### CurrencyDTO

```php
$dto->id              // int
$dto->code            // string  — ISO 4217 (always UPPERCASE)
$dto->name            // string  — base name, always present
$dto->symbol          // string  — ISO glyph, never translated
$dto->isActive        // bool
$dto->displayOrder    // int
$dto->createdAt       // string
$dto->updatedAt       // string|null
$dto->translatedName  // string|null — null when no $languageId was passed
$dto->languageId      // int|null    — the language that was queried

$dto->displayName()   // string — translatedName ?? name (best available name)
$dto->toArray()       // array{id, code, name, symbol, is_active, display_order,
                      //        created_at, updated_at, translated_name, language_id}
```

### CurrencyTranslationDTO

```php
$dto->id              // int|null    — null = no translation row yet
$dto->languageId      // int
$dto->languageCode    // string      — e.g. "ar", "ar-EG"
$dto->languageName    // string      — e.g. "Arabic"
$dto->translatedName  // string|null — null = no translation row yet
$dto->createdAt       // string|null — null = no translation row yet
$dto->updatedAt       // string|null

$dto->hasTranslation() // bool — ($translatedName !== null)
$dto->toArray()        // array{id, language_id, language_code, language_name,
                       //        translated_name, has_translation, created_at, updated_at}
```

---

## Exceptions

All exceptions implement `CurrencyExceptionInterface` and extend `maatify/exceptions` families.

| Exception | Family | HTTP | When thrown |
|---|---|---|---|
| `CurrencyNotFoundException` | NotFound | 404 | Currency id / code not found |
| `CurrencyTranslationNotFoundException` | NotFound | 404 | Translation row not found after upsert (infrastructure guard) |
| `CurrencyCodeAlreadyExistsException` | Conflict | 409 | Duplicate ISO 4217 code on create or update |
| `CurrencyInvalidArgumentException` | Validation | 400 | `newOrder < 1` · invalid `languageId` (FK not found) · bad DTO field type |
| `CurrencyPersistenceException` | System | 500 | PDO prepare failure · unexpected column type · DI container mismatch |

```php
// Catch any currency exception
catch (CurrencyExceptionInterface $e) { ... }

// Catch a specific one
catch (CurrencyNotFoundException $e) { ... }
```

---

## Translation Listing Behaviour

```
listTranslations($currencyId)
└── LEFT JOIN languages ON (language_id = l.id AND currency_id = ?)
    WHERE l.is_active = 1
    ORDER BY l.id ASC

Result for a DB with 3 languages [ar, en, fr], currency has only ar translated:

  [
    { language_code: "ar", translatedName: "دولار أمريكي", has_translation: true  }
    { language_code: "en", translatedName: null,           has_translation: false }
    { language_code: "fr", translatedName: null,           has_translation: false }
  ]
```

---

## Schema

Tables created in this order:

```sql
languages            -- kernel-grade identity (BCP 47 codes, fallback chain)
language_settings    -- UI only (direction, icon, sort_order)
currencies           -- ISO 4217 code, symbol, display_order
currency_translations -- one row per (currency_id, language_id) pair
```

Key constraints:

```sql
currencies.code              UNIQUE
currency_translations.(currency_id, language_id)  UNIQUE
fk_ct_currency  ON DELETE CASCADE
fk_ct_language  ON DELETE CASCADE
languages.fallback_language_id → languages(id) ON DELETE SET NULL
```
