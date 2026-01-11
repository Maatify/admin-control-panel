# API Documentation — Phase 1

Project: Admin Control Panel
Phase: Infrastructure & Core Security
Audience: Frontend Developers, QA
Auth Model: Session-based (Cookie) + Step-Up (TOTP)

---

## 🔒 Global Conventions

### Authentication & Sessions
The system uses **server-side sessions** identified by a secure, HttpOnly cookie (`auth_token`).
*   **Web requests:** The cookie is managed automatically by the browser.
*   **API requests:** The cookie must be included in the request headers (handled by browser or manually if outside browser context).
*   **CSRF:** Phase 1 relies on strict `SameSite=Strict` cookie attributes.

### Step-Up / TOTP
Sensitive actions require an elevated session state.
*   If a user is authenticated but not "stepped up" (i.e., pending 2FA), they will receive a **403 Forbidden** response with a specific error reason (e.g., `STEP_UP_REQUIRED`).
*   The user must complete the `/auth/step-up` or `/2fa/verify` flow to elevate their session.

### Error Handling
Errors are returned as JSON (for API) or Twig views (for Web).
Common HTTP Status Codes:
*   **200 OK:** Success.
*   **201 Created:** Resource created successfully.
*   **204 No Content:** Success, no body returned.
*   **302 Found:** Redirect (Web flows).
*   **400 Bad Request:** Invalid input parameters.
*   **401 Unauthorized:** Not logged in or session expired.
*   **403 Forbidden:** Logged in but insufficient permission or step-up required.
*   **422 Unprocessable Entity:** Validation failure (e.g., invalid OTP).
*   **500 Internal Server Error:** Unexpected system failure.

---

## 🔒 Canonical LIST / QUERY Contract (LOCKED)

This section defines the **ONLY VALID contract** for
POST-based `/api/{resource}/query` endpoints
implemented via the Canonical Query pipeline
(DTO + Capabilities + Reader).

It does NOT apply to legacy GET list endpoints
or helper / selector APIs.

### Canonical Request Model
```json
{
  "page": 1,
  "per_page": 20,
  "search": {
    "global": "text",
    "columns": {
      "alias": "value"
    }
  },
  "date": {
    "from": "YYYY-MM-DD",
    "to": "YYYY-MM-DD"
  }
}
```

### Field Semantics (Authoritative)

| Field      | Type         | Required | Notes                                   |
|------------|--------------|----------|-----------------------------------------|
| `page`     | Integer (≥1) | Yes      | Controls server-side OFFSET calculation |
| `per_page` | Integer      | Yes      | Default = 20. Controls LIMIT            |
| `search`   | Object       | Optional | See **Search Contract** below           |
| `date`     | Object       | Optional | See **Date Contract** below             |

---

### 🔍 Search Contract (Canonical & Locked)

`search` **MUST be omitted entirely** if the client has no filters.

If present, **it must satisfy** all of the following:

✔️ `search.global` **OR** `search.columns` **MUST exist** (one or both)
✔️ `search.global` MUST be a **string** if present
✔️ `search.columns` MUST be an **object of aliases → string filters** if present

❌ Empty search blocks are forbidden:

```json
{ "search": {} } // ❌ INVALID (no global or columns)
```

Valid examples:

```json
{ "search": { "global": "alice" } }              // ✔️ global-only
{ "search": { "columns": { "email": "alice" } } } // ✔️ columns-only
{ "search": { "global": "alice", "columns": { "email": "alice" } } } // ✔️ both
```

`search.columns` **uses ALIASES ONLY** — never raw DB column names.

---

### 📅 Date Contract (Canonical & Locked)

`date` **MUST be omitted entirely** if empty.

If present, it MUST include both:

| Key    | Type              | Required |
|--------|-------------------|----------|
| `from` | Date (YYYY-MM-DD) | Yes      |
| `to`   | Date (YYYY-MM-DD) | Yes      |

❌ Partial date blocks are forbidden:

```json
{ "date": { "from": "2024-01-01" } } // ❌ INVALID (missing `to`)
```

---

### Mandatory Rules

* `page`: Integer ≥ 1.
* `per_page`: Integer, default = 20.
* `search` and `date`: **OPTIONAL**, but **MUST satisfy their contracts if present**.
* `search.columns`: **ALIASES ONLY**.
* **No dynamic filters**: clients may not send arbitrary SQL columns.
* **Server-side only** filtering & pagination.

---

### Explicitly Forbidden (NON-NEGOTIABLE)

The following request or response shapes are **STRICTLY FORBIDDEN**:

* ❌ `filters`
* ❌ `limit`
* ❌ `items` / `meta`
* ❌ `from_date` / `to_date`
* ❌ client-side pagination
* ❌ client-side filtering
* ❌ undocumented or dynamic keys

