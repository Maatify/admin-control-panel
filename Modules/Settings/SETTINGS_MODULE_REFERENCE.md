# Settings Module — API Reference

Complete API documentation, design rules, and extension guidelines for `maatify/settings`.

---

## Overview

The Settings module provides application-level configuration management:

- **Admin tier** — `AdminSettingService` for managing settings
- **Application tier** — `SettingValueService` for reading setting values
- **Immutable structure** — non-editable settings protect system integrity
- **Type support** — bool, int, string, datetime, date

---

## Exception Classes

### `SettingsExceptionInterface`

Marker interface for all module exceptions.

```php
interface SettingsExceptionInterface extends \Throwable {}
```

### `SettingsNotFoundException`

Thrown when a setting key is not found.

```php
SettingsNotFoundException::withKey(string $key): self
```

**Example:**

```php
try {
    $setting = $adminService->getByKey('unknown_key');
} catch (SettingsNotFoundException $e) {
    // "Setting with key [unknown_key] not found."
}
```

### `SettingsInvalidArgumentException`

Thrown for invalid input or business rule violations.

```php
static emptyField(string $field): self
static keyNotEditable(string $key): self
static invalidValueType(string $valueType): self
```

**Example:**

```php
// Attempt to update a non-editable setting
$command = new UpdateSettingValueCommand('system_id', '123');
// SettingsInvalidArgumentException::keyNotEditable('system_id')

// Invalid constructor arguments
new UpdateSettingValueCommand('', ''); // emptyField('settingKey')
```

---

## DTO Classes

### `SettingDTO`

Full setting record with all fields.

```php
final readonly class SettingDTO implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public string $settingKey,
        public string $settingValue,
        public string $valueType,
        public bool $isAdminEditable,
        public ?string $adminNote,
        public string $createdAt,
        public string $updatedAt,
    ) {}
}
```

**Usage:**

```php
$setting = $adminService->getByKey('maintenance');
echo $setting->settingKey;       // "maintenance"
echo $setting->settingValue;     // "0"
echo $setting->valueType;        // "bool"
echo $setting->isAdminEditable;  // true
echo $setting->adminNote;        // "App maintenance mode"
```

### `SettingListItemDTO`

Compact setting record for list operations (excludes `createdAt`).

```php
final readonly class SettingListItemDTO implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public string $settingKey,
        public string $settingValue,
        public string $valueType,
        public bool $isAdminEditable,
        public ?string $adminNote,
        public string $updatedAt,
    ) {}
}
```

### `SettingCollectionDTO`

Iterable collection of `SettingListItemDTO` objects.

```php
/** @implements \IteratorAggregate<int, SettingListItemDTO> */
final readonly class SettingCollectionDTO 
    implements \IteratorAggregate, \JsonSerializable
```

**Usage:**

```php
foreach ($collectionDto as $item) {
    echo $item->settingKey; // "maintenance", "default_currency", ...
}

json_encode($collectionDto); // serializes to array of items
```

---

## Commands

### `UpdateSettingValueCommand`

Encapsulates a setting value update with validation.

```php
final readonly class UpdateSettingValueCommand
{
    public function __construct(
        public string $settingKey,
        public string $settingValue,
    )
}
```

**Validation:**

- `settingKey` must not be empty
- `settingValue` may be empty

**Example:**

```php
$command = new UpdateSettingValueCommand('maintenance', '1');
// Validated in constructor, safe to use

// Fails:
new UpdateSettingValueCommand('', '1');      // emptyField('settingKey')

// Valid:
new UpdateSettingValueCommand('key', '');    // empty string is allowed
```

---

## Repository Interfaces

### Admin Query Repository

```php
interface AdminSettingQueryRepositoryInterface
{
    public function findByKey(string $settingKey): ?SettingDTO;
    
    /**
     * @param array<string, string|int> $columnFilters
     * @return array{data: list<SettingListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
     */
    public function list(
        int $page,
        int $perPage,
        ?string $globalSearch,
        array $columnFilters
    ): array;

    /** @return array<string, string> */
    public function listAsKeyValue(): array;
}
```

