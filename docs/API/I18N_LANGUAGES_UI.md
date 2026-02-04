# 🌍 Languages Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / I18n`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for the Languages UI.

It answers, precisely:

* What the UI is allowed to send
* How global search and filters actually work
* What each endpoint requires vs what is optional
* What response shapes exist (success + failure)
* Why you are getting `422` / runtime exceptions

If something is not documented here, treat it as **not supported**.

### ⚠️ CRITICAL: UI vs API Distinction

You must understand the difference between the **UI Page** and the **API**:

*   **`GET /languages`**
    *   ❌ **This is NOT an API.**
    *   ✅ This is the **browser entry point** that renders the HTML page.
    *   It returns `text/html`.
    *   Do not call this from JavaScript fetch/axios.

*   **`POST /api/languages/*`**
    *   ✅ **These ARE the APIs.**
    *   They return `application/json` (or empty 200).
    *   All programmatic interaction happens here.

---

## 1) Hard rules (no debate)

### 1.1 Canonical LIST/QUERY contract is mandatory

All list/table screens in this project use the canonical request/response envelope defined in `docs/API.md`.

That means:

* ✅ `page` (int)
* ✅ `per_page` (int)
* ✅ `search` (object, optional)
* ✅ `date` (object, optional; must include `from` and `to` together)
* ✅ response envelope: `{ data: [], pagination: {...} }`

And it also means:

* ❌ `limit` is forbidden (use `per_page`)
* ❌ `filters` is forbidden (use `search.columns`)
* ❌ `items` / `meta` is forbidden (use `data` / `pagination`)

---

### 1.2 Never send `null` for “optional” fields

This backend treats **`null` as an explicit value**, not “not provided”.

So:

* ✅ omit the key entirely if the user didn’t select a value
* ✅ send a real typed value
* ❌ do not send `null`

**Example (wrong):**

```json
{ "fallback_language_id": null }
```

**Example (correct if unset):**

```json
{ }
```

**Example (correct if set):**

```json
{ "fallback_language_id": 1 }
```

---

### 1.3 Runtime error semantics (this is the real behavior)

**Important:** This project does **NOT** use one uniform “always 200 with error object” pattern.

Your actual runtime middleware/handlers enforce this:

#### 1.3.1 LIST/QUERY endpoints

* ✅ Typically return **200 + JSON** envelope

#### 1.3.2 MUTATION / ACTION endpoints (Create, Update, Delete)

* ✅ Return **200 OK with EMPTY BODY** (no JSON payload)
* ❌ Do **NOT** try to parse JSON from these responses.
* UI must treat **empty body** as success when status is `200`.

#### 1.3.3 Validation failures

* ❗ Return **422** (`ValidationFailedException`)

Payload shape:

```json
{
  "error": "Invalid request payload",
  "errors": {
    "field": "reason"
  }
}
```

#### 1.3.4 Auth / Permission / Domain errors

* Can return **401 / 403 / 404 / 409** with this payload shape:

```json
{
  "message": "...",
  "code": "..."
}
```

#### 1.3.5 UI must implement robust parsing

UI must **NOT** do `await response.json()` blindly.

Correct pattern:

```js
const raw = await response.text();

// 200 + empty body = success
if (response.status === 200 && (!raw || raw.trim() === '')) {
  return { ok: true };
}

// If body exists, parse JSON
let data = null;
try {
  data = raw ? JSON.parse(raw) : null;
} catch (e) {
  // Could be HTML error page or invalid JSON
  return { ok: false, status: response.status, raw };
}

// Non-200 is failure
if (!response.ok) {
  return { ok: false, status: response.status, data };
}

// 200 with JSON (query endpoints)
return { ok: true, data };
```

---

## 2) Page architecture (UI perspective)

```
Twig Controller
  ├─ injects capabilities
  ├─ renders languages page
  └─ includes JS bundle

JavaScript
  ├─ DataTable (query + pagination)
  ├─ Modals (create, update settings)
  └─ Actions (toggle active, fallback, update name/code/sort)

API (authoritative)
  ├─ validates request schema
  ├─ applies query resolver rules
  └─ returns canonical envelope (queries) or empty 200 (actions)
```

