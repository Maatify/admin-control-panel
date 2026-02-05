# Admin Kernel — API Documentation Index

**Project:** `maatify/admin-control-panel`
**Status:** **CANONICAL INDEX**

This directory contains the authoritative API documentation for the Admin Control Panel kernel.
Documentation is split by logical domain.

---

## 🔒 Authorization & RBAC

| Document                                               | Scope                                                        |
|:-------------------------------------------------------|:-------------------------------------------------------------|
| **[ROLES.md](API/ROLES.md)**                           | Core Role APIs (CRUD, Metadata, Rename).                     |
| **[ROLE-MANAGEMENT.md](API/ROLE-MANAGEMENT.md)**       | Advanced Role Ops (Assign Permissions, Assign Admins).       |
| **[ADMIN-PERMISSIONS.md](API/ADMIN-PERMISSIONS.md)**   | Admin-Centric Permissions (Effective, Direct Overrides).     |
| **[PERMISSION-DETAILS.md](API/PERMISSION-DETAILS.md)** | Permission Usage Insights (Roles/Admins using a permission). |

---

## 🔐 Authentication & Admins

| Document                                             | Scope                                                     |
|:-----------------------------------------------------|:----------------------------------------------------------|
| **[AUTH_SESSIONS.md](API/AUTH_SESSIONS.md)**         | Login, Step-Up (MFA), Password Reset, Session Management. |
| **[ADMINS_MANAGEMENT.md](API/ADMINS_MANAGEMENT.md)** | Admin CRUD, Email Management, Profile Management.         |

---

## 🌍 I18n & Localization

| Document                                             | Scope                                                   |
|:-----------------------------------------------------|:--------------------------------------------------------|
| **[I18N_LANGUAGES_UI.md](API/I18N_LANGUAGES_UI.md)** | **Contract Template**. Languages management (UI + API). |
| **[I18N_KEYS_UI.md](API/I18N_KEYS_UI.md)**           | Translation Keys management (UI + API).                 |

---

## ⚙️ System & Utilities

| Document                                           | Scope                                                 |
|:---------------------------------------------------|:------------------------------------------------------|
| **[SYSTEM_TELEMETRY.md](API/SYSTEM_TELEMETRY.md)** | Notifications, Preferences, Telemetry, Health Checks. |
| **[APP_SETTINGS_UI.md](API/APP_SETTINGS_UI.md)**   | App Settings Management (UI + API).                   |

---

## ⚠️ Runtime Rules (Binding)

All UI integrations must strictly follow the shared runtime rules:

> **[UI_RUNTIME_RULES.md](API/UI_RUNTIME_RULES.md)**
>
> Covers:
> * Canonical Query Envelope (pagination, search)
> * Error Handling (422 validation, 403 auth)
> * Response Parsing (JSON vs Empty Body)
> * Null Handling

---
