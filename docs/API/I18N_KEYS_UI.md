# 🌍 Translation Keys Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / I18n`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the Translation Keys UI.

It answers, precisely:

*   What the UI is allowed to send
*   How global search and filters actually work
*   What each endpoint requires vs what is optional
*   What response shapes exist (success + failure)
*   Why you are getting `422` / runtime exceptions

If something is not documented here, treat it as **not supported**.

### ⚠️ CRITICAL: UI vs API Distinction

You must understand the difference between the **UI Page** and the **API**:

*   **`GET /i18n/keys`**
    *   ❌ **This is NOT an API.**
    *   ✅ This is the **browser entry point** that renders the HTML page.
    *   It returns `text/html`.
    *   Do not call this from JavaScript fetch/axios.

*   **`POST /i18n/keys/*`**
    *   ✅ **These ARE the APIs.**
    *   They return `application/json` (or empty 200/204).
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
  ├─ renders translation keys page
  └─ includes JS bundle

JavaScript
  ├─ DataTable (query + pagination)
  ├─ Modals (create, update name, update description)
  └─ Actions (edit)

API (authoritative)
  ├─ validates request schema
  ├─ applies query resolver rules
  └─ returns canonical envelope (queries) or empty 200/204 (actions)
```

---

## 2) Capabilities (Authorization Contract)

The UI receives these **Translation Keys-specific capability flags**:

### 2.1 Injected Flags

```php
$capabilities = [
    'can_create'             => $hasPermission('i18n.keys.create.api'),
    'can_update_name'        => $hasPermission('i18n.keys.update.name.api'),
    'can_update_description' => $hasPermission('i18n.keys.update.description.api'),
];
```

### 2.2 Capability → UI Behavior Mapping

| Capability               | UI Responsibility                                  |
|--------------------------|----------------------------------------------------|
| `can_create`             | Show/hide **Create Key** button                    |
| `can_update_name`        | Enable/disable **rename** action (key name edit)   |
| `can_update_description` | Enable/disable **update description** action       |

---

## 3) List Translation Keys (table)

**Endpoint:** `POST /i18n/keys/query`
**Capability:** `can_query` (implicit/base)

### Request — Specifics

*   **Global Search:** Matches against **key_name OR description**.
*   **Sorting:** ⚠️ **SERVER-CONTROLLED**.
    *   Clients **MUST NOT** send `sort` parameters.

**Example Request:**

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "welcome",
    "columns": {
      "key_name": "app.home"
    }
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias         | Type   | Example       | Semantics         |
|---------------|--------|---------------|-------------------|
| `key_name`    | string | `"app.home"`  | `LIKE %value%`    |
| `description` | string | `"Main page"` | `LIKE %value%`    |

### Response Model

```json
{
  "data": [
    {
      "key_id": 123,
      "key_name": "app.home.welcome_title",
      "description": "Welcome message on dashboard"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 25,
    "total": 150,
    "filtered": 1
  }
}
```

**Pagination Meanings:**
*   `total`: total records in DB (no filters)
*   `filtered`: total records after applying `search.global` and/or `search.columns`
*   When no filters are applied, `filtered` MAY equal `total`.

---

## 4) Create Translation Key

**Endpoint:** `POST /i18n/keys/create`
**Capability:** `can_create`

### Request Body

*   `key_name` (string, required)
*   `description` (string, optional)

> **Note:** If `description` is omitted, do NOT send `null`.

**Example:**
```json
{
  "key_name": "app.dashboard.welcome",
  "description": "Greeting text for logged-in users"
}
```

### Response

*   ✅ **204 No Content**
*   ❌ **409** if key already exists.

---

## 5) Update Key Name

**Endpoint:** `POST /i18n/keys/update-name`
**Capability:** `can_update_name`

### Request Body

*   `key_id` (int, required)
*   `key_name` (string, required)

**Example:**
```json
{
  "key_id": 123,
  "key_name": "app.dashboard.welcome_message"
}
```

### Response

*   ✅ **200 OK (EMPTY BODY)**
*   ❌ **409** if new key name already exists.

---

## 6) Update Key Description

**Endpoint:** `POST /i18n/keys/update-description`
**Capability:** `can_update_description`

### Request Body

*   `key_id` (int, required)
*   `description` (string, required)

**Example:**
```json
{
  "key_id": 123,
  "description": "Updated context for translators"
}
```

### Response

*   ✅ **200 OK (EMPTY BODY)**

---

## 7) Implementation Checklist (Translation Keys Specific)

*   [ ] **Never send `sort`** to `/i18n/keys/query`.
*   [ ] Handle **204 No Content** for creation.
*   [ ] Handle **200 OK (Empty Body)** for updates.
*   [ ] Refresh list after create/update actions.