**Implementations:**

- `PdoAdminSettingQueryRepository` — PDO-based implementation

### Admin Command Repository

```php
interface AdminSettingCommandRepositoryInterface
{
    public function updateValue(UpdateSettingValueCommand $command): bool;
}
```

**Return semantics:**

- `true` if row was updated
- `false` if setting key not found

**Implementations:**

- `PdoAdminSettingCommandRepository` — PDO-based implementation

---

## Service Classes

### `AdminSettingService`

Admin-tier service for managing settings.

```php
public function getByKey(string $settingKey): SettingDTO
```

Returns the full setting record.

**Throws:**

- `SettingsNotFoundException` if key not found

**Example:**

```php
$setting = $adminService->getByKey('maintenance');
```

---

```php
public function updateValue(UpdateSettingValueCommand $command): void
```

Updates a setting value. Enforces admin editability.

**Throws:**

- `SettingsInvalidArgumentException` if setting is not `is_admin_editable`
- `SettingsNotFoundException` if key not found after validation

**Example:**

```php
$command = new UpdateSettingValueCommand('maintenance', '1');
$adminService->updateValue($command); // OK if is_admin_editable=1

// If is_admin_editable=0:
// SettingsInvalidArgumentException::keyNotEditable('maintenance')
```

---

```php
/**
 * @param array<string, string|int> $columnFilters
 * @return array{data: list<SettingListItemDTO>, pagination: array{page: int, per_page: int, total: int, filtered: int}}
 */
public function list(
    int $page,
    int $perPage,
    ?string $globalSearch,
    array $columnFilters
): array
```

Paginated list with search and filtering.

**Parameters:**

- `$page` — 1-indexed
- `$perPage` — items per page
- `$globalSearch` — optional text search (matches `setting_key` and `admin_note`)
- `$columnFilters` — optional column-level filters:
  - `'id'` (int)
  - `'is_admin_editable'` (0 or 1)
  - `'value_type'` (string)

**Return shape:**

```
[
  'data'       => [SettingListItemDTO, ...],
  'pagination' => [
    'page'     => 1,
    'per_page' => 20,
    'total'    => 4,          // unfiltered total
    'filtered' => 3,          // count after filters applied
  ]
]
```

**Example:**

```php
$result = $adminService->list(
    page: 1,
    perPage: 20,
    globalSearch: 'currency',
    columnFilters: ['is_admin_editable' => 1]
);

// Returns editable settings matching "currency" in key or note
echo count($result['data']);  // 1
echo $result['pagination']['total'];  // 4 (all settings)
echo $result['pagination']['filtered'];  // 1 (after filter)
```

---

```php
/** @return array<string, string> */
public function listAsKeyValue(): array
```

Returns all settings as a simple key => value map.

**Usage:**

```php
$all = $adminService->listAsKeyValue();
// [
//   'maintenance' => '0',
//   'default_currency' => '1',
//   'default_language' => '1',
//   'pre_cart_preparation_ttl_days' => '15'
// ]
```

---

### `SettingValueService`

Application-tier service for reading setting values with type casting.

```php
public function getValue(string $settingKey): string
```

Returns the raw value.

**Throws:**

- `SettingsNotFoundException` if key not found

---

```php
public function getBool(string $settingKey): bool
```

Returns value cast as boolean (0 = false, 1 = true).

**Example:**

```php
$maintenance = $valueService->getBool('maintenance');
if ($maintenance) {
    // App is in maintenance mode
}
```

---

```php
public function getInt(string $settingKey): int
```

Returns value cast as integer.

**Example:**

```php
$currencyId = $valueService->getInt('default_currency');
$ttl = $valueService->getInt('pre_cart_preparation_ttl_days');
```

---

```php
public function getString(string $settingKey): string
```

Returns value as string (no cast).

---

