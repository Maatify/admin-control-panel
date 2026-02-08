# 🌍 Scope Domains Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / I18n`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the Scope Domains UI.

It answers, precisely:

*   What the UI is allowed to send
*   How global search and filters actually work
*   What each endpoint requires vs what is optional
*   What response shapes exist (success + failure)
*   Why you are getting `422` / runtime exceptions

If something is not documented here, treat it as **not supported**.

### ⚠️ CRITICAL: UI vs API Distinction

You must understand the difference between the **UI Page** and the **API**:

*   **`GET /i18n/scopes/{scope_id}`**
    *   ❌ **This is NOT an API.**
    *   ✅ This is the **browser entry point** that renders the HTML page.
    *   It returns `text/html`.
    *   Do not call this from JavaScript fetch/axios.

*   **`POST /api/i18n/scopes/{scope_id}/domains/*`**
    *   ✅ **These ARE the APIs.**
    *   They return `application/json` (or empty 200).
    *   All programmatic interaction happens here.

> ⚠️ **RUNTIME RULES:**
> This document assumes **mandatory compliance** with the **[UI Runtime Integration Rules](UI_RUNTIME_RULES.md)**.
> Refer to that file for:
> *   Response parsing (JSON vs Empty Body)
> *   Error handling (422/403)
> *   Null handling in payloads
> *   Canonical Query construction

---

## 1) Page Architecture

```
Twig Controller
  ├─ injects capabilities
  ├─ renders scope details page
  └─ includes JS bundle

JavaScript
  ├─ DataTable (query + pagination)
  └─ Actions (assign, unassign)

API (authoritative)
  ├─ validates request schema
  ├─ applies query resolver rules
  └─ returns canonical envelope (queries) or empty 200 (actions)
```

---

## 2) Capabilities (Authorization Contract)

The UI receives these **Scope Domains-specific capability flags**:

### 2.1 Injected Flags

```javascript
window.scopeDetailsCapabilities = {
  can_assign,
  can_unassign
};
```

### 2.2 Capability → UI Behavior Mapping

| Capability     | UI Responsibility                    |
|----------------|--------------------------------------|
| `can_assign`   | Enable/disable **assign** controls   |
| `can_unassign` | Enable/disable **unassign** controls |

> **Note:** Authorization is enforced server-side. The UI MUST NOT infer or derive permissions.

---

## 3) List Scope Domains (table)

**Endpoint:** `POST /api/i18n/scopes/{scope_id}/domains/query`
**Capability:** Available by default for authenticated admins with access to the scope.

### Request — Specifics

*   **Global Search:** Free-text search applied on top of column filters. Matches against **code OR name OR description**.
*   **Sorting:** ⚠️ **NOT SUPPORTED**.
    *   The server returns results in a fixed internal order.
    *   Clients **MUST NOT** send `sort` parameters.

**Example Request:**

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "store",
    "columns": {
      "is_active": "1"
    }
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias       | Type   | Example   | Semantics         |
|-------------|--------|-----------|-------------------|
| `id`        | string | `"1"`     | exact match       |
| `code`      | string | `"store"` | exact match       |
| `name`      | string | `"Store"` | exact match       |
| `is_active` | string | `"1"`     | cast to int (1/0) |

### Response Model

```json
{
  "data": [
    {
      "id": 1,
      "code": "store_front",
      "name": "Store Front",
      "description": "Main store interface",
      "is_active": 1,
      "sort_order": 10,
      "assigned": 1
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 25,
    "total": 10,
    "filtered": 3
  }
}
```

### Pagination Meanings (REQUIRED)

* `total`: total records in DB (no filters)
* `filtered`: total records after applying filters
* When no filters are applied, `filtered` MAY equal `total`

> **Note:** `is_active` and `assigned` are returned as integers (`0` or `1`).

**Example Error Response (422):**

```json
{
  "status": "error",
  "errors": {
    "page": "must be an integer"
  }
}
```

---

## 4) Assign Domain

**Endpoint:** `POST /api/i18n/scopes/{scope_id}/domains/assign`
**Capability:** `can_assign`

### Request Body

*   `domain_code` (string, required, 1-64 chars)

**Example Request:**

```json
{
  "domain_code": "store_front"
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
    "domain_code": "This field is required"
  }
}
```

**Example Error Response (409):**

```json
{
  "error": "INVALID_OPERATION",
  "message": "Invalid operation \"assign\" on domain: already assigned to scope."
}
```

---

## 5) Unassign Domain

**Endpoint:** `POST /api/i18n/scopes/{scope_id}/domains/unassign`
**Capability:** `can_unassign`

### Request Body

*   `domain_code` (string, required, 1-64 chars)

**Example Request:**

```json
{
  "domain_code": "store_front"
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
    "domain_code": "This field is required"
  }
}
```

**Example Error Response (409):**

```json
{
  "error": "INVALID_OPERATION",
  "message": "Invalid operation \"unassign\" on domain: not assigned to scope."
}
```
---

## 6) Implementation Checklist

*   [ ] **Never send `sort`** to `/api/i18n/scopes/{scope_id}/domains/query`.
*   [ ] Treat `is_active` and `assigned` as integers (`0`/`1`).
*   [ ] Respect `window.scopeDetailsCapabilities` for UI controls.

## Audit Result
Document corrected to match enforced runtime behavior.
