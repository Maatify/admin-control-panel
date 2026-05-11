# Maatify Settings Module

A lightweight, reusable settings module for Maatify applications. Manages application runtime configuration with support for different data types, admin editability control, and direct value retrieval by setting key.

## Features

- **Key-based settings** — identify settings by stable string keys
- **Type-safe enums** — `SettingValueType` enum for safe type handling
- **Type support** — bool, int, string, datetime, date (with auto-validation)
- **Admin control** — mark settings as editable or system-only
- **Direct value retrieval** — `SettingValueService` methods return values by key name directly
- **List management** — paginated admin list with search and filtering
- **PDO-based** — pure database access, no ORM

## Installation

```bash
composer require maatify/settings
```

## Database Setup

Import the schema:

```bash
mysql -u user -p database < Modules/Settings/schema.sql
```

## Basic Usage

### In your DI container

```php
use Maatify\Settings\Bootstrap\SettingsBindings;
use DI\ContainerBuilder;

$builder = new ContainerBuilder();
SettingsBindings::register($builder);
$container = $builder->build();
```

### Get settings values

```php
use Maatify\Settings\Shared\Service\SettingValueService;
use Maatify\Settings\Shared\SettingValueType;

$valueService = $container->get(SettingValueService::class);

// Returns string directly
$maintenanceValue = $valueService->getValue('maintenance');

// Type-safe getters
$maintenance = $valueService->getBool('maintenance');
$defaultCurrencyId = $valueService->getInt('default_currency');
$language = $valueService->getString('default_language');

// Get with defaults (no exception)
$ttl = $valueService->getOrDefaultInt('pre_cart_preparation_ttl_days', 15);
```

### Working with Setting Types (Enum)

```php
use Maatify\Settings\Shared\SettingValueType;

// Available types
SettingValueType::BOOL      // 'bool'
SettingValueType::INT       // 'int'
SettingValueType::STRING    // 'string'
SettingValueType::DATE      // 'date'
SettingValueType::DATETIME  // 'datetime'

// Get from value
$type = SettingValueType::fromValue('bool');

// Get label for UI
echo SettingValueType::INT->label();  // "Integer"
```

### Admin operations

```php
use Maatify\Settings\Admin\Setting\Service\AdminSettingService;
use Maatify\Settings\Admin\Setting\Command\UpdateSettingValueCommand;

$adminService = $container->get(AdminSettingService::class);

// Get single setting
$setting = $adminService->getByKey('maintenance');
echo $setting->settingValue;     // "0" or "1"
echo $setting->valueType;        // "bool"
echo $setting->isAdminEditable;  // true/false

// Update (only if is_admin_editable = true)
// Type validation is automatic based on SettingValueType
$command = new UpdateSettingValueCommand('default_currency', '2');
$adminService->updateValue($command);  // ✓ validates as int

// Invalid type throws exception
$command = new UpdateSettingValueCommand('default_currency', 'abc');
$adminService->updateValue($command);  // ✗ SettingsInvalidArgumentException

// List all settings with pagination
$result = $adminService->list(
    page: 1,
    perPage: 20,
    globalSearch: 'maintenance',
    columnFilters: ['is_admin_editable' => 1]
);

// Get all as key => value
$allSettings = $adminService->listAsKeyValue();
// ['maintenance' => '0', 'default_currency' => '1', ...]
```

### Custom Types Example (Email Validator)

```php
// Create custom provider with email support
$provider = new class implements SettingValueTypeProviderInterface {
    public function all(): array {
        return ['bool', 'int', 'string', 'email'];
    }
    
    public function label(string $type): string {
        return match($type) {
            'email' => 'Email Address',
            default => 'Unknown'
        };
    }
    
    public function validate(string $value, string $type): void {
        if ($type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw SettingsInvalidArgumentException::invalidValueForType($value, 'Email Address');
        }
    }
    
    public function isValid(string $type): bool {
        return in_array($type, $this->all(), true);
    }
};

// Register in container
$builder->addDefinitions([
    SettingValueTypeProviderInterface::class => $provider,
]);

// Now validation is automatic
$command = new UpdateSettingValueCommand('admin_email', 'invalid-email');
$adminService->updateValue($command);
// ✗ SettingsInvalidArgumentException: Invalid value [invalid-email] for type [Email Address]
```

## Default Settings

The schema includes these default settings:

| Key | Value | Type | Editable | Purpose |
|-----|-------|------|----------|---------|
| `maintenance` | `0` | bool | Yes | Toggle maintenance mode |
| `default_currency` | `1` | int | Yes | Default currency ID |
| `default_language` | `1` | int | Yes | Default language ID |
| `pre_cart_preparation_ttl_days` | `15` | int | Yes | Cart draft cleanup threshold |

## Custom Types & Validation

The module supports custom setting types via **`SettingValueTypeProviderInterface`**. Projects can extend validation without modifying the module:

```php
use Maatify\Settings\Shared\Contract\SettingValueTypeProviderInterface;
use DI\ContainerBuilder;

// Create your custom provider
$customProvider = new App\Settings\CustomSettingValueTypeProvider();

// Register in DI
$builder = new ContainerBuilder();
$builder->addDefinitions([
    SettingValueTypeProviderInterface::class => $customProvider,
]);
```

Built-in types: `bool`, `int`, `string`, `date`, `datetime`

Custom type examples: `email`, `url`, `json`, `phone`, `currency`

See **`CUSTOM_TYPES.md`** for complete implementation guide.

---

## Design Notes

### Immutable vs Mutable Fields

When `is_admin_editable = 0`, the setting cannot be changed through `AdminSettingService`:

- ❌ Cannot change: `setting_value`
- ❌ Cannot change: `setting_key`, `value_type`, `is_admin_editable`, `admin_note`

This protects system settings from admin-side runtime changes that could break application behavior.

### Value Types

The `value_type` field guides type casting in the application:

- **bool** — stored as "0" or "1", cast with `getBool()`
- **int** — stored as numeric string, cast with `getInt()`
- **string** — stored as-is, use `getString()`
- **datetime** — stored as "YYYY-MM-DD HH:MM:SS"
- **date** — stored as "YYYY-MM-DD"

### Two-tier Access

- **Admin** — `AdminSettingService` for full read/write control
- **Value only** — `SettingValueService` for application runtime access

This separation ensures the app tier can only read, never modify settings.

## Exceptions

- `SettingsNotFoundException` — setting key not found
- `SettingsInvalidArgumentException` — invalid input or attempt to edit non-editable setting

See `SETTINGS_MODULE_REFERENCE.md` for detailed API documentation.