Any usage of the above is a **Canonical Violation**.

---

### Canonical Pagination Semantics

* **LIMIT**  = `:per_page`
* **OFFSET** = `(:page - 1) * :per_page`
* **total**  = filtered total count

Pagination is **SERVER-SIDE ONLY** and applies to **ALL**
Canonical LIST / QUERY endpoints.

---

### Canonical Response Envelope

```json
{
  "data": [],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 0
  }
}
```

---

## 🔐 Authentication

### Web Login (Form)
Renders the login page.

**Endpoint:** `GET /login`
**Auth Required:** No (Guest)

### Perform Login (Web)
Submits credentials to establish a session.

**Endpoint:** `POST /login`
**Auth Required:** No (Guest)

**Parameters (Form Data):**
*   `email` (required): Admin email address.
*   `password` (required): Admin password.

**Response:**
*   **Success (302):** Redirects to `/dashboard` (or `/verify-email` if unverified). Sets `auth_token` and `remember_me` cookies.
*   **Error (200):** Renders login page with error message.

### API Login
JSON-based login for API clients.

**Endpoint:** `POST /api/auth/login`
**Auth Required:** No (Guest)

**Parameters (JSON Body):**
*   `email` (required): Admin email address.
*   `password` (required): Admin password.

**Response:**
*   **Success (200):**
    ```json
    {
      "token": "session_token_string",
      "expires_at": "timestamp"
    }
    ```
*   **Error (401):** Invalid credentials.

### Logout
Terminates the session and revokes tokens.

**Endpoint:** `POST /logout`
**Auth Required:** Yes
**Permission:** None (Authenticated only)

**Response:**
*   **Success (302):** Redirects to `/login`. Clears cookies.

### Remember-Me Behavior
Remember-Me is an **optional login convenience** that persists a session across browser restarts.

*   **Behavior:**
    *   It does **NOT** bypass authentication or 2FA / Step-Up.
    *   It does **NOT** restore a fully trusted session.
*   **Session State:**
    *   Sessions restored via Remember-Me always start in `PENDING_STEP_UP`.
    *   Access to the dashboard is blocked until TOTP verification is completed.
*   **Security:**
    *   Remember-Me ≠ Session
    *   Remember-Me ≠ Step-Up

---

## 📧 Email Verification

### Verify Email Page
Renders the email verification form.

**Endpoint:** `GET /verify-email`
**Auth Required:** No (Guest)

### Submit Verification
Verifies the email using an OTP.

**Endpoint:** `POST /verify-email`
**Auth Required:** No (Guest)

**Parameters (Form Data):**
*   `email` (required): Admin email address.
*   `otp` (required): The verification code sent via email.

**Response:**
*   **Success (302):** Redirects to `/login`.
*   **Error (200):** Renders page with error message.

### Resend Verification Code
Triggers a new OTP email.

**Endpoint:** `POST /verify-email/resend`
**Auth Required:** No (Guest)

**Parameters (Form Data):**
*   `email` (required): Admin email address.

**Response:**
*   **Success (302):** Redirects to `/verify-email` with a generic "sent" message.

### API Verify Email (Admin Context)
**Force-verifies** an email for a specific admin ID (privileged).
This is an administrative override and does **NOT** require an OTP.

**Endpoint:** `POST /api/admins/{id}/emails/verify`
**Permission:** `email.verify`

**Parameters:** None.

**Response:**
*   **Success (200):** JSON confirmation (`{ "status": "verified" }` or similar).
*   **Error (404):** Admin not found.

### API Lookup Email
Resolves an admin ID from an email (Privileged/Internal).

**Endpoint:** `POST /api/admin-identifiers/email/lookup`
**Permission:** `email.lookup`

**Parameters (JSON Body):**
*   `email` (required): Admin email address.

**Response:**
*   **Success (200):** Returns admin ID details.

---

## 🔑 TOTP / Step-Up

### Setup Page
Renders the 2FA setup page (QR code / Secret).

**Endpoint:** `GET /2fa/setup`
**Auth Required:** Yes

### Submit Setup
Verifies the initial code to enable 2FA.

**Endpoint:** `POST /2fa/setup`
**Auth Required:** Yes

**Parameters (Form Data):**
*   `code` (required): OTP from authenticator app.
*   `secret` (required): The secret being set up.

**Response:**
*   **Success (302):** Redirects to dashboard/profile.
*   **Error (200):** Renders page with error.

### Verify Page (Step-Up)
Renders the step-up verification form.

**Endpoint:** `GET /2fa/verify`
**Auth Required:** Yes