```php
public function getOrDefault(string $settingKey, string $default): string
public function getOrDefaultBool(string $settingKey, bool $default): bool
public function getOrDefaultInt(string $settingKey, int $default): int
```

Returns value or default if not found (no exception).

**Example:**

```php
$ttl = $valueService->getOrDefaultInt('pre_cart_preparation_ttl_days', 30);
// Returns 15 if setting exists, else 30

$mode = $valueService->getOrDefault('app_mode', 'production');
// No exception if key missing
```

---

## Extension Guide

### Adding a New Setting

1. Update schema or insert into settings table:

```sql
INSERT INTO `settings` 
(`setting_key`, `setting_value`, `value_type`, `is_admin_editable`, `admin_note`)
VALUES
('new_feature_enabled', '1', 'bool', 1, 'Enable new feature in production');
```

2. Use in application:

```php
$enabled = $valueService->getBool('new_feature_enabled');
if ($enabled) {
    // Feature code
}
```

### Mark Setting as Non-Editable

For system-critical settings, set `is_admin_editable = 0`:

```sql
UPDATE `settings` 
SET `is_admin_editable` = 0 
WHERE `setting_key` = 'system_id';
```

Now attempting to update it via `AdminSettingService::updateValue()` will throw:

```php
SettingsInvalidArgumentException::keyNotEditable('system_id')
```

### Support New Value Type

1. Add comment to schema describing the new type
2. Add type-safe getter to `SettingValueService`:

```php
public function getDatetime(string $settingKey): \DateTime
{
    $value = $this->getValue($settingKey);
    return new \DateTime($value);
}
```

3. Value type validation is automatically enforced by `AdminSettingService` on updates:
   - `bool`: accepts only "0" or "1"
   - `int`: validates numeric format
   - `date`/`datetime`: validates format with `DateTimeImmutable`
   - `string`: accepts any value

---

## Design Rules

### Immutability of Setting Structure

When `is_admin_editable = 0`, the setting is locked from `AdminSettingService` updates:

| Field | Can change |
|-------|-----------|
| `setting_value` | ❌ No |
| `setting_key` | ❌ No |
| `value_type` | ❌ No |
| `is_admin_editable` | ❌ No |
| `admin_note` | ❌ No |

This ensures that system-critical settings cannot be accidentally modified in ways that break application code. Attempting to update a locked setting throws `SettingsInvalidArgumentException::keyNotEditable()`.

### PDO-Only Persistence

- All SQL in `PdoAdminSetting*Repository` classes
- No ORM, no query builder
- Direct prepared statements with named placeholders
- Named placeholder rule: each placeholder name used only once per statement

### Service-Repository Separation

| Layer | Responsibility |
|-------|-----------------|
| Service | Business logic, validation, orchestration |
| Repository | SQL execution, hydration |

Services never contain SQL. Repositories never contain business logic.

### Exception Propagation

- Repositories return `null`/`false`, never throw for not-found
- Services throw domain exceptions when business rule is violated
- PDO infrastructure errors propagate as-is

### PHPStan Max

All code must pass `phpstan analyze --level max`:

- Explicit type annotations on all array fetches
- No direct casting of `mixed` values
- Generic types on `IteratorAggregate` implementations
- Hydration validates type before casting

---

## Testing

### Unit Test Structure

```php
namespace Maatify\Settings\Tests\Admin\Setting\Service;

use Maatify\Settings\Admin\Setting\Service\AdminSettingService;
use Maatify\Settings\Exception\SettingsNotFoundException;
use Maatify\Settings\Exception\SettingsInvalidArgumentException;
```

### Test Categories

1. **Service validation** — constructor validation in Commands
2. **Repository queries** — SQL correct for pagination, filtering
3. **Service orchestration** — throws correct exceptions
4. **Type casting** — value hydration preserves types
5. **Editability** — non-editable settings reject updates

---

## See Also

- `README.md` — quick start and usage examples
- `schema.sql` — database schema with design notes
- `MODULE_BUILDING_STANDARD.md` — module architecture and patterns
