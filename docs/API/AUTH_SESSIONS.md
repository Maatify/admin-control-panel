# Authentication & Sessions — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / Auth`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for Authentication, MFA, and Session Management.

It answers, precisely:
*   How to perform login and step-up (MFA).
*   How to handle password resets (forced change).
*   How to verify emails.
*   How to list and revoke sessions.

### ⚠️ CRITICAL: UI vs API Distinction

*   **`GET /login`, `GET /2fa/verify`**
    *   ❌ **These are NOT APIs.**
    *   ✅ These are **browser entry points** that render HTML pages.

*   **`POST /api/auth/*`**
    *   ✅ **These ARE the APIs.**
    *   They return `application/json`.

> ⚠️ **RUNTIME RULES:**
> This document assumes **mandatory compliance** with the **[UI Runtime Integration Rules](UI_RUNTIME_RULES.md)**.
> Refer to that file for:
> *   Response parsing (JSON vs Empty Body)
> *   Error handling (422/403)
> *   Null handling in payloads
> *   Canonical Query construction

---

## 1) Login Flow

### Web Login (Form)
**Endpoint:** `POST /login`
**Auth Required:** No

Submits credentials to establish a session (Cookie-based).

**Parameters (Form Data):**
*   `email` (required): Admin email.
*   `password` (required): Admin password.

**Response:**
*   **Success (302):** Redirects to `/dashboard` (or `/verify-email`).
*   **Error (200):** Renders page with error message.

---

### API Login
**Endpoint:** `POST /api/auth/login`
**Auth Required:** No

JSON-based login for API clients.

**Request Body:**
```json
{
  "email": "admin@example.com",
  "password": "secret_password"
}
```

**Response (200 OK):**
```json
{
  "token": "session_token_string",
  "expires_at": 1700000000
}
```

**Error (401/403):**
```json
{ "error": "Invalid credentials" }
```

---

## 2) Step-Up / MFA

### Web Step-Up (Form)
**Endpoint:** `POST /2fa/verify`
**Auth Required:** Yes

Submits OTP to elevate session scope.

**Parameters (Form Data):**
*   `code` (required): 6-digit OTP.
*   `scope` (optional): Requested scope (default `login`).
*   `return_to` (optional): URL to redirect to on success.

**Response:**
*   **Success (302):** Redirects to `return_to` or `/dashboard`.
*   **Error (200):** Renders page with error.

---

### API Step-Up
**Endpoint:** `POST /api/auth/step-up`
**Auth Required:** Yes

**Request Body:**
```json
{
  "code": "123456",
  "scope": "login"
}
```

**Response (200 OK):**
```json
{
  "status": "granted",
  "scope": "login"
}
```

---

## 3) Password Management (Forced Change)

### Change Password
**Endpoint:** `POST /auth/change-password`
**Auth Required:** Partial (Pre-Session)

Used when `MustChangePasswordException` is triggered during login.

**Parameters (Form Data):**
*   `current_password`: Verify identity.
*   `new_password`: New secret.
*   `confirm_password`: Confirmation.

**Behavior:**
*   Clears `must_change_password` flag.
*   Does **NOT** create a session (user must log in again).
*   Redirects to `/login`.

---

## 4) Email Verification

### Verify (Web)
**Endpoint:** `POST /verify-email`
**Auth Required:** No (Guest)

**Parameters (Form Data):**
*   `email` (required)
*   `otp` (required)

**Response:**
*   **Success (302):** Redirects to `/login`.

---

### Resend Code (Web)
**Endpoint:** `POST /verify-email/resend`
**Auth Required:** No (Guest)

**Parameters (Form Data):**
*   `email` (required)

---

## 5) Session Management

### List Sessions (API)
**Endpoint:** `POST /api/sessions/query`
**Permission:** `sessions.list`

**Request Body:**
```json
{
  "page": 1,
  "per_page": 20,
  "search": {
    "columns": {
      "admin_id": "123",
      "status": "active"
    }
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
      "status": "active",
      "is_current": true
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 5,
    "filtered": 1
  }
}
```

**Pagination Meanings:**
*   `total`: total records in DB (no filters)
*   `filtered`: total records matching search
*   When no filters are applied, `filtered` MAY equal `total`.

---

### Revoke Session (Single)
**Endpoint:** `DELETE /api/sessions/{session_id}`
**Permission:** `sessions.revoke`

**Response:**
*   ✅ **200 OK** (JSON confirmation)
*   ❌ **400** if attempting to revoke current session.

---

### Bulk Revoke
**Endpoint:** `POST /api/sessions/revoke-bulk`
**Permission:** `sessions.revoke`

**Request Body:**
```json
{
  "session_ids": ["hash1...", "hash2..."]
}
```

**Response:**
*   ✅ **200 OK** (JSON confirmation)

---

## 6) Implementation Checklist

*   [ ] **CSRF**: Ensure `SameSite=Strict` cookies are respected.
*   [ ] **Step-Up**: Handle `403 Forbidden` with `STEP_UP_REQUIRED` by redirecting to `/2fa/verify`.
*   [ ] **Password Change**: Handle redirect to `/auth/change-password` if login fails with that specific error.
