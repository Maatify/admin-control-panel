# Currencies — UI & API Integration Guide

**Module:** `Modules/currency`
**Status:** CANONICAL / BINDING CONTRACT

## 0) Why this document exists
This document defines the strict API contract for interacting with Currencies and their Translations via the backend REST API. It outlines all necessary endpoints, their payload schemas, response structures, and capability rules to guide frontend UI implementations.

## 1) Endpoints

### Currencies Dropdown
**Method:** POST
**URL:** `/api/currencies/dropdown`
**Route name:** `currencies.dropdown.api`
**Capability:** `currencies.dropdown`

#### Request Payload
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `language_id` | `int` | No | Must be an integer representing a valid language ID. |

#### Response — Success
```json
[
  {
    "id": 1,
    "code": "USD",
    "name": "US Dollar",
    "symbol": "$",
    "is_active": true,
    "display_order": 1,
    "created_at": "2026-01-01 12:00:00",
    "updated_at": null,
    "translated_name": "دولار أمريكي",
    "language_id": 2
  }
]
```

#### Response — Error
```json
{
  "success": false,
  "error": {
    "code": "error",
    "category": "SYSTEM",
    "message": "Invalid request payload",
    "meta": [],
    "retryable": false
  }
}
```

### List Currencies
**Method:** POST
**URL:** `/api/currencies/query`
**Route name:** `currencies.list.api`
**Capability:** `currencies.list`

#### Request Payload
Standard `SharedListQuerySchema` format, plus optional `language_id`.

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `page` | `int` | No | Minimum 1 |
| `per_page` | `int` | No | Minimum 1 |
| `search.global` | `string` | No | - |
| `search.columns` | `object` | No | Keys must be valid filterable columns (`is_active`, `code`) |
| `language_id` | `int` | No | ID for translation JOIN |

#### Response — Success
```json
{
  "data": [
    {
      "id": 1,
      "code": "USD",
      "name": "US Dollar",
      "symbol": "$",
      "is_active": true,
      "display_order": 1,
      "created_at": "2026-01-01 12:00:00",
      "updated_at": null,
      "translated_name": "دولار أمريكي",
      "language_id": 2
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 50,
    "filtered": 1
  }
}
```

### Create Currency
**Method:** POST
**URL:** `/api/currencies/create`
**Route name:** `currencies.create.api`
**Capability:** `currencies.create`

#### Request Payload
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `code` | `string` | Yes | 3 chars (ISO 4217) |
| `name` | `string` | Yes | 1-50 chars |
| `symbol` | `string` | Yes | 1-10 chars |
| `is_active` | `bool` | No | Default true |
| `display_order` | `int` | No | Minimum 0 |

#### Response — Success
```json
{
  "success": true
}
```

### Update Currency
**Method:** POST
**URL:** `/api/currencies/update`
**Route name:** `currencies.update.api`
**Capability:** `currencies.update`

#### Request Payload
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `id` | `int` | Yes | Minimum 1 |
| `code` | `string` | Yes | 3 chars (ISO 4217) |
| `name` | `string` | Yes | 1-50 chars |
| `symbol` | `string` | Yes | 1-10 chars |
| `is_active` | `bool` | Yes | - |
| `display_order` | `int` | Yes | Minimum 1 |

#### Response — Success
```json
{
  "success": true
}
```

### Update Currency Status
**Method:** POST
**URL:** `/api/currencies/set-active`
**Route name:** `currencies.set_active.api`
**Capability:** `currencies.set_active`

#### Request Payload
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `id` | `int` | Yes | Minimum 1 |
| `is_active` | `bool` | Yes | - |

#### Response — Success
```json
{
  "success": true
}
```

### Update Currency Sort Order
**Method:** POST
**URL:** `/api/currencies/update-sort`
**Route name:** `currencies.update_sort.api`
**Capability:** `currencies.update_sort`

#### Request Payload
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `id` | `int` | Yes | Minimum 1 |
| `display_order` | `int` | Yes | Minimum 1 |

#### Response — Success
```json
{
  "success": true
}
```

### List Currency Translations
**Method:** POST
**URL:** `/api/currencies/{currency_id}/translations/query`
**Route name:** `currencies.translations.list.api`
**Capability:** `currencies.translations.list`

#### Request Payload
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| - | - | - | Currently accepts empty body or `SharedListQuerySchema` although only non-paginated returned. |

#### Response — Success
```json
[
  {
    "id": 1,
    "language_id": 2,
    "language_code": "ar",
    "language_name": "Arabic",
    "translated_name": "دولار أمريكي",
    "has_translation": true,
    "created_at": "2026-01-01 12:00:00",
    "updated_at": null
  }
]
```

### Upsert Currency Translation
**Method:** POST
**URL:** `/api/currencies/{currency_id}/translations/upsert`
**Route name:** `currencies.translations.upsert.api`
**Capability:** `currencies.translations.upsert`

#### Request Payload
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `language_id` | `int` | Yes | Minimum 1 |
| `translated_name` | `string` | Yes | 1-50 chars |

#### Response — Success
```json
{
  "success": true
}
```

### Delete Currency Translation
**Method:** POST
**URL:** `/api/currencies/{currency_id}/translations/delete`
**Route name:** `currencies.translations.delete.api`
**Capability:** `currencies.translations.delete`

#### Request Payload
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `language_id` | `int` | Yes | Minimum 1 |

#### Response — Success
```json
{
  "success": true
}
```

## 2) What the UI must NOT do
- Must NOT rely on route IDs for updates, except for the parent `currency_id` on translations. Entity IDs for updates/deletes should be in the payload.
- Must NOT send `id` in creation endpoints.
- Must NOT attempt to update `symbol` without ISO standards consideration.
- Must NOT handle exceptions by parsing HTML output; wait for strict JSON `{"success": false}` payload matching.

## 3) Implementation Checklist
- [x] Endpoints exposed and registered in router.
- [x] Validation constraints properly implemented via `ValidationGuard` schemas.
- [x] API Contract Document updated for frontend implementation references.
