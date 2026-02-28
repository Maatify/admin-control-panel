# 📄 Content Document Types Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / ContentDocuments`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the **Content Document Types UI**.

It defines, precisely:

* What the UI is allowed to send
* How list filtering works
* What the dropdown endpoint is for
* What is required vs optional
* What response shapes exist
* Why you are getting `422`, `403`, or runtime exceptions

If something is not documented here, treat it as **not supported**.

---

## ⚠️ CRITICAL: UI vs API Distinction

You must understand the difference between the **UI Page** and the **API**:

### UI Page

* **`GET /content-document-types`**

    * ❌ This is NOT an API.
    * ✅ This renders the HTML page (Twig).
    * Returns `text/html`.
    * Never call this via fetch/axios.

---

### APIs

All programmatic interaction happens via:

* `POST /api/content-document-types/*`

These return `application/json`.

---

# 1) Page Architecture

```
Twig Controller
  ├─ injects capabilities
  ├─ renders content document types page
  └─ includes JS bundle

JavaScript
  ├─ DataTable (query + pagination)
  ├─ Create Modal (uses dropdown endpoint)
  ├─ Update Modal
  └─ Action handlers

API (authoritative)
  ├─ validates request schema
  ├─ applies list resolver rules
  ├─ applies enum enforcement
  ├─ applies protection rules
  └─ returns canonical envelope (queries) or empty 200 (actions)
```

---

# 2) Capabilities (Authorization Contract)

Injected server-side:

```php
$capabilities = [
    'can_create' => hasPermission('content_documents.types.create'),
    'can_update' => hasPermission('content_documents.types.update'),
];
```

### Capability → UI Behavior Mapping

| Capability | UI Responsibility   |
|------------|---------------------|
| can_create | Show Create button  |
| can_update | Enable edit actions |

UI **must not infer** capabilities.

Use only injected flags.

---

# 3) List Content Document Types (Table)

**Endpoint:**
`POST /api/content-document-types/query`

**Capability:** Available for authenticated admins.

---

## 3.1 Request Payload

| Field          | Type   | Required | Description         |
|----------------|--------|----------|---------------------|
| page           | int    | Optional | Default 1           |
| per_page       | int    | Optional | Default 25, max 100 |
| search         | object | Optional | Search wrapper      |
| search.global  | string | Optional | Free-text search    |
| search.columns | object | Optional | Column filters      |

---

## 3.2 Supported Filters (search.columns)

Based on `ContentDocumentTypeListCapabilities`.

| Alias                       | Type   | Semantics    |
|-----------------------------|--------|--------------|
| id                          | string | exact match  |
| key                         | string | LIKE %value% |
| requires_acceptance_default | string | cast to int  |
| is_system                   | string | cast to int  |

---

## 3.3 Sorting Rule

⚠️ **SERVER-CONTROLLED**

Clients MUST NOT send sort parameters.

---

## 3.4 Example Request

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "terms",
    "columns": {
      "is_system": "1"
    }
  }
}
```

---

## 3.5 Success Response

```json
{
  "data": [
    {
      "id": 1,
      "key": "terms",
      "requires_acceptance_default": 1,
      "is_system": 1,
      "created_at": "2026-02-20 05:00:00",
      "updated_at": "2026-02-20 05:00:00"
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

---

## 3.6 Error Example (422 Invalid Filter)

```json
{
  "success": false,
  "error": {
    "code": 422,
    "type": "VALIDATION_FAILED"
  }
}
```

---

# 4) Dropdown (CRITICAL — Create Dependency)

**Endpoint:**
`POST /api/content-document-types/dropdown`

---

## ⚠️ CRITICAL UI RULE

This endpoint:

* MUST be called **when opening the Create Modal**
* MUST NOT be replaced with a free text input
* MUST populate a **SELECT dropdown**
* MUST enforce selection from returned values
* MUST NOT allow arbitrary key entry

---

## Why?

Document type keys are defined by:

```php
DocumentTypeKeyEnum
```

And enforced at backend.

You CANNOT create arbitrary keys.

---

## 4.1 What Dropdown Returns

It returns **available enum keys NOT yet registered**.

If a key is already created in DB, it will not appear again.

---

## 4.2 Request Payload

Send empty JSON:

```json
{}
```

---

## 4.3 Success Response

```json
{
  "data": [
    {
      "key": "refund_policy",
      "label": "Refund Policy"
    },
    {
      "key": "gdpr_notice",
      "label": "GDPR Notice"
    }
  ]
}
```

---

## 4.4 UI Behavior Requirements

| Requirement                    | Mandatory |
|--------------------------------|-----------|
| Use SELECT element             | YES       |
| Preload dropdown on page load  | NO        |
| Load on modal open             | YES       |
| Allow custom typing            | NO        |
| Validate selection client-side | YES       |
| Trust server validation        | YES       |

If dropdown returns empty array:

→ Disable Create button or show message:
"All document types are already registered."

---

# 5) Create Content Document Type

**Endpoint:**
`POST /api/content-document-types/create`

**Capability:** `can_create`

---

## 5.1 Request Payload

| Field                       | Type   | Required | Description        |
|-----------------------------|--------|----------|--------------------|
| key                         | string | YES      | Must match enum    |
| requires_acceptance_default | bool   | YES      | Default acceptance |
| is_system                   | bool   | YES      | System flag        |

---

## 5.2 Example Request

```json
{
  "key": "refund_policy",
  "requires_acceptance_default": true,
  "is_system": false
}
```

---

## 5.3 Success Response

```json
{
  "status": "ok"
}
```

---

## 5.4 Error Example (422 Invalid Key)

```json
{
  "success": false,
  "error": {
    "code": 422,
    "type": "VALIDATION_FAILED"
  }
}
```

---

# 6) Update Content Document Type

**Endpoint:**
`POST /api/content-document-types/{id}/update`

**Capability:** `can_update`

---

## 6.1 Request Payload

| Field                       | Type | Required |
|-----------------------------|------|----------|
| requires_acceptance_default | bool | Optional |
| is_system                   | bool | Optional |

⚠️ `key` CANNOT be modified.

---

## 6.2 Example Request

```json
{
  "requires_acceptance_default": false,
  "is_system": true
}
```

---

## 6.3 Success Response

```json
{
  "status": "ok"
}
```

---

## 6.4 Error Example (403)

```json
{
  "success": false,
  "error": {
    "code": 403,
    "type": "INSUFFICIENT_PERMISSIONS"
  }
}
```

---

# 7) Full Implementation Checklist (Content Document Types Specific)

### LIST

* [ ] Never send `sort`
* [ ] Respect server-controlled pagination
* [ ] Map filters exactly to supported aliases

---

### DROPDOWN

* [ ] Call only when opening Create modal
* [ ] Render SELECT (not input)
* [ ] Disable Create if empty
* [ ] Never allow arbitrary key typing

---

### CREATE

* [ ] Must use selected dropdown key
* [ ] Send booleans as real booleans
* [ ] Handle 422 errors properly

---

### UPDATE

* [ ] Never allow editing `key`
* [ ] Only send changed flags
* [ ] Respect capability flag

---

# 8) Runtime Failure Scenarios

| Error          | Cause                    |
|----------------|--------------------------|
| 422            | Invalid enum key         |
| 422            | Boolean type mismatch    |
| 403            | Missing permission       |
| Empty dropdown | All enum keys registered |

---

# FINAL AUTHORITATIVE RULE

Document type keys are controlled by:

```
DocumentTypeKeyEnum
```

The UI must treat dropdown values as **authoritative source of truth**.

No manual input allowed.

No key guessing.

No client-side enum reconstruction.

---
