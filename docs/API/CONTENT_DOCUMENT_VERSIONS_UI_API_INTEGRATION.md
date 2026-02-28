# 📄 Content Document Versions Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / ContentDocuments`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

# 0) Why this document exists

This file is a **runtime integration contract** for the **Content Document Versions UI**.

It defines, precisely:

* What the UI is allowed to send
* How list filtering works
* How scoped filtering by `type_id` works
* What endpoints exist
* What state transitions are allowed
* What response shapes exist
* Why you are getting `422`, `403`, `404`, or lifecycle exceptions

If something is not documented here, treat it as **NOT SUPPORTED**.

This document is **fully self-contained**.

---

# ⚠️ CRITICAL: UI vs API Distinction

You must understand the difference between the **UI Page** and the **API**.

---

## UI Page

**Route:**

```
GET /content-document-types/{type_id}/versions
```

* ❌ This is NOT an API.
* ✅ This renders the HTML page (Twig).
* Returns `text/html`.
* Never call this via fetch/axios.
* `type_id` is injected from routing context.

---

## APIs

All programmatic interaction happens via:

```
POST /api/content-document-types/{type_id}/versions/*
```

All APIs:

* Return `application/json`
* Are schema validated
* Are permission enforced
* Are scope enforced by `type_id`

---

# 1) Page Architecture

```
Twig Controller
  ├─ validates type_id
  ├─ injects capabilities
  ├─ injects type metadata
  └─ renders versions page

JavaScript
  ├─ DataTable (query + pagination)
  ├─ Create Modal
  ├─ Publish / Activate / Deactivate / Archive actions
  └─ Confirmation handlers

API (authoritative)
  ├─ validates route args
  ├─ validates request schema
  ├─ enforces type scope
  ├─ enforces lifecycle rules
  └─ returns canonical envelope (queries) or empty 200 (actions)
```

---

# 2) Scope Rule (CRITICAL)

All version operations are scoped by:

```
type_id
```

Every API request:

```
/api/content-document-types/{type_id}/versions/...
```

### What this means:

* You CANNOT query versions without type_id.
* You CANNOT activate a version outside its type.
* You CANNOT deactivate a version outside its type.
* You CANNOT archive a version of another type.
* Cross-type access returns `404`.

---

# 3) Capabilities (Authorization Contract)

Injected server-side:

```php
$capabilities = [
    'can_create'     => hasPermission('content_documents.versions.create'),
    'can_publish'    => hasPermission('content_documents.versions.publish'),
    'can_activate'   => hasPermission('content_documents.versions.activate'),
    'can_deactivate' => hasPermission('content_documents.versions.deactivate'),
    'can_archive'    => hasPermission('content_documents.versions.archive'),
];
```

---

## Capability → UI Behavior Mapping

| Capability     | UI Responsibility      |
|----------------|------------------------|
| can_create     | Show Create button     |
| can_publish    | Show Publish action    |
| can_activate   | Show Activate action   |
| can_deactivate | Show Deactivate action |
| can_archive    | Show Archive action    |

UI **must not infer permissions**.

Use only injected flags.

---

# 4) List Versions (Table)

**Endpoint:**

```
POST /api/content-document-types/{type_id}/versions/query
```

---

## 4.1 Request Payload

| Field          | Type   | Required | Description         |
|----------------|--------|----------|---------------------|
| page           | int    | Optional | Default 1           |
| per_page       | int    | Optional | Default 25, max 100 |
| search         | object | Optional | Search wrapper      |
| search.global  | string | Optional | Free text           |
| search.columns | object | Optional | Column filters      |

---

## 4.2 Supported Filters (search.columns)

| Alias               | Type   | Semantics                 |
|---------------------|--------|---------------------------|
| document_id         | string | exact id                  |
| version             | string | LIKE %value%              |
| is_active           | string | cast to int               |
| requires_acceptance | string | cast to int               |
| status              | string | draft / active / archived |

---

## 4.3 Date Filtering

Date filtering is server-controlled.

UI must send:

```
created_from
created_to
```

Inside:

```
search.columns
```

Example:

