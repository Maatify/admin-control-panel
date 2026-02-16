# AppSettings Module

**Namespace:** `Maatify\AppSettings`  
**Type:** Application Configuration Module  
**Scope:** Global / App-wide settings  
**Storage:** Abstracted (Repository Interface)  

---

## 🎯 Purpose

The AppSettings module provides a **centralized, safe, and extensible**
configuration system for application-wide settings.

It is designed to replace hardcoded configuration tables such as:
- `app_social`
- `app_meta`
- `app_links`

with a **grouped key-value store** backed by strict policies.

---

## 🧠 Design Principles

- **Single Source of Truth**  
  All application settings live in one canonical store.

- **No Hard Deletes**  
  Settings are enabled/disabled using `is_active`.

- **Strict Contracts**  
  DTOs, Enums, Interfaces — no ad-hoc arrays.

- **Storage Agnostic**  
  Repository Interface allows database replacement without breaking consumers.

- **Policy-Driven**  
  Whitelist and protection rules prevent configuration chaos.

---

## 🗄️ Database Model (Summary)

Table: `app_settings`

| Column          | Description                                          |
|-----------------|------------------------------------------------------|
| `setting_group` | Logical grouping (social, apps, legal, system, etc.) |
| `setting_key`   | Unique key inside the group                          |
| `setting_value` | Stored value (TEXT)                                  |
| `setting_type`  | Type metadata (string, int, bool, json)              |
| `is_active`     | Soft activation flag (1 = active, 0 = inactive)      |

> ⚠️ Physical DELETE is forbidden.

---

## 🧩 Module Structure

```

Modules/AppSettings/
├── AppSettingsService.php
├── AppSettingsServiceInterface.php
│
├── DTO/
├── Enum/
├── Policy/
├── Repository/
└── Exception/

````

---

## 🚪 Public Entry Point

**`AppSettingsServiceInterface`**  
This is the **ONLY supported entry point** for:
- Admin panels
- Web applications
- Mobile applications
- CLI / jobs

❌ Consumers must NOT call repositories directly.

---

## 📌 Basic Usage

### Get a setting value
```php
$value = $appSettings->get('social', 'facebook');
````

### Check existence

```php
if ($appSettings->has('apps', 'android')) {
    // ...
}
```

### Get all settings in a group

```php
$apps = $appSettings->getGroup('apps');
```

### Get a typed setting value

```php
$timeout = $appSettings->getTyped('system', 'timeout'); // Returns int, bool, array, or string
```

---

## 🛡️ Typed Configuration Behavior

The module enforces strict typing for configuration values:

- **STRING**: Always valid.
- **INT**: Must be numeric and castable to an integer.
- **BOOL**: Accepts only `true`, `false`, `1`, `0` (case-insensitive).
- **JSON**: Must be valid JSON string; returns associative array.

**Validation:**
`create()` and `update()` throw `InvalidAppSettingException` if the value violates the type rules.

**Concurrency:**
`create()` throws `DuplicateAppSettingException` if a race condition occurs.

---

## ✏️ Write Operations (Admin/System Only)

### Create

```php
$appSettings->create(
    new AppSettingDTO(
        group: 'social',
        key: 'instagram',
        value: 'https://instagram.com/ep4n',
        valueType: AppSettingValueTypeEnum::STRING
    )
);
```

### Update

```php
$appSettings->update(
    new AppSettingUpdateDTO(
        group: 'social',
        key: 'instagram',
        value: 'https://instagram.com/ep4n_official'
    )
);
```

### Disable / Enable (Soft)

```php
$appSettings->setActive(
    new AppSettingKeyDTO('social', 'instagram'),
    false
);
```

---

## 🔒 Policies & Rules

### Whitelist Policy

* Only predefined groups and keys are allowed
* Any unknown group/key throws an exception

### Protection Policy

Some settings are **protected** and cannot be:

* Disabled
* Modified

Examples:

* `system.base_url`
* `system.environment`

---

## ❗ Error Handling

The module fails **loudly and explicitly** using domain exceptions:

* `AppSettingNotFoundException`
* `InvalidAppSettingException`
* `AppSettingProtectedException`

Consumers are expected to handle these cases.

---

## 🚫 Forbidden Patterns

* Direct SQL access to `app_settings`
* Physical DELETE operations
* Storing secrets (passwords, tokens, keys)
* Using settings as user-generated content

---

## 🔮 Future Extensions (Non-breaking)

The design allows future additions without schema changes:

* Caching layer (Redis / APCu)
* Multi-tenant settings
* Localization
* Feature flags dashboard
* Audit logging for changes

---

## 🏁 Status

This module is **library-grade**, stable, and intended for long-term use.

All changes must respect:

* Existing DTOs
* Repository contracts
* Policy rules

Breaking changes require an explicit architectural decision.
