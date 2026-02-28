# 🌍 Scope Keys Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / I18n`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the Scope Keys UI.

It answers, precisely:

* What the UI is allowed to send
* How global search and filters actually work
* What each endpoint requires vs what is optional
* What response shapes exist (success + failure)
* Why you are getting `422` / `404` / runtime exceptions

If something is not documented here, treat it as **not supported**.

### ⚠️ CRITICAL: UI vs API Distinction

You must understand the difference between the **UI Page** and the **API**:

* **`GET /i18n/scopes/{scope_id}/keys`**

    * ❌ **This is NOT an API.**
    * ✅ This is the **browser entry point** that renders the HTML page.
    * It returns `text/html`.
    * Do not call this from JavaScript fetch/axios.

* **`POST /api/i18n/scopes/{scope_id}/keys/*`**

    * ✅ **These ARE the APIs.**
    * They return `application/json`.
    * All programmatic interaction happens here.

> ⚠️ **RUNTIME RULES:**
> This document assumes **mandatory compliance** with the **[UI Runtime Integration Rules](UI_RUNTIME_RULES.md)**.
> Refer to that file for:
>
> * Response parsing (JSON vs Empty Body)
> * Error handling (422/403/404)
> * Null handling in payloads
> * Canonical Query construction

---

## 1) Page Architecture

```
Twig Controller
  ├─ injects capabilities
  ├─ renders scope keys page
  └─ includes JS bundle

JavaScript
  ├─ DataTable (query + pagination)
  └─ Actions (create, rename, update metadata)

API (authoritative)
  ├─ validates request schema
  ├─ resolves scope code
  ├─ applies query resolver rules
  └─ returns canonical envelope (queries) or JSON { status: ok }
```

---

## 2) Capabilities (Authorization Contract)

The UI receives these **Scope Keys-specific capability flags**:

### 2.1 Injected Flags

```javascript
window.scopeKeysCapabilities = {
  can_create,
  can_rename,
  can_update_meta,
  can_view_scopes
};
```

### 2.2 Capability → UI Behavior Mapping

| Capability        | UI Responsibility                              |
|-------------------|------------------------------------------------|
| `can_create`      | Enable/disable **create key** controls         |
| `can_rename`      | Enable/disable **rename key** controls         |
| `can_update_meta` | Enable/disable **update description** controls |
| `can_view_scopes` | Enable/disable breadcrumb navigation           |

> **Note:** Authorization is enforced server-side. The UI MUST NOT infer or derive permissions.

---

## 3) List Scope Keys (table)

**Endpoint:** `POST /api/i18n/scopes/{scope_id}/keys/query`
**Capability:** Available by default for authenticated admins with access to the scope.

### Request — Specifics

* **Global Search:** Free-text search applied on top of column filters. Matches against **domain OR key_part**.
* **Sorting:** ⚠️ **NOT SUPPORTED**.

    * The server returns results ordered by `id ASC`.
    * Clients **MUST NOT** send `sort` parameters.

**Example Request:**

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "home",
    "columns": {
      "domain": "store_front"
    }
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias      | Type   | Example   | Semantics                  |
|------------|--------|-----------|----------------------------|
| `id`       | string | `"12"`    | exact match (`k.id = :id`) |
| `domain`   | string | `"store"` | LIKE match                 |
| `key_part` | string | `"home"`  | LIKE match                 |

No other column filters are supported.

### Response Model

```json
{
  "data": [
    {
      "id": 12,
      "scope": "admin",
      "domain": "store_front",
      "key_part": "home.title",
      "description": "Homepage title",
      "created_at": "2026-02-11 10:15:00"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 25,
    "total": 120,
    "filtered": 5
  }
}
```

### Supported Global Search (`search.global`)

| Column     | SQL Expression Used            | Match Type         | Included in Global Search |
|------------|--------------------------------|--------------------|---------------------------|
| `domain`   | `k.domain LIKE :global_text`   | Partial (`%text%`) | ✅ Yes                     |
| `key_part` | `k.key_part LIKE :global_text` | Partial (`%text%`) | ✅ Yes                     |

---

### Backend Behavior (Exact)

```sql
(k.domain LIKE :global_text OR k.key_part LIKE :global_text)
```

Wrapped as:

```
%{searchText}%
```

