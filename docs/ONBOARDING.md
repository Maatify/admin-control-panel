# Onboarding Guide — Reality Aligned

**Project:** Admin Control Panel
**Status:** Current State — Infrastructure-First
**Stack:** PHP 8.2 (Slim Framework) + Twig + MySQL
**Audience:** Backend Developers & Frontend (Twig) Developers

---

## 1️⃣ Executive Overview

This project is a **backend-heavy administrative system** built with a
**Security-First / Zero-Trust** architecture.

It is **not** a UI-first or feature-first project.

### What is this project?

A centralized admin system designed for sensitive environments
(financial, enterprise, internal systems), based on:

* Zero-Trust (no implicit trust after login)
* Multi-layer middleware pipeline
* Server-side session authority
* Step-up authentication for sensitive actions
* Mandatory audit logging for critical operations

### ⚠️ Important Warning

> Any change made without fully understanding this document
> introduces **high security risk**.
>
> The complexity in this system is **intentional**.
> Do not attempt to “simplify” or bypass it.

---

## 2️⃣ GitHub Workflow

To keep the codebase stable and auditable, follow these rules strictly.

1. **Clone**

```bash
git clone <repository-url>
```

2. **Branching Strategy**

* Features: `feature/your-feature-name`
* Fixes: `fix/issue-description`

3. **Pull Safety**

```bash
git pull origin main --rebase
```

4. **Strictly Forbidden**

* ❌ Direct push to `main`
* ❌ Merge without review

5. **Clean History**

* Squash commits before merge
* Clean git history = easier debugging & auditing

---

## 3️⃣ Local Setup

### Requirements

* PHP 8.2+
* Extensions:
    * pdo
    * openssl
    * redis
    * mbstring
    * readline
    * json
    * intl
* Composer

---

### Setup Steps

#### 1️⃣ Install Dependencies

```bash
composer install
```

#### 2️⃣ Environment Configuration

* Copy:

  ```
  .env.example → .env
  ```
* Configure database connection values (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
* **Critical Security Keys:**
  The system requires specific cryptographic keys to function (`ENCRYPTION_KEY`, `EMAIL_BLIND_INDEX_KEY`, `PASSWORD_PEPPER`).

  > **Frontend Developers:** Do NOT modify these keys.
  >
  > **Backend Developers:** Use a secure random generator (CSPRNG). Consult the core/security team for specific length and format requirements.

---

## 4️⃣ Database Setup (SQL Import) — **REQUIRED**

Before running the system, the database **must be created and initialized**.

### SQL Schema File

* Location: `database/schema.sql`
* It contains:
    * Tables
    * Constraints
    * Indexes
* It does NOT contain:
    * Default admins
    * Seed data
    * Demo accounts

---

### Import Command

```bash
mysql -u USER -p DB_NAME < database/schema.sql
```

### 📌 Important

After import:
* The database will contain **no admin users**
* An empty database is expected

---

## 5️⃣ First Admin Creation (Bootstrap) — **CRITICAL**

> ⚠️ This is the most important step for setting up a new environment.

The system does **not** have a web-based registration or bootstrap UI.

### The Only Correct Method

#### 1️⃣ Run the Bootstrap Script (CLI)

```bash
php scripts/bootstrap_admin.php
```

#### 2️⃣ Follow the Prompts

1. The script will verify that no admins currently exist.
2. Enter the **Email** and **Password** for the first admin.
3. The script will generate a **TOTP Secret** and display it.
4. **Configure your Authenticator App** (Google Authenticator, Authy, etc.) with the displayed secret.
5. Enter the **OTP Code** from your app to verify.
6. Upon success, the admin is created, and the email is marked as verified.

### ⚠️ Security Warning

> Running this script more than once is considered a security violation.
> The script will automatically exit if any admin already exists in the database.
> Do NOT attempt to bypass this check.

---

## 6️⃣ Local Server Startup

To start the local development server:

```bash
php -S 0.0.0.0:8080 -t public
```

*   Access the application at: `http://localhost:8080`
*   This command is for **local development only**.
*   It assumes PHP 8.2+ is available in your shell.

---

## 7️⃣ Exposed Routes

For the authoritative list of exposed routes, please refer to:

1. **`docs/API.md`** — The Canonical API Contract.
2. **`routes/web.php`** — The source of truth for current routing configuration.

📌 **Note:** Any endpoint not documented in `docs/API.md` is considered unavailable.

---

## 8️⃣ Frontend & Twig — Safe Usage Rules

### Templates Location

```
templates/
```

### Strict Rules

1. Controller ≠ View
2. ❌ No security logic in Twig
3. Always escape output:

   ```twig
   {{ value|e }}
   ```
4. Use translation keys only
5. Do not assume DTO field order or presence

### Frontend Notes
*   **No First-Run UI:** The frontend assumes the bootstrap process (CLI) has already been completed. There is no UI for creating the first admin.
*   **State:** Do not assume any specific system state. Handle 401/403 errors gracefully.

---

## 9️⃣ Golden Rules for All Developers

1. ❌ No manual auth logic
2. ❌ Do not change permission semantics
3. ❌ Session ≠ Identity
4. ❌ Do not expose `admin_id` in UI
5. ✅ When in doubt — ask

---

## 🔟 Current Phase Boundary

### **Infrastructure & Core Security**

**Completed**

* Login
* Sessions
* TOTP / Step-Up Authentication
* Transactional Audit Outbox
* Notification Infrastructure

**Not Allowed Yet**

* Business logic (beyond admin management)
* Product-specific features

---

## 🔚 Final Notes

This document is the **single source of truth** for running the project.

* Any conflict → this document is correct
* Any feature request → outside this guide

**Work carefully — the system will work with you 🔒**
