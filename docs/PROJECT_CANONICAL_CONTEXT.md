# Admin Control Panel — Canonical Context

> **Status:** Draft / Living Document  
> **Source:** Repository Analysis (AS-IS) + `docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md` (TARGET)  
> **Context Owner:** Project Architects

---

## 🏗️ A) Project Snapshot (AS-IS)

The project is a secure Admin Control Panel built with **PHP 8.2+, Slim 4, PHP-DI, and Twig**. It follows a strictly layered **Domain-Driven Design (DDD)** architecture with a strong emphasis on security, auditing, and clean separation of concerns.

### Directory Map
*   **`app/Domain/`**: Pure business logic (Services, Contracts, DTOs, Enums). No infrastructure dependencies allowed.
*   **`app/Infrastructure/`**: Concrete implementations (Repositories, Mailers, Loggers, PDO adapters).
*   **`app/Http/`**: Application layer (Controllers, Middleware).
*   **`app/Bootstrap/`**: Dependency Injection (`Container.php`) and Configuration (`AdminConfigDTO`).
*   **`public/`**: Web root. Entry point `index.php`.
*   **`routes/`**: Route definitions (`web.php`).
*   **`templates/`**: Twig views (`pages/`, `layouts/`, `components/`).
*   **`docs/`**: Canonical documentation and architectural records.

### Key Entry Points
*   **Web/API**: `public/index.php` -> `routes/web.php`
*   **CLI**: `scripts/bootstrap_admin.php` (System bootstrapping only)
*   **Config**: `app/Bootstrap/Container.php` (Single source of configuration loading)

---

## ⚙️ B) Operating Model (How we work)

### 1. No Guessing Policy
*   We **DO NOT** assume behavior. Every change must be proven by existing patterns or explicit documentation.
*   If a rule is not in this file or `docs/`, it is an **OPEN QUESTION** that must be resolved before coding.

### 2. Phase Discipline
*   **Phase 1-13 (Core Security/Auth)**: **FROZEN**. No changes allowed to `AdminAuthenticationService`, `PasswordService`, or basic Auth flows unless explicitly requested for security fixes.
*   **Phase 14+ (UI/UX)**: **ACTIVE**. New pages and APIs are expected to follow the **Canonical Template** (`docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md`).

### 3. File Responsibility Matrix
| Layer      | Files                            | Allowed Changes                                                                     |
|:-----------|:---------------------------------|:------------------------------------------------------------------------------------|
| **Domain** | `app/Domain/**/*.php`            | **RESTRICTED**. Interfaces & DTOs only. Logic changes require strong justification. |
| **Infra**  | `app/Infrastructure/**/*.php`    | **ALLOWED**. Repositories, new adapters.                                            |
| **HTTP**   | `app/Http/Controllers/Ui/*.php`  | **ALLOWED**. New UI Controllers.                                                    |
| **HTTP**   | `app/Http/Controllers/Api/*.php` | **ALLOWED**. New API Controllers.                                                   |
| **Web**    | `routes/web.php`                 | **ALLOWED**. New routes (strict naming).                                            |
| **Views**  | `templates/**/*.twig`            | **ALLOWED**. UI implementation.                                                     |
| **Docs**   | `docs/**/*.md`                   | **REQUIRED**. Every feature needs docs.                                             |

---

## 🛡️ C) Security & Authority Rules

### 1. Observed Configuration Rules
*   **Fail-Closed Environment**: Missing `.env` variables cause immediate crash in `Container.php` (via `$dotenv->required(...)->notEmpty()`).
*   **Recovery Mode**: If `RECOVERY_MODE=true`, strict lock-down is enforced by `RecoveryStateService`.
*   **Session State**: Sessions default to `PENDING_STEP_UP`. `ACTIVE` state requires `Scope::LOGIN`.

---

### 2. Middleware Pipeline (Observed)
All protected routes passed through `routes/web.php` groups are observed to follow this sequence:
1.  `UiRedirectNormalizationMiddleware` (UI only)
2.  `RememberMeMiddleware`
3.  `SessionGuardMiddleware` (Identity)
4.  `SessionStateGuardMiddleware` (State / Step-Up)
5.  `ScopeGuardMiddleware` (Context)
6.  `AuthorizationGuardMiddleware` (RBAC)

