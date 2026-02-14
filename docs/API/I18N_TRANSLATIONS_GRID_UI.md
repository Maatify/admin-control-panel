# 🌍 Translation Grid — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / I18n`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file defines the **runtime integration contract** for the Translation Grid.

It specifies, precisely:

* How the Translation Grid loads data
* How inline editing must behave
* What mutation endpoints are allowed
* What validation rules apply
* What response shapes must be handled
* How delete vs upsert must be triggered

If something is not explicitly documented here, treat it as **not supported**.

This document is binding.

---

## 1) Architectural Separation

### 1.1 Translation Context

Translation is defined as:

```
(language_id + key_id) → value
```

Translation mutation is **language-scoped**, not domain-scoped.

Domain is used only for **grid context and data loading**.

---

## 2) Page Context Requirements

The Translation Grid page must be provided with:

* `scope_id`
* `domain_code`
* `available_languages`
* `capabilities`

These are injected by the server (Twig controller).

No grid data is injected server-side.

All translation data must be loaded via API.

---

## 3) Load Translation Grid

### Endpoint

```
POST /api/i18n/scopes/{scope_id}/domains/{domain_code}/translations/query
```

### Capability

Authenticated admin with access to the scope.

---

## 3.1 Request Payload

| Field      | Type   | Required | Description                |
|------------|--------|----------|----------------------------|
| `page`     | int    | Yes      | Pagination page number     |
| `per_page` | int    | Yes      | Number of records per page |
| `mode`     | string | No       | `all` or `missing`         |
| `search`   | object | No       | Optional key search        |

---

### Example

```json
{
  "page": 1,
  "per_page": 50,
  "mode": "all",
  "search": {
    "key_part": "home"
  }
}
```

---

## 3.2 Response Model

```json
{
  "languages": [
    { "id": 1, "code": "en" },
    { "id": 2, "code": "ar" }
  ],
  "data": [
    {
      "key_id": 101,
      "key_part": "login.title",
      "translations": {
        "1": "Sign In",
        "2": "تسجيل الدخول"
      }
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 120
  }
}
```

---

## 3.3 Missing Mode

If `mode = "missing"`:

Only keys where at least one language has no translation value must be returned.

---

## 4) Inline Editing Rules

The grid is **editable inline**.

Each cell represents:

```
language_id + key_id
```

Mutation must occur immediately after:

* Blur event
* Enter key confirmation

No full-page reload is allowed.

---

## 5) Upsert Translation

**Endpoint:**

```
POST /languages/{language_id}/translations/upsert
```

**Route Name:**

```
languages.translations.upsert.api
```

**Capability:** `can_upsert`

---

## 5.1 Request Payload

> `language_id` is resolved from the route.

| Field    | Type   | Required | Description                    |
|----------|--------|----------|--------------------------------|
| `key_id` | int    | Yes      | ID of translation key          |
| `value`  | string | Yes      | Translation value (length ≥ 1) |

---

## 5.2 Validation Rules

* `key_id` must be integer > 0.
* `value` must be string.
* `value` must have length ≥ 1.
* No max length restriction (DB type TEXT).

---

## 5.3 Example

```
POST /languages/2/translations/upsert
```

```json
{
  "key_id": 101,
  "value": "دخول"
}
```

---

## 5.4 Success Response

```
200 OK
{"status":"ok"}
```

---

## 5.5 Validation Error

```
422 Unprocessable Entity
```

```json
{
  "success": false,
  "errors": {
    "value": ["REQUIRED_FIELD"]
  }
}
```

---

## 6) Delete Translation

**Endpoint:**

```
POST /languages/{language_id}/translations/delete
```

**Route Name:**

```
languages.translations.delete.api
```

**Capability:** `can_delete`

---

## 6.1 Purpose

Removes translation value for the specific language.

---

## 6.2 Request

```
POST /languages/2/translations/delete
```

```json
{
  "key_id": 101
}
```

---

## 6.3 Validation Rules

* `key_id` must be integer > 0.
* `language_id` must match page context.

---

## 6.4 Success

```
200 OK
{"status":"ok"}
```

---

## 7) Editable Grid Mutation Rules

### 7.1 Non-Empty Value

If cell value length ≥ 1:

→ Must call `upsert`.

### 7.2 Empty Value

If cell becomes empty string:

→ Must call `delete`.

---

## 8) UI Mutation Behavior

* Cell must display loading indicator during request.
* On success → show saved indicator.
* On validation error → cell must display error state.
* No silent failures allowed.
* No optimistic commit without server confirmation.

---

## 9) Implementation Checklist

* [ ] Grid data loaded only via translations/query endpoint.
* [ ] Inline edit must trigger upsert or delete.
* [ ] language_id must always come from route.
* [ ] key_id must come from grid row context.
* [ ] No batching.
* [ ] No client-side inference of permissions.
* [ ] UI must respect capability flags.

---

**END OF CONTRACT**