### Submit Verification (Web)
Submits OTP to elevate session.

**Endpoint:** `POST /2fa/verify`
**Auth Required:** Yes

**Parameters (Form Data):**
*   `code` (required): OTP from authenticator app.

**Response:**
*   **Success (302):** Redirects to the intended destination.
*   **Error (200):** Renders page with error.

### API Step-Up
JSON endpoint to elevate session.

**Endpoint:** `POST /api/auth/step-up`
**Auth Required:** Yes

**Parameters (JSON Body):**
*   `code` (required): OTP from authenticator app.
*   `scope` (optional): Requested scope (default: login).

**Response:**
*   **Success (200):** JSON confirmation.
*   **Error (422):** Invalid OTP or Missing parameters.

---

## 📢 Notifications

### Connect Telegram (Web)
Renders instructions to link Telegram.

**Endpoint:** `GET /notifications/telegram/connect`
**Auth Required:** Yes

### Telegram Webhook (System)
Inbound webhook from Telegram Bot. **Machine-to-Machine only.**

**Endpoint:** `POST /webhooks/telegram`
**Auth Required:** No (Signature/Logic validation internal)

**Parameters (JSON Payload):**
*   Standard Telegram Update object.
*   Must contain `/start <OTP>` message.

**Response:**
*   **200 OK** (Always, to satisfy Telegram API).

### List Admin Notifications (API)
Retrieves notification history for the authenticated admin.

**Endpoint:** `GET /api/admins/{admin_id}/notifications`
**Auth Required:** Yes (Strictly scoped to `{admin_id}`)
**Permission:** `admin.notifications.history` (implied scope check)

### ⚠️ Legacy / Non-Canonical Endpoint

This endpoint predates the Canonical LIST / QUERY Contract.

- Uses legacy pagination and filtering keys (`limit`, `from_date`, `to_date`)
- Returns legacy response shape (`items`, `meta` likely)
- Does NOT use the Canonical Query pipeline

This endpoint MUST NOT be used as a reference
for implementing new LIST / QUERY APIs.

**Query Parameters:**
*   `page` (int, default 1)
*   `limit` (int, default 20)
*   `notification_type` (string, optional)
*   `is_read` (boolean, optional)
*   `from_date` (Y-m-d, optional)
*   `to_date` (Y-m-d, optional)

**Response:**
*   **Success (200):** JSON Array/Object representing history.
*   **Error (403):** If requesting another admin's history.

### Mark as Read (API)
Marks a specific notification as read.

**Endpoint:** `POST /api/admin/notifications/{id}/read`
**Permission:** `admin.notifications.read` (Scoped to self)

**Response:**
*   **Success (204):** No content.

### Get Notification Preferences
Retrieves the current notification channel preferences for an admin.

**Endpoint:** `GET /api/admins/{admin_id}/preferences`
**Permission:** `admin.preferences.read`

**Response:**
*   **Success (200):** List of preferences.

### Update Notification Preferences
Enables or disables a specific notification channel.

**Endpoint:** `PUT /api/admins/{admin_id}/preferences`
**Permission:** `admin.preferences.write`

**Parameters (JSON Body):**
*   `notification_type` (string, required)
*   `channel_type` (string, required)
*   `is_enabled` (boolean, required)

**Response:**
*   **Success (200):** Updated preference object.

### Global Notifications Query (Legacy / System)
**WARNING:** Privileged endpoint. Allows global queries.

**Endpoint:** `GET /api/notifications`
**Auth Required:** Yes (High Privilege Required)
**Permission:** `notifications.list`

**Query Parameters:**
*   `admin_id` (optional): Filter by admin.
*   `status` (optional): Filter by status.
*   `channel` (optional): Filter by channel.
*   `from` (optional): Start date (YYYY-MM-DD).
*   `to` (optional): End date (YYYY-MM-DD).

**Response:**
*   **Success (200):** List of notification summaries.

---

## 🩺 System & UI Helpers

### Health Check
Simple status check.

**Endpoint:** `GET /health`
**Auth Required:** No

**Response:**
*   **Success (200):**
    ```json
    {"status": "ok"}
    ```

### UI Error Page
Renders a generic error page based on a code.

**Endpoint:** `GET /error`
**Auth Required:** No

**Parameters (Query):**
*   `code` (string, optional): Error code to display.

**Response:**
*   **Success (200):** HTML Page.

---

## 🖥️ UI Pages (Protected)

### Dashboard
Main entry point.

**Endpoint:** `GET /` or `GET /dashboard`
**Auth Required:** Yes
**Permission:** None (Authenticated)

### Admins List (UI)
Renders the admins list page.

**Endpoint:** `GET /admins`
**Auth Required:** Yes
**Permission:** `admins.list`

