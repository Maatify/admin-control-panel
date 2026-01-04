# 🗺️ Admin Control Panel — Execution Roadmap (v1.1)

**Scope:** Backend only
**Architecture:** Clean Architecture / Layered
**Current State:** **Phase 3 — CLOSED ✅**

---

## 🎯 Project Goal

Build a **secure, runnable Admin Control Panel backend** that:

* Is usable immediately after install
* Is secure by design (no retrofitting)
* Scales cleanly with future features
* Enforces strict architectural boundaries

---

## 🔒 Phase 0 — Project Bootstrap (CLOSED)

**Goal:** Runnable project from day one

### Delivered

* Composer project (`type: project`)
* Slim Framework bootstrap
* Dependency Injection container
* dotenv configuration
* PDO Factory
* Health endpoint

📌 **Phase 0 is locked**

---

## 🆔 Phase 1 — Admin Identity (CLOSED)

**Goal:** Create Admin as a pure identifier

### Delivered

* `admins` table
* `POST /admins`
* Admin ID only (no sensitive data)

📌 **Phase 1 is locked**

---

## 🔐 Phase 2 — Identifier Storage & Retrieval (CLOSED)

**Goal:** Secure storage and controlled retrieval of identifiers

### 2.1 Secure Storage

* `admin_emails` table
* Blind index (HMAC-SHA256)
* AES-256-GCM encryption
* `POST /admins/{id}/emails`

### 2.2 Controlled Retrieval

* `POST /admin-identifiers/email/lookup` (existence only)
* `GET /admins/{id}/emails` (controlled decrypt)
* No enumeration
* No listing

### 2.3 Governance Lock

* No new retrieval endpoints
* Zero-diff phase (by design)

### 2.4 Architectural Refactor

* Controllers contain no PDO or SQL
* Repository layer introduced
* Clean DI wiring

📌 **Phase 2 is locked**

---

## 🧠 Phase 3 — Verification & State Control (CLOSED)

**Goal:** Move from CRUD to state-aware domain logic

### 3.1 DTO Layer ✅

* Request DTOs
* Response DTOs
* ❌ No raw arrays

### 3.2 Enums ✅

* `IdentifierType`
* `VerificationStatus`
* `ActionResult`

### 3.3 Custom Exceptions ✅

* Domain-specific exceptions
* Explicit failures
* ❌ No silent errors

### 3.4 Verification Foundations ✅

* Validation isolated inside DTOs
* Explicit error signaling
* State readiness without introducing flows yet

### 3.5 Static Analysis Hardening ✅

* phpstan `level=max`
* Explicit type narrowing
* No behavior changes
* No cross-phase refactors

📌 **Phase 3 is locked**

---

## 🔑 Phase 4 — Authentication (NEXT)

**Goal:** Secure system access

* Login flow
* Password hashing
* Session or token strategy (TBD)
* Backend only (no UI)

📌 **Phase 4 not started**

---

## 🛡️ Phase 5 — Authorization (PLANNED)

* Roles
* Permissions
* Policy checks

---

## 🧾 Phase 6 — Audit & Logging (PLANNED)

* Admin action logs
* Security events
* Immutable audit trail

---

## 🧰 Phase 7 — Operational (PLANNED)

* System configuration
* Feature flags
* Maintenance endpoints

---

## 📌 Global Architecture Rules (ENFORCED)

* Controllers never access the database directly
* Repositories return primitives only
* Public contracts use DTOs / Enums / Custom Exceptions
* Closed phases must not be modified
* Refactors are isolated tasks only

---

## 🧭 Current Position

* Phase 0 → Phase 3 **CLOSED**
* Architecture stabilized
* Static analysis clean
* **Ready to begin Phase 4 — Authentication**

---