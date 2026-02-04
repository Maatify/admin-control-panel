# Admins Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / Admins`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for Admins Management.
It covers listing admins, creating admins, and managing their emails and profiles.

### ⚠️ CRITICAL: UI vs API Distinction

*   **`GET /admins`**
    *   ❌ **This is NOT an API.**
    *   ✅ This is the **browser entry point**.

*   **`POST /api/admins/*`**
    *   ✅ **These ARE the APIs.**

> ⚠️ **RUNTIME RULES:**
> This document assumes **mandatory compliance** with the **[UI Runtime Integration Rules](UI_RUNTIME_RULES.md)**.

---

## 1) List Admins (table)

**Endpoint:** `POST /api/admins/query`
**Capability:** `can_view_admins` (UI flag) / `admins.query` (API permission)

### Request — Specifics

*   **Global Search:** Matches `id` (exact), `email` (blind index), `status` (enum), or `display_name` (LIKE).
*   **Sorting:** ⚠️ **SERVER-CONTROLLED**.

**Example Request:**

```json
{
  "page": 1,
  "per_page": 20,
  "search": {
    "global": "active",
    "columns": {
      "status": "ACTIVE"
    }
  }
}
```

### Supported Column Filters (`search.columns`)

| Alias          | Type   | Semantics                          |
|----------------|--------|------------------------------------|
| `id`           | int    | Exact match                        |
| `email`        | string | Blind index match (case-insensitive)|
| `display_name` | string | `LIKE %value%`                     |
| `status`       | string | Exact enum (`ACTIVE`, `SUSPENDED`) |

### Response Model

```json
{
  "data": [
    {
      "id": 5,
      "display_name": "John Admin",
      "status": "ACTIVE",
      "created_at": "2026-01-10 14:22:11"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 37,
    "filtered": 3
  }
}
```

**Pagination Meanings:**
*   `total`: total records in DB (no filters)
*   `filtered`: total records after applying `search.global` and/or `search.columns`
*   When no filters are applied, `filtered` MAY equal `total`.

---

## 2) Create Admin

**Endpoint:** `POST /api/admins/create`
**Permission:** `admin.create`

### Request Body

*   `display_name` (string, required)
*   `email` (string, required)

**Example:**
```json
{
  "display_name": "New Admin",
  "email": "new.admin@example.com"
}
```

### Response (200 OK)

Returns initial details including temporary password (shown ONCE).

```json
{
  "admin_id": 123,
  "created_at": "2026-01-01 12:00:00",
  "temp_password": "random_hex_string"
}
```

---

## 3) Admin Emails

### List Emails
**Endpoint:** `GET /api/admins/{id}/emails`
**Permission:** `admins.email.list`

**Response:**
```json
{
  "admin_id": 123,
  "display_name": "John",
  "items": [
    {
      "email_id": 1,
      "email": "john@example.com",
      "status": "verified"
    }
  ]
}
```

### Add Email
**Endpoint:** `POST /api/admins/{id}/emails`
**Permission:** `admin.email.add`

**Request:**
```json
{ "email": "alt@example.com" }
```

**Response:**
```json
{ "adminId": 123, "emailAdded": true }
```

### Email Actions (Verify/Replace/Fail/Restart)
**Endpoints:** `POST /api/admin-emails/{emailId}/{action}`
**Actions:** `verify`, `replace`, `fail`, `restart-verification`

**Response:**
```json
{
  "adminId": 123,
  "emailId": 1,
  "status": "verified"
}
```

---

## 4) Admin Profile

### View Profile (UI)
**Endpoint:** `GET /admins/{id}/profile`
**Permission:** `admins.profile.view`

### Update Profile
**Endpoint:** `POST /admins/{id}/profile/edit`
**Permission:** `admins.profile.edit`

**Request (Form Data or JSON):**
*   `display_name` (optional)
*   `status` (optional)

**Response:**
*   **302 Redirect** to profile page on success.

---

## 5) Implementation Checklist

*   [ ] **Create Flow**: Capture the `temp_password` from the response and show it to the user immediately. It cannot be retrieved later.
*   [ ] **Email List**: Use the `status` field to show badges (Verified, Pending, etc.).
*   [ ] **Search**: Use `status` enum values exactly (`ACTIVE`, `SUSPENDED`, `DISABLED`).
