# Maatify Settings Module

A lightweight, reusable settings module for Maatify applications. Manages application runtime configuration with support for different data types, admin editability control, and direct value retrieval by setting key.

## Features

- **Key-based settings** — identify settings by stable string keys
- **Type support** — bool, int, string, datetime, date
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

### Admin operations

```php
use Maatify\Settings\Admin\Setting\Service\AdminSettingService;
use Maatify\Settings\Admin\Setting\Command\UpdateSettingValueCommand;

$adminService = $container->get(AdminSettingService::class);

// Get single setting
$setting = $adminService->getByKey('maintenance');
echo $setting->settingValue; // "0" or "1"
echo $setting->isAdminEditable; // true/false

// Update (only if is_admin_editable = true)
$command = new UpdateSettingValueCommand('maintenance', '1');
$adminService->updateValue($command); // throws if not editable

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

## Default Settings

The schema includes these default settings:

| Key | Value | Type | Editable | Purpose |
|-----|-------|------|----------|---------|
| `maintenance` | `0` | bool | Yes | Toggle maintenance mode |
| `default_currency` | `1` | int | Yes | Default currency ID |
| `default_language` | `1` | int | Yes | Default language ID |
| `pre_cart_preparation_ttl_days` | `15` | int | Yes | Cart draft cleanup threshold |

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