### Roles List (UI)
Renders the roles list page.

**Endpoint:** `GET /roles`
**Auth Required:** Yes
**Permission:** None (Authenticated)

### Permissions List (UI)
Renders the permissions list page.

**Endpoint:** `GET /permissions`
**Auth Required:** Yes
**Permission:** None (Authenticated)

### Settings Page (UI)
Renders the settings page.

**Endpoint:** `GET /settings`
**Auth Required:** Yes
**Permission:** None (Authenticated)

### Sessions Page (UI)
Renders the sessions management UI.

**Endpoint:** `GET /sessions`
**Auth Required:** Yes
**Permission:** `sessions.list`

### Sandbox / Examples (UI)
Non-canonical sandbox page for testing layouts/components.

**Endpoint:** `GET /examples`
**Auth Required:** Yes
**Permission:** None (Authenticated)

---

## 👥 Admins (API)

### List Admins (Query)
Retrieves a paginated list of admins using the Canonical LIST / QUERY Contract.

**Endpoint:** `POST /api/admins/query`
**Permission:** `admins.query`

This endpoint strictly uses the **Canonical LIST / QUERY Contract (LOCKED)**
defined in this document.

**Request Model:**
> Uses **Canonical LIST / QUERY Contract**.

**Allowed Search Aliases:**
*   `id` (Integer)
*   `email` (String)

**Notes:**
*   **Email Search:** Uses Blind Index (exact match or prefix depending on backend implementation).
*   **Decryption:** Email addresses are decrypted only in the response.

**Response Model:**
> Uses **Canonical Response Envelope**.

### Create Admin (Blank)
Creates a new admin entity with default state.
Does not set email or password (use subsequent calls).

**Endpoint:** `POST /api/admins/create`
**Permission:** `admin.create`

**Parameters:** None.

**Response:**
*   **Success (200):**
    ```json
    {
      "admin_id": 123,
      "created_at": "2024-01-01 12:00:00"
    }
    ```

### Add Email to Admin
Associates an email address with an admin.

**Endpoint:** `POST /api/admins/{id}/emails`
**Permission:** `email.add`

**Parameters (JSON Body):**
*   `email` (required): valid email address.

**Response:**
*   **Success (200):** `{ "admin_id": 123, "email_added": true }`
*   **Error (400):** Invalid email format.

### Get Admin Email
Retrieves the decrypted email address for an admin.

**Endpoint:** `GET /api/admins/{id}/emails`
**Permission:** `email.read`

**Response:**
*   **Success (200):**
    ```json
    {
      "admin_id": 123,
      "email": "admin@example.com"
    }
    ```

---

## 📅 Sessions (API)

### List Sessions (API)
Server-side pagination and filtering for sessions list.

**Endpoint:** `POST /api/sessions/query`
**Permission:** `sessions.list`

This endpoint strictly uses the **Canonical LIST / QUERY Contract (LOCKED)**
defined in this document.

**Request Model:**
> Uses **Canonical LIST / QUERY Contract**.

**Allowed Search Aliases:**
*   `session_id`
*   `status` (active | revoked | expired)
*   `admin_id` (Integer)

**Notes:**
*   **Date Filter:** Applies to `created_at`.
*   **Admin Scope:** Enforced server-side.

**Response Model:**
> Uses **Canonical Response Envelope**.
> Data items contain:
```json
{
  "session_id": "abc123hash...",
  "admin_id": 123,
  "admin_identifier": "admin@example.com",
  "created_at": "2024-01-01 10:00:00",
  "expires_at": "2024-01-02 10:00:00",
  "status": "active",
  "is_current": true
}
```

### Revoke Session (Single)
Revokes a specific session by ID (Hash).

**Endpoint:** `DELETE /api/sessions/{session_id}`
**Auth Required:** Yes (Permission `sessions.revoke`)

**Response:**
*   **Success (200):** JSON confirmation.
*   **Error (400):** Invalid ID or Attempt to revoke current session.
*   **Error (404):** Session not found.

### Bulk Revoke Sessions
Revokes multiple sessions in a single transaction.

**Endpoint:** `POST /api/sessions/revoke-bulk`
**Auth Required:** Yes (Permission `sessions.revoke`)

**Request Model:**
```json
{
  "session_ids": [
    "hash1...",
    "hash2..."
  ]
}
```

**Response:**
*   **Success (200):** JSON confirmation.
*   **Error (400):** If current session is included in the list.

### Select Admins (Helper / Non-Canonical)
**⚠️ REMOVED / STALE**
This endpoint is currently disabled in the codebase.
Use `POST /api/admins/query` instead.

**Endpoint:** `GET /api/admins/list`