Core rule:

> UI never implements business logic.
> UI only sends valid contracts and renders results.

---

## 3) Capabilities (Authorization Contract)

Authorization is **always server-side**.
The UI receives **explicit capability flags** injected by the UI controller.

### 3.1 Injection Source (Server)

> This is the canonical capability set for the Languages page.

```php
$capabilities = [
    'can_create'         => $this->authorizationService->hasPermission($adminId, 'languages.create.api'),
    'can_update'         => $this->authorizationService->hasPermission($adminId, 'languages.update.settings.api'),
    'can_update_name'    => $this->authorizationService->hasPermission($adminId, 'languages.update.name.api'),
    'can_update_code'    => $this->authorizationService->hasPermission($adminId, 'languages.update.code.api'),
    'can_update_sort'    => $this->authorizationService->hasPermission($adminId, 'languages.update.sort.api'),
    'can_active'         => $this->authorizationService->hasPermission($adminId, 'languages.set.active.api'),
    'can_fallback_set'   => $this->authorizationService->hasPermission($adminId, 'languages.set.fallback.api'),
    'can_fallback_clear' => $this->authorizationService->hasPermission($adminId, 'languages.clear.fallback.api'),
];
```

### 3.2 Exposed to UI (Global JS)

```js
window.languagesCapabilities = {
  can_create: true|false,
  can_update: true|false,
  can_update_name: true|false,
  can_update_code: true|false,
  can_update_sort: true|false,
  can_active: true|false,
  can_fallback_set: true|false,
  can_fallback_clear: true|false
};
```

### 3.3 Capability → UI Behavior Mapping

| Capability           | UI Responsibility                                      |
|----------------------|--------------------------------------------------------|
| `can_create`         | Show/hide **Create Language** button                   |
| `can_update`         | Enable/disable **settings edit** (direction/icon only) |
| `can_update_name`    | Enable/disable **update name** UI                      |
| `can_update_code`    | Enable/disable **update code** UI                      |
| `can_update_sort`    | Enable/disable **sort order** controls                 |
| `can_active`         | Enable/disable **active toggle**                       |
| `can_fallback_set`   | Allow selecting fallback                               |
| `can_fallback_clear` | Allow clearing fallback                                |

### 3.4 Hard Rules

* ❌ UI must **never** infer permissions
* ❌ UI must **never** hardcode permission names
* ❌ UI must **never** call an endpoint if its capability flag is false
* ✅ Backend still enforces authorization even if UI is bypassed

---

# 4) List Languages (table)

## Endpoint

```http
POST /api/languages/query
```

## Capability

* Requires authenticated session
* UI should only render table if `languagesCapabilities.can_query === true` (if provided)

## Request — Canonical LIST/QUERY model

### ✅ Minimum valid request

```json
{
  "page": 1,
  "per_page": 25
}
```

### ✅ Global search and column filters can be combined

> **Important rule:** `search.global` and `search.columns` **can be sent together in the same request**.
>
> This allows the UI to:
>
> * Apply **explicit column filters** first (e.g. code, direction, active status)
> * Then perform a **global search** within the already filtered result set
>
> In other words:
> **Global search is applied on top of the column filters, not instead of them.**

Example:

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "en",
    "columns": {
      "name": "English",
      "is_active": "1"
    }
  }
}
```

### Global search

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "global": "en"
  }
}
```

**Global search meaning (backend-defined):**

* Free text search
* Matches against (current implementation): **name OR code**

### Column filters

```json
{
  "page": 1,
  "per_page": 25,
  "search": {
    "columns": {
      "code": "en",
      "direction": "ltr",
      "is_active": "1"
    }
  }
}
```

### Supported Column Aliases

> **Note:** Boolean-like values must be sent as **strings**, not actual booleans.
> Use `"1"` for `true` and `"0"` for `false`.