---

### 3. Authorization & Permission Semantics (Canonical)

This project enforces a **strict, explicit, non-hierarchical authorization model**.

*   Permissions are **flat and non-hierarchical**.
*   **No permission implicitly grants another permission**.
*   Authorization decisions are made **exclusively at the route level**, using the route name as the permission identifier.
*   Backend services, shared methods, filters, or internal implementation details MUST NOT influence authorization decisions.
*   There is **no automatic permission linking**, implication, or inheritance.

#### Supporting / Select Permissions
*   Any list or dataset required **only for selection purposes** (e.g. dropdowns, autocomplete, filters) MUST be exposed via:
  *   A **dedicated endpoint**
  *   A **dedicated explicit permission** (e.g. `products.select`, `admins.select`)
*   Granting a mutation permission (e.g. `products.price.edit`) does NOT grant selection or listing permissions implicitly.
*   If a UI operation requires both selecting an entity and performing an action, the role MUST explicitly include **both permissions**.

This rule is **SECURITY-CRITICAL** and MUST NOT be bypassed, inferred, or altered without an explicit architectural decision and documentation update.

---

### 4. Auditing (Authority & Security Only)
*   **Scope**: `audit_logs` are strictly reserved for **Authority Changes**, **Security-Impacting Actions**, and **Admin Responsibility Events**.
*   **Exclusion**: Routine non-security CRUD or UI-driven mutations are **NOT** automatically audit entries unless they impact authority or security posture.
*   **Mechanism**: When required, auditing uses `AuthoritativeSecurityAuditWriterInterface` within the same `PDO` transaction as the mutation.

## 🪵 D) Logging Policy (HARD)

The system enforces a strict separation between "What Changed" (Audit) and "What Happened" (Security Event).

### 1. Audit Logs (`audit_logs`)
*   **Purpose**: Authoritative history of Authority/Security mutations.
*   **Interface**: `AuthoritativeSecurityAuditWriterInterface`.
*   **Schema**: Actor (Admin ID), Target Type (String), Target ID, Action, Changes (JSON Diff).
*   **Constraint**: Transactional integrity required for these events.

### 2. Security Events (`security_events`)
*   **Purpose**: Signals, alerts, and high-volume tracking (Login, Failed Access, Logout).
*   **Interface**: `SecurityEventLoggerInterface`.
*   **Schema**: Event Name, Admin ID, Severity (Info/Warning/Error), Payload (Context).
*   **Behavior**: Best-effort logging (does not block unless critical).

---

## 🚦 E) Routing & Middleware Contract

### 1. Route Definitions
*   **File**: `routes/web.php`.
*   **Prefix**: `/api` distinguishes API (JSON) from Web (HTML).

### 2. Naming Convention
*   **Format**: `resource.action` (e.g., `sessions.list`, `admin.create`).
*   **Usage**: Required for `AuthorizationGuardMiddleware` to look up permissions.

### 3. Web vs API (Observed)
*   **Web (`/`)**: Returns HTML/Twig. Redirects on error (`UiRedirectNormalizationMiddleware`).
*   **API (`/api`)**: Returns JSON. Returns 401/403 JSON on error.
*   **Auth**: Both share the same `auth_token` cookie. No Bearer tokens observed.

---

## 📄 F) Pagination & Filtering Contract (Canonical)

Defined by `SessionQueryController` implementation
and enforced across **POST-based Canonical LIST / QUERY APIs** only.

---

### 0. Pagination & Filtering — Architectural Decision (LOCKED)

**Status:** LOCKED / MANDATORY
**Applies to:** All LIST APIs (Sessions, Admins, Roles, and future resources)

The Admin Control Panel enforces a **single canonical model** for:

* Pagination
* Searching
* Column filtering
* Optional date range filtering

These concerns are **architectural**, not UI conveniences.

---

### 1. Canonical Pagination DTO

Pagination MUST be represented using the shared Domain DTO:

```
App\Domain\DTO\Common\PaginationDTO
```

This DTO is the **only allowed representation** of pagination data
inside the application.

#### Hard Rules

* Pagination MUST NOT be represented as anonymous arrays
* All LIST responses MUST expose pagination via `PaginationDTO`
* `PaginationDTO`:

  * Lives in the Domain layer
  * Implements `JsonSerializable`
  * Defines an explicit array shape in `jsonSerialize()`
* Infrastructure Readers (PDO / Infra layer) are responsible for constructing `PaginationDTO`
* Controllers MUST NOT assemble or mutate pagination structures

Any deviation from this contract is considered a **Canonical Violation**.

---

### 2. Canonical LIST Request Contract

#### Request (JSON)

```json
{
  "page": 1,
  "per_page": 20,

  "search": {
    "global": "",
    "columns": {}
  },

  "date": {
    "from": "YYYY-MM-DD",
    "to": "YYYY-MM-DD"
  }
}
```

---

### 3. Search Semantics (LOCKED)

#### 3.1 Global Search

* `search.global` represents a **free-text search**
* Applied as **OR** across a predefined whitelist of searchable columns
* Backend defines the searchable columns explicitly
* UI MUST NOT decide which columns are searchable

**Rules:**

* Global search is OPTIONAL
* Empty or missing value MUST be ignored
* Search is **server-side only**

---

#### 3.2 Column-Based Filters

* `search.columns` is a key-value map
* Each key represents a **specific column filter**
* Applied as **AND** conditions

**Rules:**

* Only documented columns are allowed
* Unknown columns MUST be ignored or rejected
* Empty values MUST be ignored
* UI MUST NOT send undocumented filters

---

### 4. Date Range Filtering (OPTIONAL / CAPABILITY-BASED)

#### 4.1 Purpose

Some LIST resources are **time-based** (e.g. sessions, audit logs),
while others are not.

Date filtering is therefore **optional** and **capability-driven**.

---

#### 4.2 Request Shape

```json
"date": {
  "from": "YYYY-MM-DD",
  "to": "YYYY-MM-DD"
}
```

**Rules:**

* `date` object is OPTIONAL
* `from` and `to` are OPTIONAL and independent
* One-sided ranges are allowed
* Backend is responsible for validation and normalization

---

#### 4.3 Backend Capability Declaration (MANDATORY)

Each LIST API MUST explicitly declare whether it supports date filtering.

**Backend-owned decision only.**

Example (conceptual):

```
supportsDateFilter = true | false
dateColumn = "created_at"
```

**Rules:**

* UI MUST NOT assume date support
* UI MUST NOT send `date` filters unless explicitly supported
* Date filtering applies to **ONE predefined column only**
* Dynamic date columns are FORBIDDEN

---

#### 4.4 Unsupported Date Filters

If a LIST API does NOT support date filtering:

* `date` input MUST be:

  * Ignored silently
    **OR**
  * Rejected with validation error (`date_filter_not_supported`)

The chosen behavior MUST be consistent per API.

---

### 5. Canonical LIST Response Contract

#### Response (JSON)

```json
{
  "data": [ ... ],

  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 500,
    "filtered": 37
  }
}
```

---

### 6. Pagination Fields Semantics

| Field      | Meaning                                |
|------------|----------------------------------------|
| `page`     | Current page number                    |
| `per_page` | Rows per page                          |
| `total`    | Total rows in the dataset (no filters) |
| `filtered` | Rows count after ALL filters applied   |

**Important:**

* `filtered` reflects:

  * Global search
  * Column filters
  * Date range filters (if supported)

---

### 7. Hard Prohibitions (SECURITY & CONSISTENCY)

❌ Client-side pagination
❌ Client-side searching
❌ UI-defined searchable columns
❌ Dynamic SQL column injection
❌ Multiple date columns per LIST
❌ Implicit date filtering

---

### 8. Enforcement Summary

* LIST APIs MUST follow this contract
* UI reflects backend-declared capabilities
* Backend owns all filtering logic
* Any deviation is a **Canonical Violation**

---

## ✅ Status

