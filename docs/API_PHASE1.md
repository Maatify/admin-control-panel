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

**Endpoint:** `POST /auth/login`
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
Verifies an email for a specific admin ID (privileged).

**Endpoint:** `POST /admins/{id}/emails/verify`
**Auth Required:** Yes (Permission Required)

**Parameters (JSON Body):**
*   `otp` (required): The verification code.

**Response:**
*   **Success (200):** JSON confirmation.
*   **Error (422):** Invalid OTP.

### API Lookup Email
Resolves an admin ID from an email (Privileged/Internal).

**Endpoint:** `POST /admin-identifiers/email/lookup`
**Auth Required:** Yes (Permission Required)

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

**Endpoint:** `POST /auth/step-up`
**Auth Required:** Yes

**Parameters (JSON Body):**
*   `otp` (required): OTP from authenticator app.

**Response:**
*   **Success (200):** JSON confirmation.
*   **Error (422):** Invalid OTP.

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

**Endpoint:** `GET /admins/{admin_id}/notifications`
**Auth Required:** Yes (Strictly scoped to `{admin_id}`)

**Query Parameters:**
*   `page` (int, default 1)
*   `limit` (int, default 20)
*   `notification_type` (string, optional)
*   `is_read` (boolean, optional)
*   `from_date` (Y-m-d, optional)
*   `to_date` (Y-m-d, optional)

**Response:**
*   **Success (200):**
    ```json
    {
      "items": [...],
      "meta": { "total": ... }
    }
    ```
*   **Error (403):** If requesting another admin's history.

### Mark as Read (API)
Marks a specific notification as read.

**Endpoint:** `POST /admin/notifications/{id}/read`
**Auth Required:** Yes (Strictly scoped)

**Response:**
*   **Success (204):** No content.

### Global Notifications Query (Legacy / System)
**WARNING:** Privileged endpoint. Allows global queries.

**Endpoint:** `GET /notifications`
**Auth Required:** Yes (High Privilege Required)

**Query Parameters:**
*   `admin_id` (optional): Filter by admin.
*   `status` (optional): Filter by status.
*   `channel` (optional): Filter by channel.

**Response:**
*   **Success (200):** List of notification summaries.

---

## 🩺 System

### Health Check
Simple status check.

**Endpoint:** `GET /health`
**Auth Required:** No

**Response:**
*   **Success (200):**
    ```json
    {"status": "ok"}
    ```

---

## 📅 Sessions

### Sessions Page (Web)
Renders the sessions management UI with filtering and bulk actions.

**Endpoint:** `GET /sessions`
**Auth Required:** Yes

### List Sessions (API)
Server-side pagination and filtering for sessions list.

**Endpoint:** `POST /api/sessions/query`
**Auth Required:** Yes (Permission `sessions.list`)

**Request Model:**
```json
{
  "page": 1,
  "per_page": 20,
  "filters": {
    "session_id": "optional_id_fragment",
    "admin_id": 123, // Optional (Requires sessions.view_all)
    "status": "active|revoked|expired|all"
  }
}
```

**Response Model:**
```json
{
  "data": [
    {
      "session_id": "abc123hash...",
      "admin_id": 123,
      "admin_identifier": "admin@example.com",
      "created_at": "2024-01-01 10:00:00",
      "expires_at": "2024-01-02 10:00:00",
      "status": "active",
      "is_current": true
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 50
  }
}
```

**Notes:**
*   Pagination is mandatory.
*   Status is derived on backend.
*   `admin_id` filter is permission-gated (ignored if user lacks `sessions.view_all`).
*   `is_current` indicates the session executing the request.

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

### List Admins (Filter Helper)
Retrieves a list of admins for UI filtering.

**Endpoint:** `GET /api/admins/list`
**Auth Required:** Yes (Permission `sessions.view_all`)

**Response:**
*   **Success (200):**
    ```json
    {
      "data": [
        { "id": 1, "identifier": "admin@example.com" },
        { "id": 2, "identifier": "other@example.com" }
      ]
    }
    ```

### Create Admin
Creates a new system administrator with email and password.

**Endpoint:** `POST /api/admins/create`
**Auth Required:** Yes (Permission `admins.create`)

**Request Model:**
```json
{
  "email": "new.admin@example.com",
  "password": "SecurePassword123!"
}
```

**Response Model:**
```json
{
  "id": 123,
  "created_at": "2024-01-01 12:00:00"
}
```

**Notes:**
*   Email must be unique.
*   Password must be at least 8 characters.
*   The new admin is created with a verified email address.
*   TOTP/2FA is not set up automatically; the new admin must set it up on first login (pending step-up).