```json
{
  "search": {
    "columns": {
      "created_from": "2026-01-01",
      "created_to": "2026-02-01"
    }
  }
}
```

Server expands internally to:

```
created_at >= 2026-01-01 00:00:00
created_at <= 2026-02-01 23:59:59
```

UI must NOT attempt manual date formatting beyond ISO date.

---

## 4.4 Sorting Rule

⚠️ SERVER-CONTROLLED

Clients MUST NOT send sort parameters.

---

## 4.5 Example Request

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "1.0",
    "columns": {
      "status": "active"
    }
  }
}
```

---

## 4.6 Success Response

```json
{
  "data": [
    {
      "id": 5,
      "document_type_id": 2,
      "type_key": "terms",
      "version": "1.0",
      "is_active": 1,
      "requires_acceptance": 1,
      "published_at": "2026-02-01 10:00:00",
      "archived_at": null,
      "created_at": "2026-01-20 08:00:00",
      "updated_at": "2026-02-01 10:00:00"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 25,
    "total": 10,
    "filtered": 1
  }
}
```

---

# 5) Create Version

**Endpoint:**

```
POST /api/content-document-types/{type_id}/versions/create
```

---

## 5.1 Request Payload

| Field               | Type   | Required |
|---------------------|--------|----------|
| version             | string | YES      |
| requires_acceptance | bool   | YES      |

---

## 5.2 Example Request

```json
{
  "version": "2.0",
  "requires_acceptance": true
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

# 6) Publish Version

**Endpoint**

```
POST /api/content-document-types/{type_id}/versions/{document_id}/publish
```

No body required.

---

## Publish Rules

* Cannot publish archived document.
* Cannot publish non-existing document.
* Idempotent — publishing already published document does nothing.

---

# 7) Activate Version

**Endpoint**

```
POST /api/content-document-types/{type_id}/versions/{document_id}/activate
```

No body required.

---

## Activation Rules

* Version must be published.
* Only one version per type can be active.
* Automatically deactivates other versions of same type.
* Idempotent if already active.

---

# 8) Deactivate Version

**Endpoint**

```
POST /api/content-document-types/{type_id}/versions/{document_id}/deactivate
```

No body required.

---

## Deactivation Rules

* Can only deactivate non-archived version.
* Idempotent — deactivating already inactive version does nothing.
* Does NOT automatically activate another version.
* Scoped strictly by type_id.

---

# 9) Archive Version

**Endpoint**

```
POST /api/content-document-types/{type_id}/versions/{document_id}/archive
```

No body required.

---

## Archive Rules

* Idempotent if already archived.
* Automatically deactivates if active.
* Cannot archive non-existing version.
* Cannot archive cross-type version.

---

# 10) Lifecycle State Model

```
Draft → Published → Active
Active → Inactive (Deactivate)
Inactive → Active
Published → Archived
Draft → Archived
Active → Archived
```

Invalid transitions return 422.

---

# 11) Full Implementation Checklist (Versions Specific)

### LIST

* [ ] Always include type_id in route
* [ ] Never send sort
* [ ] Only send supported filters
* [ ] Use proper date format

---

### CREATE

* [ ] Send real booleans
* [ ] Respect version format
* [ ] Handle 422 errors

---

### PUBLISH

* [ ] No request body
* [ ] Confirm before calling

---

### ACTIVATE

* [ ] No request body
* [ ] Expect silent success if already active

---

### DEACTIVATE

* [ ] No request body
* [ ] Expect silent success if already inactive

---

### ARCHIVE

* [ ] No request body
* [ ] Confirm destructive action

---

# 12) Runtime Failure Scenarios

| Error | Cause                        |
|-------|------------------------------|
| 422   | Invalid version format       |
| 422   | Activate unpublished version |
| 404   | Wrong type_id scope          |
| 403   | Missing permission           |
| 200   | Idempotent no-op             |

---

# FINAL AUTHORITATIVE RULE

All lifecycle rules are enforced server-side.

The UI:

* Must not assume state
* Must not bypass scope
* Must not infer transitions
* Must treat API responses as authoritative

No client-side lifecycle reconstruction allowed.

No state guessing.

No optimistic activation logic.

---