**Pagination, Search, and Date Filtering Contract: LOCKED**

---

## 🧩 F.1) Reusable LIST Infrastructure (Canonical)

**Status:** ARCHITECTURE-LOCKED
**Applies to:** All current and future LIST APIs

To avoid duplication, inconsistency, and security drift, the Admin Control Panel
defines a **reusable, capability-driven infrastructure** for all LIST queries.

Reuse is achieved through **shared contracts and orchestration**,
**NOT** through generic SQL builders or magic helpers.

---

### 1. Core Principle

> **LIST behavior is reusable by contract, not by copy-paste.**

All LIST APIs MUST share:

* The same request structure
* The same pagination model
* The same filtering semantics

While allowing:

* Per-resource capabilities
* Per-resource column control
* Per-resource SQL ownership

---

### 2. Canonical LIST Query DTO (MANDATORY)

All LIST APIs MUST accept a unified request DTO representing list intent.

**Conceptual DTO:**

```
App\Domain\List\ListQueryDTO
```

**Responsibilities:**

* Page number
* Page size
* Global search term
* Column-based filters
* Optional date range

**Non-Responsibilities (STRICT):**
❌ No SQL logic
❌ No column knowledge
❌ No table awareness
❌ No authorization logic

This DTO defines **shape only**, not behavior.

---

### 3. Capability-Driven Design (MANDATORY)

Each LIST resource MUST explicitly declare its supported capabilities.

Capabilities are **backend-owned** and **resource-specific**.

Conceptual capability set:

* Supports global search (yes/no)
* Supported searchable columns (explicit whitelist)
* Supports column-based filters (yes/no)
* Supports date filtering (yes/no)
* Date column name (single, predefined)

**Rules:**

* UI MUST NOT assume capabilities
* UI reflects backend-declared capabilities only
* Capabilities MUST NOT be inferred dynamically
* Capabilities MUST be documented per resource

---

### 4. Centralized Filter Resolution (REUSABLE CORE)

Filtering logic MUST be centralized in a reusable resolver.

**Conceptual component:**

```
App\Infrastructure\Query\ListFilterResolver
```

**Responsibilities:**

* Validate incoming filters against declared capabilities
* Normalize search input
* Ignore or reject unsupported filters
* Produce a **structured, SQL-agnostic filter model**

**Non-Responsibilities (STRICT):**
❌ No SQL generation
❌ No PDO usage
❌ No table or column names
❌ No pagination math

The resolver prepares **safe intent**, not queries.

---

### 5. Repository Responsibility (STRICT)

Repositories remain **fully responsible** for SQL execution.

Each Repository:

* Knows exactly ONE table
* Knows its allowed columns
* Applies resolved filters explicitly
* Constructs `PaginationDTO`
* Executes queries using PDO only

**Prohibited:**
❌ Generic repositories
❌ Shared SQL builders
❌ Cross-table list handlers

---

### 6. Reference Implementation Rule

At least ONE LIST API MUST act as a **reference implementation**
for this infrastructure.

Current reference:

* `Sessions` LIST API

All future LIST APIs MUST:

* Follow the same structure
* Reuse the same DTOs and resolver
* Differ ONLY in declared capabilities and repository logic

---

### 7. Hard Prohibitions

❌ Copy-pasting list logic across controllers
❌ UI-driven filtering logic
❌ Generic SQL helpers
❌ Dynamic column selection
❌ Implicit capabilities

---

### 8. Enforcement Summary

* LIST reuse is **structural**, not procedural
* Capabilities are explicit and backend-owned
* Repositories remain simple and predictable
* Any deviation is a **Canonical Violation**

---

## ✅ Status

**Reusable LIST Infrastructure: LOCKED**

---

## 🎨 G) UI/Twig Contract

### 1. Controller Pattern (Observed)