---

### Placeholder Must Reflect

Because global search matches only:

* `domain`
* `key_part`

Recommended placeholder:

```
🔍 Search by domain or key segment...
```

---


### Pagination Meanings (REQUIRED)

* `total`: total records in `i18n_keys` (no filters applied, global table count)
* `filtered`: total records after applying scope + filters
* When no filters are applied, `filtered` MAY equal `total`

**Example Error Response (422):**

```json
{
  "status": "error",
  "errors": {
    "page": "must be an integer"
  }
}
```

**Example Error Response (404):**

```json
{
  "error": "ENTITY_NOT_FOUND",
  "message": "scope not found"
}
```

---

## 3.1 Load Domains for Create Key (Dropdown Integration)

The **Create Key modal** requires a list of domains assigned to the current scope.

### Endpoint

```
GET /api/i18n/scopes/{scope_id}/domains/dropdown
```

### Authorization

This endpoint requires:

```
i18n.scopes.domains.dropdown.api
```

Mapped internally to:

```
i18n.scopes.keys.create
```

Only users allowed to create keys can load the domain dropdown.

---

### Response Model

```json
{
  "data": [
    {
      "code": "store_front",
      "name": "Store Front"
    },
    {
      "code": "admin_panel",
      "name": "Admin Panel"
    }
  ]
}
```

No pagination.
No search.
No filtering.

---

### Frontend Responsibilities

1. Load this endpoint when:

  * Page initializes
  * OR when opening the Create Key modal

2. Populate a `<select>`:

```html
<select name="domain_code">
  <option value="store_front">Store Front</option>
</select>
```

3. Send selected `domain_code` when calling:

```
POST /api/i18n/scopes/{scope_id}/keys/create
```

---

### Important Runtime Notes

* If `scope_id` is invalid → 404
* If user lacks permission → 403
* If no domains assigned → `data` will be empty array

Example empty:

```json
{
  "data": []
}
```

Frontend must handle empty state gracefully.

---

### Why This Endpoint Exists

Domains are scope-specific.
UI must not:

* Hardcode domains
* Fetch all system domains
* Infer assignments client-side

Only the backend determines which domains belong to the scope.

---

## 4) Create Key

**Endpoint:** `POST /api/i18n/scopes/{scope_id}/keys/create`
**Capability:** `can_create`

### Request Body

* `domain_code` (string, required)
* `key_name` (string, required)
* `description` (string, optional, max 255)

**Example Request:**

```json
{
  "domain_code": "store_front",
  "key_name": "home.title",
  "description": "Homepage title"
}
```

**Example Success Response:**

```json
{
  "status": "ok"
}
```

**Example Error Response (422):**

```json
{
  "status": "error",
  "errors": {
    "key_name": "This field is required"
  }
}
```

---

## 5) Rename Key

**Endpoint:** `POST /api/i18n/scopes/{scope_id}/keys/update-name`
**Capability:** `can_rename`

### Request Body

* `key_id` (integer, required)
* `key_name` (string, required)

**Example Request:**

```json
{
  "key_id": 12,
  "key_name": "home.page.title"
}
```

**Example Success Response:**

```json
{
  "status": "ok"
}
```

**Example Error Response (404):**

```json
{
  "error": "ENTITY_NOT_FOUND",
  "message": "key not found"
}
```

---

## 6) Update Key Metadata

**Endpoint:** `POST /api/i18n/scopes/{scope_id}/keys/update_metadata`
**Capability:** `can_update_meta`

### Request Body

* `key_id` (integer, required)
* `description` (string, required, max 255)

**Example Request:**

```json
{
  "key_id": 12,
  "description": "Updated homepage title"
}
```

**Example Success Response:**

```json
{
  "status": "ok"
}
```

**Example Error Response (404):**

```json
{
  "error": "ENTITY_NOT_FOUND",
  "message": "key not found"
}
```

---

## 7) Implementation Checklist

* [ ] **Never send `sort`** to `/api/i18n/scopes/{scope_id}/keys/query`.
* [ ] Global search matches only `domain` and `key_part`.
* [ ] Use only supported column filters.
* [ ] Respect `window.scopeKeysCapabilities` for UI controls.
* [ ] Treat `scope_id` as path parameter only (never in body).

---