| Alias       | Type   | Example | Semantics         |
|-------------|--------|---------|-------------------|
| `name`      | string | `"Eng"` | `LIKE %value%`    |
| `code`      | string | `"en"`  | exact match       |
| `is_active` | string | `"1"`   | cast to int (1/0) |
| `direction` | string | `"ltr"` | `ltr` / `rtl`     |

### Sorting

> ⚠️ **SERVER-CONTROLLED SORTING**

*   ❌ Clients **MUST NOT** send `sort` parameters.
*   ❌ `sort_order` is **ignored** in the request.
*   ✅ The server enforces strict ordering: `ORDER BY language_settings.sort_order ASC, languages.id ASC`.

To change the order, you must use the dedicated **Update Language Sort Order** endpoint.

## Response — 200 OK (canonical envelope)

```json
{
  "data": [
    {
      "id": 1,
      "name": "English",
      "code": "en",
      "direction": "ltr",
      "sort_order": 1,
      "icon": "🇬🇧",
      "is_active": true,
      "fallback_language_id": 2
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

### Pagination meanings

* `total`: total records in DB (no filters)
* `filtered`: total records after applying `search.global` and/or `search.columns`

> When no filters are applied, `filtered` MAY equal `total`.

---

# 5) Create Language

## Endpoint

```http
POST /api/languages/create
```

## Capability

* `languagesCapabilities.can_create` must be `true` to show the create button.

## Request body

### Required fields

* `name` (string)
* `code` (string)
* `direction` (`"ltr" | "rtl"`)

### Optional fields (MUST NOT be null)

* `icon` (string)
* `is_active` (boolean, default: true if omitted)
* `fallback_language_id` (int)

> **Important:** `sort_order` is **NOT** accepted here. New languages are automatically appended to the end of the list.

### ✅ Valid examples

**Minimal:**

```json
{
  "name": "English",
  "code": "en",
  "direction": "ltr"
}
```

**With icon + explicit active:**

```json
{
  "name": "English",
  "code": "en",
  "direction": "ltr",
  "is_active": true,
  "icon": "🇬🇧"
}
```

**With fallback:**

```json
{
  "name": "Arabic",
  "code": "ar",
  "direction": "rtl",
  "fallback_language_id": 1
}
```

### ❌ Invalid example

```json
{
  "name": "English",
  "code": "en",
  "direction": "ltr",
  "fallback_language_id": null
}
```

## Response

*   ✅ **200 OK (EMPTY BODY)**
*   The ID of the created language is not returned. The UI should refresh the list.
*   ❌ **422** on schema validation failure (e.g. Code already exists)

---

# 6) Update Language Settings (direction + icon only)

> **Sort is NOT updated here.**

## Endpoint

```http
POST /api/languages/update-settings
```

## Capability

* `languagesCapabilities.can_update`

## Request body

### Required

* `language_id` (int)

### Optional (MUST NOT be null)

* `direction` (`"ltr" | "rtl"`)
* `icon` (string)

### Clearing icon

Because `null` is forbidden:

* To **keep** icon unchanged → omit `icon`
* To **set/update** icon → send a non-empty string
* To **clear** icon → send `""` (empty string)

Example (clear icon):

```json
{
  "language_id": 2,
  "icon": ""
}
```

## Response

* ✅ **200 OK (EMPTY BODY)**
* ❌ **422** on schema validation failure
* ❌ **404 / 409 / 403** on domain errors / permissions

---

# 7) Update Language Sort Order

> This is the **ONLY** way to change the sort order of languages.

## Endpoint

```http
POST /api/languages/update-sort
```

## Capability

* `languagesCapabilities.can_update_sort`

## Request body

### Required

* `language_id` (int)
* `sort_order` (int, min 1)

Example:

```json
{
  "language_id": 2,
  "sort_order": 1
}
```

## Behavior

* Moves language to a new sort position
* Shifts other rows accordingly (transactional)

## Response

* ✅ **200 OK (EMPTY BODY)**

---

# 8) Update Language Name

## Endpoint

```http
POST /api/languages/update-name
```

## Capability

* `languagesCapabilities.can_update_name`

## Request body

### Required

* `language_id` (int)
* `name` (string)

Example:

```json
{
  "language_id": 2,
  "name": "English (UK)"
}
```

## Response

* ✅ **200 OK (EMPTY BODY)**

---

# 9) Update Language Code

## Endpoint

```http
POST /api/languages/update-code
```

## Capability

* `languagesCapabilities.can_update_code`

## Request body

### Required

* `language_id` (int)
* `code` (string)

Example:

```json
{
  "language_id": 2,
  "code": "en-GB"
}
```

## Response

* ✅ **200 OK (EMPTY BODY)**

---

# 10) Toggle Active

## Endpoint

```http
POST /api/languages/set-active
```

## Capability

* `languagesCapabilities.can_active`

## Request body

* `language_id` (int)
* `is_active` (boolean)

Example:

```json
{
  "language_id": 2,
  "is_active": false
}
```

## Response

* ✅ **200 OK (EMPTY BODY)**
* ❌ **409** if business rule prevents change

---

# 11) Set Fallback

## Endpoint

```http
POST /api/languages/set-fallback
```

## Capability

* `languagesCapabilities.can_fallback_set`

## Conceptual Logic: What is a Fallback?

*   There is **only ONE** fallback language in the entire system at any given time.
*   Setting a language as the fallback **automatically unsets** any previous fallback.
*   **Purpose:** If a user requests a translation key that is missing in their current language, the system falls back to this language.
*   **Constraint:** You cannot deactivate the fallback language.

## Request body

### Required

* `language_id` (int)

### Optional (MUST NOT be null)

* `fallback_language_id` (int)

**Example:**

To set Language ID `1` (e.g. English) as the fallback for Language ID `2`:

```json
{
  "language_id": 2,
  "fallback_language_id": 1
}
```

## Response

* ✅ **200 OK (EMPTY BODY)**

---

# 12) Clear Fallback

## Endpoint

```http
POST /api/languages/clear-fallback
```

## Capability

* `languagesCapabilities.can_fallback_clear`

## Logic

*   Removes the fallback status from the language.
*   After this, **no language** is the fallback.
*   Missing translations may return keys instead of text.

## Request body

* `language_id` (int)

Example:

```json
{
  "language_id": 2
}
```

## Response

* ✅ **200 OK (EMPTY BODY)**

---

# 13) Why you got 422 on `/api/languages/query`

Your UI logged this request:

```json
{
  "page": 1,
  "limit": 25,
  "sort": {
    "field": "sort_order",
    "direction": "asc"
  }
}
```

This fails the canonical schema because:

* `limit` is forbidden → must be `per_page`
* `sort` is **forbidden** → sorting is server-controlled

✅ Fix (minimum valid):

```json
{
  "page": 1,
  "per_page": 25
}
```

---

# 14) Debugging & Console Logging (UI requirement)

To debug schema and runtime issues, always log:

```js
console.log('📤 [Languages] Request:', payload);

const status = response.status;
const raw = await response.text();
console.log('📡 [Languages] Status:', status);
console.log('📄 [Languages] Raw Response:', raw);

if (raw && raw.trim() !== '') {
  try {
    const data = JSON.parse(raw);
    console.log('✅ [Languages] Parsed JSON:', data);
  } catch (e) {
    console.error('❌ [Languages] JSON parse failed:', e);
  }
}
```

---

# 15) UI implementation checklist

## DataTable request builder

* [ ] always send `page` and `per_page`
* [ ] never send `limit`
* [ ] never send `filters`
* [ ] allow combining `search.global` and `search.columns`
* [ ] use `search.global` for global search
* [ ] use `search.columns.{alias}` for column filters
* [ ] never send any key with `null` value
* [ ] **never send `sort`** to `/api/languages/query`

## Modals / Actions

* [ ] build payload by omitting empty optional fields
* [ ] normalize booleans (`true/false`) not strings
* [ ] treat **200 + empty body** as success for update/action endpoints
* [ ] show errors from:

    * `422` payload (`error` + `errors`)
    * `401/403/404/409` payload (`message` + `code`)

---

# 16) Reference

The binding canonical rules live in `docs/API.md`.

✅ **Status:** LOCKED — Languages UI & API Integration Contract