* **UI Controllers** (`App\Http\Controllers\Ui\`): Render Twig templates. No DB access observed.
* **Base Layout**: `templates/layouts/base.twig`.
* **Scripts**: Injected via `{% block scripts %}`.

### 2. Data Flow (Target Pattern)

* **Page Load**: Renders skeleton (HTML).
* **Data Fetch**: Client-side JS calls `POST /api/{resource}/query`.
* **Actions**: Client-side JS calls `POST /api/{resource}/{action}`.

---

## 🗄️ H) Database & Repositories Contract

### 1. Architecture

* **Access**: `PDO` only. No ORM observed.
* **Injection**: Repositories injected via Interface into Services.
* **Strictness**: `declare(strict_types=1)`. Explicit return types.

### 2. Repositories

* **Location**: `app/Infrastructure/Repository/`.
* **Pattern**: Methods return Domain Objects or DTOs.
* **Transactions**: Services manage transactions, Repositories accept `PDO` in constructor (shared connection).

---

## 🏆 I) Canonical Templates

**Reference**: `docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md`

### Target State (Phase 14+)

* **Page Types**: LIST, CREATE, EDIT, VIEW.
* **Routing**: strict `GET /{resource}` (UI) and `POST /api/{resource}/query` (API).
* **Permissions**: 1:1 mapping with routes.

### CURRENT STATE vs CANONICAL GAP ANALYSIS

* **Compliance**:

    * `Sessions` (Phase 14.3) is fully compliant.
* **Gaps (Observed)**:

    * `Admins`, `Roles`, `Permissions` pages are currently placeholders (`coming soon`). They do not yet implement the Canonical API-First pattern.
    * Legacy `Web\*Controller` classes (e.g., `LogoutController`) exist alongside `Ui*Controller` wrappers.

---

## 📝 J) Task Playbook

### 1. Add New Admin Panel Page (UI)

* **Files**:

    * Create `app/Http/Controllers/Ui/Ui{Resource}Controller.php`.
    * Create `templates/pages/{resource}.twig`.
    * Update `routes/web.php` (Group: Protected UI).
* **Target**: Follow the Canonical Template pattern (View -> API).

### 2. Add New Protected API Endpoint

* **Files**:

    * Create `app/Http/Controllers/Api/{Resource}{Action}Controller.php`.
    * Create `App/Domain/DTO/{Resource}/{Action}RequestDTO.php`.
    * Update `routes/web.php` (Group: `/api`, Middleware: `AuthorizationGuardMiddleware`).
* **Security**: Ensure `AuthorizationGuardMiddleware` and proper Permission name.

### 3. Add New DB Table

* **Files**:

    * Update `database/schema.sql` (Canonical Schema).
    * Create `scripts/migrations/xxx_add_table.sql` (if strict migration required).
* **Code**: Create `app/Infrastructure/Repository/Pdo{Resource}Repository.php` and Interface in `app/Domain/Contracts/`.

---

## ⚔️ K) CONFLICTS

* **Web vs Ui Controllers**: `app/Http/Controllers/Web/` contains legacy logic. `app/Http/Controllers/Ui/` is the new standard.

    * *Conflict*: `LoginController` is in `Web` but wrapped by `UiLoginController`.
    * *Resolution*: Prefer `Ui` controllers for all new UI routes. Keep `Web` only for legacy support until fully migrated.

---

## ❓ L) OPEN QUESTIONS

* **Asset Management**: How are frontend assets (JS/CSS) specifically for `sessions.twig` managed? The file content is not visible, but `SessionListController` exists. It implies inline scripts or a pattern not yet fully documented.
* **Legacy Data Loading**: Do the legacy "Web" controllers handle data loading inside the controller (server-side)? Verification needed before refactoring.

---

## 🧩 M) Cross-Cutting Concerns (Canonical)

The system defines several modules that cross application boundaries and affect multiple layers.

---

### **0. Input Normalization (CANONICAL BOUNDARY)**

**Status:** ARCHITECTURE-LOCKED / MANDATORY  
**Applies to:** All Web & API requests  
**Authoritative Decision:** `docs/adr/ADR-001-input-normalization.md`

The Admin Control Panel enforces a **mandatory input normalization boundary**
that executes **before any validation, guards, or authorization logic**.

Input Normalization is responsible for:

* Canonical key mapping
* Legacy compatibility
* Precedence resolution

And is **strictly forbidden** from performing:

❌ Validation  
❌ Sanitization  
❌ Business logic  
❌ Default value injection

All downstream layers (Validation, DTOs, Controllers, Services)
MUST operate on **canonical input only**.

By definition:

* Validation schemas MUST NOT handle legacy keys
* Controllers MUST NOT compensate for non-canonical input
* DTOs MUST be constructed from normalized input only

Any deviation is considered an **Architecture Violation**.

> Full rationale, scope, and non-goals are defined in  
> `docs/adr/ADR-001-input-normalization.md`

---

### **1. Input Validation (NEW)**

**Status:** ARCHITECTURE-APPROVED / ACTIVE  
**Canonical Spec:** `docs/architecture/input-validation.md`

**Rules:**

* Validation occurs **before** authentication & authorization
* Validation failures return UI-friendly structured errors
* Validation uses **library-assisted rule sets**
* DTO defines **shape**, Validation defines **rules**
* Validation is **not** responsible for domain invariants

**Error Semantics:**

* Validation Error → `400 INPUT_INVALID`
* Auth Error → `401 AUTH_REQUIRED`
* Step-Up Error → `403 STEP_UP_REQUIRED`
* Permission Error → `403 NOT_AUTHORIZED`

**Integration Points:**

* Controllers map validation to UI/API responses
* Guards only run after validation passes
* No audit/security events emitted on validation failure

**Library Decision:**

> `respect/validation` selected to implement rule sets

---

### **2. Email Messaging & Delivery (NEW)**

**Status:** ARCHITECTURE-APPROVED / ACTIVE  
**Phase:** 14+ (Async Infrastructure, Non-Auth)

The system implements a **fully asynchronous, encrypted email delivery pipeline**
used for verification, OTP delivery, and system notifications.

This module is **NOT** part of Authentication logic and **MUST NOT**
alter or short-circuit any Auth, Session, or Step-Up behavior.

---

#### Email Queue (Canonical Infrastructure)

* Email sending is **NEVER synchronous**
* All emails MUST be enqueued in the `email_queue` table
* Controllers and Services MUST NOT send emails directly
* Email delivery is handled by background workers / cron jobs

The email queue is treated as **Infrastructure Output**, not a Domain Event.

---

#### Domain Binding (Entity Traceability)

Each queued email MUST be bound to a domain entity:

* `entity_type`: `admin` | `user` | `system` | `external`
* `entity_id`: string or integer (casted)

This binding exists for:

* Debugging & support
* Failure tracing
* Correlation with domain actions
* Multi-domain reuse of the same queue

This binding is **NOT** used for authorization or access control.

---

#### Encryption Policy (Mandatory)

All sensitive email data is stored **encrypted at rest**:

* Recipient email address → AES-GCM encrypted
* Rendered email payload (subject + body) → AES-GCM encrypted
* No plaintext email content is stored in the database

Decryption is allowed **ONLY at send-time** by the email delivery worker.

This rule is **SECURITY-CRITICAL** and MUST NOT be bypassed.

---

#### Email Templates (Twig-Based)

* All email content is rendered using **Twig templates**
* Templates are language-specific and presentation-only
* No HTML is constructed dynamically in PHP code

**Location:**

```

templates/
└── emails/
    ├── layouts/
    │   └── base.twig
    ├── otp/
    │   ├── en.twig
    │   └── ar.twig
    └── verification/
        ├── en.twig
        └── ar.twig

```

**Rules:**

* No business logic in templates
* No database access
* No permission-based conditionals
* Templates consume DTO-provided variables only

---

#### Payload DTO Responsibility

Email Payload DTOs are **independent** of Twig templates:

* DTOs define semantic fields (e.g. `display_name`, `otp_code`, `expires_in_minutes`)
* Templates map DTO fields to presentation
* No formatting, localization, or rendering logic exists in DTOs

This separation allows:

* Safe template refactoring
* Multi-channel reuse (Email now, Telegram later)
* Deterministic alignment via static contracts

---

#### Execution Model & Failure Semantics

* Queue rows transition through:  
  `pending → processing → sent | failed | skipped`
* Retry logic is infrastructure-level only
* Failures MUST NOT block UI or API flows
* Email delivery does **NOT** emit audit logs by default

---

#### Explicit Non-Goals

Email Messaging does **NOT**:

* Affect authentication or authorization state
* Trigger `audit_logs`
* Block user-facing requests
* Perform business decisions

---

### **3. Cryptography & Secrets Handling (NEW)**

**Status:** ARCHITECTURE-LOCKED / ACTIVE  
**Scope:** All application layers (Domain, Infrastructure, Workers)

The system defines a **single, unified cryptography contract** for handling
all sensitive data, secrets, identifiers, and encrypted payloads.

Cryptography is treated as an **infrastructure capability**, not a feature.

---

#### Single Entry Point (Mandatory)

All cryptographic operations **MUST** go through the canonical facade:

```

App\Domain\Contracts\CryptoFacadeInterface

```

❌ Direct usage of:
* OpenSSL
* HKDF
* Key rotation services
* Random generators

is **STRICTLY FORBIDDEN** outside the Crypto module.

---

#### Crypto Context Registry (LOCKED)

All reversible encryption operations **MUST** use a predefined,
versioned crypto context.

Allowed contexts are defined exclusively in:

```

App\Domain\Security\CryptoContext

```

Rules:

* Contexts MUST be versioned (`:vX`)
* Contexts MUST NOT be user-defined
* Contexts MUST be static and documented
* Dynamic or runtime contexts are forbidden

---

#### Reversible Encryption (Data-at-Rest)

Used for:

* Email recipients & payloads
* TOTP seeds
* Encrypted identifiers (PII)

Characteristics:

* Encryption is reversible
* Uses Key Rotation
* Uses context-based key derivation (HKDF)
* Stores `key_id` with encrypted payload
* Decryption MUST fail hard if key is missing

Encrypted outputs are represented exclusively by:

```

App\Domain\DTO\Crypto\EncryptedPayloadDTO

```

---

#### One-Way Secrets (Passwords & OTP)

Used for:

* Passwords
* OTP codes
* Verification codes

Rules:

* One-way hashing only
* No reversible encryption
* Pepper is REQUIRED for passwords
* Secrets are never decrypted

Passwords and OTP **MUST NOT** use the reversible encryption pipeline.

---

#### Usage Matrix (LOCKED)

| Use Case           | Method     | Context            |
|--------------------|------------|--------------------|
| Passwords          | hashSecret | ❌                  |
| OTP / Verification | hashSecret | ❌                  |
| Email recipient    | encrypt    | EMAIL_RECIPIENT_V1 |
| Email payload      | encrypt    | EMAIL_PAYLOAD_V1   |
| TOTP seed          | encrypt    | TOTP_SEED_V1       |
| PII identifiers    | encrypt    | IDENTIFIER_*_V1    |

Any deviation from this matrix is a **Canonical Violation**.

---

#### Enforcement

* Application services depend **ONLY** on `CryptoFacadeInterface`
* Workers (Email, Notifications) are consumers, not cryptography owners
* Cryptography logic MUST NOT appear in Controllers or Domain Services

This contract is **SECURITY-CRITICAL** and MUST NOT be altered
without an explicit architectural decision and documentation update.

---

### **Status Summary**

* Input Validation → **ACTIVE**
* Email Messaging & Delivery → **ACTIVE / CANONICAL**
* Cryptography & Secrets Handling → **ACTIVE / LOCKED**

Any change to these cross-cutting concerns requires:

* Explicit architectural decision
* Documentation update
* Security review where applicable

---

## 🔎 Evidence Index

* **Routing**: `routes/web.php`
* **DI/Config**: `app/Bootstrap/Container.php`
* **Session List Pattern**: `app/Http/Controllers/Ui/SessionListController.php`, `app/Http/Controllers/Api/SessionQueryController.php`
* **Audit Model**: `docs/architecture/audit-model.md`, `app/Domain/Contracts/AuthoritativeSecurityAuditWriterInterface.php`
* **Canonical Template**: `docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md`
* **Placeholders**: `templates/pages/admins.twig`, `templates/pages/roles.twig`
