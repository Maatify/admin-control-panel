# System & Telemetry — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / System`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

## 0) Why this document exists

This file is a **runtime integration contract** for System Utilities, Telemetry, and Notifications.

> ⚠️ **RUNTIME RULES:**
> This document assumes **mandatory compliance** with the **[UI Runtime Integration Rules](UI_RUNTIME_RULES.md)**.

---

## 1) Notifications

### List Admin Notifications (Legacy)
**Endpoint:** `GET /api/admins/{admin_id}/notifications`
**Permission:** `admin.notifications.history`

**Parameters:** `page`, `limit`, `notification_type`, `is_read`, `from_date`, `to_date`.

### Mark as Read
**Endpoint:** `POST /api/admin/notifications/{id}/read`
**Permission:** `admin.notifications.read`

**Response:** `204 No Content`

### Global Notifications Query (Legacy)
**Endpoint:** `GET /api/notifications`
**Permission:** `notifications.list`

---

## 2) Notification Preferences

### Get Preferences
**Endpoint:** `GET /api/admins/{admin_id}/preferences`
**Permission:** `admin.preferences.read`

### Update Preference
**Endpoint:** `PUT /api/admins/{admin_id}/preferences`
**Permission:** `admin.preferences.write`

**Request:**
```json
{
  "notification_type": "security_alert",
  "channel_type": "email",
  "is_enabled": true
}
```

---

## 3) Telemetry (Read-Only)

### List Telemetry (Canonical)
**Endpoint:** `POST /api/telemetry/query`
**Permission:** `telemetry.list`

**Request Body:**
```json
{
  "page": 1,
  "per_page": 20,
  "search": {
    "columns": {
      "event_key": "AUTH_LOGIN"
    }
  }
}
```

**Supported Filters:**
*   `event_key`, `route_name`, `request_id`, `actor_type`, `actor_id`, `ip_address`

**Response Model:**
```json
{
  "data": [
    {
      "id": 123,
      "event_key": "AUTH_LOGIN",
      "severity": "INFO",
      "occurred_at": "2026-01-16 12:45:10"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 500,
    "filtered": 12
  }
}
```

**Pagination Meanings:**
*   `total`: total records in DB (no filters)
*   `filtered`: total records after applying filters
*   When no filters are applied, `filtered` MAY equal `total`.

---

## 4) System Helpers

### Health Check
**Endpoint:** `GET /health`
**Auth Required:** No

**Response:**
```json
{ "status": "ok" }
```

### Telegram Webhook
**Endpoint:** `POST /webhooks/telegram`
**Auth Required:** No (Internal validation)

Used for machine-to-machine integration. Always returns `200 OK`.
