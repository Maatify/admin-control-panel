# UI Runtime Integration Rules

**Project:** `maatify/admin-control-panel`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**
**Parent Contract:** `docs/API.md`

---

## 0) Why this document exists

This file defines the **shared runtime integration contract** for all Admin Panel UI pages.
It acts as the "Frontend Developer's Guide" to the backend contracts defined in `docs/API.md`.

It answers:
*   How to consume the Canonical LIST/QUERY contract from JavaScript.
*   How to handle `null` vs `undefined` in payloads.
*   How to parse responses (JSON vs Empty Body).
*   How to handle runtime errors (`422`, `403`, etc.).

---

## 1) Canonical LIST/QUERY Consumption

All list/table screens in this project use the canonical request/response envelope defined in `docs/API.md`.

### 1.1 Mandatory Request Shape

*   ✅ `page` (int)
*   ✅ `per_page` (int)
*   ✅ `search` (object, optional)
*   ✅ `date` (object, optional; must include `from` and `to` together)

### 1.2 Forbidden Keys (Strict)

*   ❌ `limit` is forbidden (use `per_page`)
*   ❌ `filters` is forbidden (use `search.columns`)
*   ❌ `items` / `meta` is forbidden (use `data` / `pagination`)
*   ❌ `sort` / `sort_order` is forbidden (unless explicitly supported by a legacy endpoint; Canonical endpoints are server-sorted or use specific mutation endpoints).

### 1.3 Search Semantics

*   **Global Search** (`search.global`): Applied on top of column filters.
*   **Column Filters** (`search.columns`): Applied first.
*   **Combination**: You can send both `search.global` and `search.columns` in the same request.

---

## 2) Null Handling (Payloads)

### 2.1 Never send `null` for "optional" fields

The backend treats **`null` as an explicit value**, not "not provided".

*   ✅ **Omit the key entirely** if the user didn't select a value.
*   ✅ **Send a real typed value** if set.
*   ❌ **Do NOT send `null`**.

**Example (wrong):**
```json
{ "fallback_id": null }
```

**Example (correct if unset):**
```json
{ }
```

**Example (correct if set):**
```json
{ "fallback_id": 1 }
```

---

## 3) Runtime Error Semantics

The project does **NOT** use a uniform "always 200 with error object" pattern.

### 3.1 LIST/QUERY Endpoints

*   ✅ Return **200 OK + JSON** envelope (`data`, `pagination`).

### 3.2 MUTATION / ACTION Endpoints (Create, Update, Delete)

*   ✅ Return **200 OK with EMPTY BODY** (no JSON payload).
*   ❌ Do **NOT** try to parse JSON from these responses.
*   UI must treat **empty body** as success when status is `200`.

### 3.3 Validation Failures

*   ❗ Return **422** (`ValidationFailedException`).

**Payload shape:**
```json
{
  "error": "Invalid request payload",
  "errors": {
    "field_name": "Error reason"
  }
}
```

### 3.4 Auth / Permission / Domain Errors

*   Can return **401 / 403 / 404 / 409**.

**Payload shape:**
```json
{
  "message": "Human readable message",
  "code": "ERROR_CODE_STRING"
}
```

---

## 4) Response Parsing (JavaScript Pattern)

UI must **NOT** do `await response.json()` blindly.

**Correct Pattern:**

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

## 5) Capabilities Pattern (Authorization)

Authorization is **always server-side**.
The UI receives **explicit capability flags** injected by the UI controller.

### 5.1 Hard Rules

*   ❌ UI must **never** infer permissions.
*   ❌ UI must **never** hardcode permission names.
*   ❌ UI must **never** call an endpoint if its capability flag is false.
*   ✅ Backend still enforces authorization even if UI is bypassed.

### 5.2 Typical Usage

*   **`can_create`**: Show/hide "Create" button.
*   **`can_update`**: Enable/disable edit controls.
*   **`can_delete`**: Show/hide delete actions.

---

## 6) Debugging & Console Logging

To debug schema and runtime issues, always use this logging pattern:

```js
console.log('📤 [Request]', payload);

const status = response.status;
const raw = await response.text();
console.log('📡 [Status]', status);
console.log('📄 [Raw Response]', raw);

if (raw && raw.trim() !== '') {
  try {
    const data = JSON.parse(raw);
    console.log('✅ [Parsed JSON]', data);
  } catch (e) {
    console.error('❌ [JSON parse failed]', e);
  }
}
```

---

## 7) UI Implementation Checklist

### DataTable Request Builder

*   [ ] Always send `page` and `per_page`.
*   [ ] Never send `limit`.
*   [ ] Never send `filters` (use `search.columns`).
*   [ ] Never send any key with `null` value.

### Modals / Actions

*   [ ] Build payload by **omitting** empty optional fields.
*   [ ] Normalize booleans (`true`/`false`) not strings (unless specific API requires "1"/"0").
*   [ ] Treat **200 + empty body** as success for update/action endpoints.
*   [ ] Show errors from:
    *   `422` payload (`error` + `errors`)
    *   `401/403/404/409` payload (`message` + `code`)
