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

The system enforces a **strict, non-negotiable separation** between different
types of logging, based on **authority, security impact, and transactional guarantees**.

Logging is **NOT a single concern** in this system.

---

### D.1 Audit Logs (`audit_logs`) — Authoritative (LOCKED)

* **Purpose**: Authoritative history of authority, permission, and security-impacting mutations.
* **Nature**: Source of truth.
* **Interface**: `AuthoritativeSecurityAuditWriterInterface`
* **Storage**: Database only (`audit_logs` table).
* **Schema**:
  * Actor (admin_id)
  * Target Type (string)
  * Target ID
  * Action
  * Changes (JSON diff)

**Hard Requirements:**

* Audit logs MUST be written:
  * Inside the same `PDO` transaction as the mutation
  * Fail-closed (any failure aborts the transaction)
* Audit logs MUST NOT:
  * Use filesystem logging
  * Use PSR-3
  * Be asynchronous
  * Be subject to retention cleanup

Any deviation is a **SECURITY VIOLATION**.

---

### D.2 Security Events (`security_events`) — Observational

* **Purpose**: High-volume security signals and activity tracking.
  * Login
  * Logout
  * Failed authentication
  * Step-up failures
* **Interface**: `SecurityEventLoggerInterface`
* **Storage**: Database only (`security_events` table).
* **Severity**: Info / Warning / Error
* **Behavior**:
  * Best-effort
  * MUST NOT block user-facing flows except for CRITICAL failures

**Rules:**

* Security events are **not authoritative**
* They are **queryable and aggregatable**
* They MUST NOT replace or duplicate audit logs
* Filesystem logging is **FORBIDDEN** for security events

---

### D.3 Application & Infrastructure Logs (PSR-3) — Non-Authoritative

The system allows the use of a **PSR-3 compliant logger** strictly for
**non-authoritative, non-transactional diagnostics**.

#### Approved Implementation

* `maatify/psr-logger` MAY be used as the concrete implementation of:
  * `Psr\Log\LoggerInterface`
* Binding MUST occur **only in the Dependency Injection Container**.

**Approved Container Binding Example:**
```php
LoggerInterface::class => function () {
    return \Maatify\PsrLogger\LoggerFactory::create('slim/app');
},
````

#### Explicitly ALLOWED

* Application debug logs
* Infrastructure and integration failures (SMTP, Redis, IO, queues)
* Development diagnostics
* Operational telemetry

#### Explicitly FORBIDDEN

PSR-3 logging MUST NOT be used for:

* Audit logging
* Security events
* Authority or governance actions
* Domain services
* Transaction-bound operations
* Any logic requiring consistency with database state

PSR-3 logs are **diagnostic only** and MUST NEVER be treated as a source of truth.

---

### D.4 Trait Usage Policy for PSR-3 Loggers (STRICT)

The following traits provided by `maatify/psr-logger` are governed by strict rules.

#### ❌ Forbidden in Application Runtime

The following traits MUST NOT be used inside:

* Domain layer
* Application services
* Security-related code
* Audit-related code

**Forbidden Traits:**

* `Maatify\PsrLogger\Traits\LoggerContextTrait`
* `Maatify\PsrLogger\Traits\StaticLoggerTrait`

**Reason:**

* Bypass Dependency Injection
* Introduce hidden dependencies
* Break testability
* Violate transactional and authority boundaries

---

#### ✅ Narrow Exception (Infrastructure Only)

`StaticLoggerTrait` MAY be used **only** in:

* Bootstrap scripts
* CLI tools
* Cron jobs
* Maintenance utilities

Where Dependency Injection is not available.

This exception **does NOT apply** to runtime application logic.

---

### D.5 Log Retention & Cleanup (PSR-3 Only)

* Log rotation and cleanup (e.g. `LogCleaner`) apply **ONLY** to:

  * Application & infrastructure logs
* Retention policies MUST NOT be applied to:

  * `audit_logs`
  * `security_events`

Any retention or deletion of authoritative logs is forbidden without
explicit legal and architectural approval.

---

### D.6 Summary (Non-Negotiable)

| Log Type         | Storage    | Transactional | PSR-3 |
|------------------|------------|---------------|-------|
| Audit Logs       | Database   | YES (HARD)    | ❌ NO  |
| Security Events  | Database   | NO            | ❌ NO  |
| App / Infra Logs | Filesystem | NO            | ✅ YES |

This separation is **ARCHITECTURE-LOCKED**.

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

### 0. Architectural Decision (LOCKED)

**Status:** LOCKED / MANDATORY  
**Applies to:** All LIST APIs (Sessions, Admins, Roles, and future resources)

The Admin Control Panel enforces a **single canonical model** for:

* Pagination
* Searching (global & column)
* Optional date range filtering

These concerns are **architectural**, not UI conveniences.

---

### 1. Canonical LIST Request Model (Shape)

#### Request (JSON)

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
````

**Field Semantics:**

| Field      | Type   | Required | Notes               |
|------------|--------|----------|---------------------|
| `page`     | int    | yes      | ≥ 1                 |
| `per_page` | int    | yes      | default = 20        |
| `search`   | object | optional | See Search Contract |
| `date`     | object | optional | See Date Contract   |

---

### 2. Canonical Search Contract (LOCKED)

`search` is **optional** and MUST be **omitted** entirely when unused.

If present, it MUST satisfy ALL of the following:

✔️ MUST contain `global` **OR** `columns` (one or both)
✔️ `global` MUST be a **string** if present
✔️ `columns` MUST be an **object: alias → string** if present
✔️ `columns` MUST use **ALIASES ONLY** (never DB columns)

❌ Empty search blocks are forbidden:

```json
{ "search": {} } // INVALID (missing both global and columns)
```

**Valid examples:**

```json
{ "search": { "global": "alice" } }
{ "search": { "columns": { "email": "alice" } } }
{ "search": { "global": "alice", "columns": { "email": "alice" } } }
```

**Semantics:**

* `search.global`: free-text search, applied as **OR** across an allowed whitelist
* `search.columns`: exact filters, applied as **AND**

---

### 3. Date Range Contract (LOCKED)

`date` is **optional** and MUST be **omitted** entirely when unused.

If present, it MUST include BOTH keys:

| Key    | Type              | Required |
|--------|-------------------|----------|
| `from` | Date (YYYY-MM-DD) | yes      |
| `to`   | Date (YYYY-MM-DD) | yes      |

❌ Partial date ranges are forbidden:

```json
{ "date": { "from": "2024-01-01" } } // INVALID
```

**Notes:**

* Date filtering applies to **one backend-defined column**
* Dynamic date columns are **FORBIDDEN**
* UI MUST NOT assume date support unless declared

---

### 4. Pagination Semantics (LOCKED)

Pagination is **server-side only**:

* **LIMIT** = `:per_page`
* **OFFSET** = `(:page - 1) * :per_page`

Clients MUST NOT implement client-side pagination.

---

### 5. Canonical Response Envelope (LOCKED)

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

Where:

| Field      | Meaning                               |
|------------|---------------------------------------|
| `page`     | current page                          |
| `per_page` | rows per page                         |
| `total`    | total rows before filtering           |
| `filtered` | rows after global/column/date filters |

---

### 6. Explicit Prohibitions (NON-NEGOTIABLE)

The following are **strictly forbidden** on Canonical LIST APIs:

❌ `filters`  
❌ `limit`  
❌ `items` / `meta`  
❌ `from_date` / `to_date`  
❌ client-side pagination  
❌ client-side filtering  
❌ UI-defined searchable columns  
❌ dynamic SQL column injection  
❌ multiple date columns  
❌ implicit filtering  
❌ undocumented request keys

Any usage of the above is a **Canonical Violation**.

---

### 7. Enforcement Model

* Backend declares capabilities (global search / columns / date)
* UI MUST reflect backend-declared capabilities only
* Backend owns all filtering logic
* Repositories must apply filters explicitly
* DTOs define **shape only**, not SQL

---

## 🧩 F.1) Reusable LIST Infrastructure (Canonical)

**Status:** ARCHITECTURE-LOCKED**

See reference implementation in:

```
SessionQueryController
SessionListReaderInterface
ListFilterResolver
ListQueryDTO
PaginationDTO
```

Principles:

* Reuse is **contract-based**, not copy-paste
* Repositories remain **simple & explicit**
* No generic SQL builders
* No dynamic WHERE generation
* No dynamic column reflection

Any deviation is a **Canonical Violation**.

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

## **2. Email Messaging & Delivery (CANONICAL INFRASTRUCTURE)**

**Status:** ARCHITECTURE-LOCKED / ACTIVE
**Scope:** Cross-Domain Infrastructure
**Phase:** 14+ (Async Infrastructure)

---

### 📌 Architectural Position (CRITICAL)

The Email system is a **standalone, cross-domain infrastructure capability**.

It is **NOT**:

* A sub-module of Notifications
* A feature tied to user-facing flows
* A domain-owned service

It **IS**:

* A reusable async delivery pipeline
* A shared infrastructure used by multiple domains
* A transport-agnostic message delivery mechanism

This distinction is **ARCHITECTURE-CRITICAL**.

---

### 🧱 Ownership Model

| Layer                            | Responsibility                             |
|----------------------------------|--------------------------------------------|
| **Email Module**                 | Queueing, Encryption, Rendering, Transport |
| **Notification System**          | Intent, Routing, Preferences               |
| **Workers (CLI)**                | Decryption & Physical Delivery             |
| **Domains (Auth, System, Jobs)** | Produce email intents                      |

**Notifications consume Email.
Email does NOT depend on Notifications.**

---

### 📤 Email Queue (Canonical Infrastructure)

* All email delivery is **asynchronous**
* No service, controller, or domain may send emails directly
* All emails MUST be enqueued into `email_queue`
* Queue rows are treated as **infrastructure output**, not domain events

```text
Domain / Service
        ↓
 EmailQueueWriter
        ↓
   email_queue (encrypted)
        ↓
   Email Worker (CLI)
        ↓
   SMTP / Transport
```

---

### 🔗 Domain Binding (Traceability Only)

Each email MUST be bound to a domain entity:

* `entity_type`: `admin | user | system | external`
* `entity_id`: string / int (casted)

Purpose:

* Debugging
* Support
* Failure tracing
* Cross-domain reuse

❌ This binding is NOT used for:

* Authorization
* Permission checks
* Business decisions

---

### 🔐 Encryption Policy (MANDATORY)

All sensitive email data is encrypted **at rest**:

| Field            | Encryption                 |
|------------------|----------------------------|
| Recipient Email  | AES-GCM                    |
| Rendered Payload | AES-GCM                    |
| Subject          | Encrypted (inside payload) |
| HTML Body        | Encrypted                  |

Rules:

* No plaintext email data in database
* Encryption uses **context-derived keys (HKDF)**
* Decryption allowed **only in worker process**

---

### 🧩 Crypto Contexts (LOCKED)

The following contexts are **fixed and versioned**:

| Context              | Usage                       |
|----------------------|-----------------------------|
| `email:recipient:v1` | Recipient encryption        |
| `email:payload:v1`   | Rendered payload encryption |

❌ Dynamic or runtime-generated contexts are forbidden.

---

### 🖼️ Rendering (Twig-Based)

* All email content is rendered via Twig
* Templates are language-scoped
* Templates contain **no business logic**

```
templates/
└── emails/
    ├── layouts/
    ├── otp/
    ├── verification/
    └── system/
```

DTOs provide semantic data only.
Templates control presentation only.

---

### ⚙️ Execution & Failure Semantics

Queue lifecycle:

```
pending → processing → sent | failed | skipped
```

Rules:

* Failures MUST NOT block UI or API flows
* Retry is infrastructure-level only
* Email delivery does NOT emit audit logs
* PSR-3 logging is allowed (diagnostic only)

---

### 🚫 Explicit Non-Goals

The Email system MUST NOT:

* Perform authorization
* Emit audit logs
* Influence authentication state
* Block synchronous requests
* Contain domain logic

---

### 🔗 Relationship to Notifications (Explicit)

Notifications are a **producer & consumer**, not an owner.

* NotificationDispatcher may enqueue emails
* Notification routing does NOT control Email execution
* Email system can be used **without Notifications**

Examples of non-notification email usage:

* OTP delivery
* Password reset
* System alerts
* External integrations
* Scheduled jobs

---

### 🔒 Architecture Lock

This section is **ARCHITECTURE-LOCKED**.

Any of the following requires:

* Explicit ADR
* Security review
* Documentation update

❌ Making Email dependent on Notifications
❌ Sending emails synchronously
❌ Storing plaintext email data
❌ Bypassing crypto contexts

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

## 🔐 Cryptography — Canonical Implementation (As-Built)

### Entry Point
The application utilizes a unified facade for all cryptographic operations, exposed via `App\Modules\Crypto\DX\CryptoProvider`. This class is registered in the DI container and provides three distinct pipelines:
1.  **Context-Bound Encryption:** `context(string $context)` returns a `ReversibleCryptoService` derived from root keys using HKDF.
2.  **Direct Encryption:** `direct()` returns a `ReversibleCryptoService` using root keys directly (Internal/Legacy use only).
3.  **One-Way Secrets:** `password()` exposes the `PasswordService` for hashing and verification.

### Reversible Encryption Pipeline (Context-Aware)
The primary encryption flow adheres to a "Derive-then-Encrypt" model to ensure domain separation.

1.  **Input:** Plaintext string + Context String (e.g., `email:recipient:v1`).
2.  **Root Key Resolution:** `KeyRotationService` provides the currently `ACTIVE` root key (from `CRYPTO_KEYS` or `EMAIL_ENCRYPTION_KEY`).
3.  **Key Derivation (HKDF):**
    *   The `HKDFService` derives a unique 32-byte key for the specific context using `hash_hmac('sha256')` (RFC 5869 expand-only).
    *   Context strings must strictly contain `:v` to enforce versioning.
4.  **Encryption (AES-256-GCM):**
    *   Algorithm: `Aes256GcmAlgorithm` (using `openssl_encrypt`).
    *   Key: 32-byte derived key.
    *   IV: 12-byte random binary (96-bit).
    *   Tag: 16-byte authentication tag (128-bit).
5.  **Output:** Returns an array containing:
    *   `result`: `ReversibleCryptoEncryptionResultDTO` (Cipher, IV, Tag).
    *   `key_id`: The ID of the root key used (e.g., `v1`).
    *   `algorithm`: Enum `AES_256_GCM`.

### One-Way Secrets (Passwords)
Password handling is managed by `App\Modules\Crypto\Password\PasswordHasher`, delegating to `PasswordService`.

*   **Algorithm:** `Argon2id` (via PHP `password_hash`).
*   **Peppering:** Mandatory server-side pepper (from `PASSWORD_PEPPER` env).
    *   Logic: `hash_hmac('sha256', password, pepper, true)` is calculated *before* passing to Argon2id.
*   **Verification:**
    1.  Re-calculates the peppered HMAC of the input.
    2.  Verifies against the stored hash using `password_verify`.
    3.  Supports rotation via `PASSWORD_PEPPER_OLD`.

### Blind Indexing
Blind indexes are used to look up encrypted identifiers (e.g., Email) without decryption.
*   **Algorithm:** `hash_hmac('sha256', raw_value, key)`.
*   **Key:** `EMAIL_BLIND_INDEX_KEY` (loaded via `AdminConfigDTO`).
*   **Implementation:** Calculated via `hash_hmac` before being passed to Domain Services or Repositories.
*   **Purpose:** Allows exact-match lookups (`SELECT ... WHERE blind_index = ?`) on encrypted columns.

### Key Management
Keys are managed by `App\Modules\Crypto\KeyRotation\KeyRotationService`, populated via `App\Bootstrap\Container`.

*   **Source:** `CRYPTO_KEYS` (JSON array of `{id, key}`) or legacy `EMAIL_ENCRYPTION_KEY`.
*   **Active Key:** Strictly defined by `CRYPTO_ACTIVE_KEY_ID`.
*   **States:**
    *   `ACTIVE`: Used for new encryption and decryption.
    *   `INACTIVE` / `RETIRED`: Used for decryption only.
*   **Safety:** The service exposes raw key material only to the `CryptoContextFactory` and `CryptoDirectFactory`, never to application logic.

### Failure Semantics
The crypto layer enforces strict "fail-closed" behavior:
*   **Encryption Failure:** Throws `RuntimeException` (e.g., OpenSSL failure).
*   **Decryption Failure:** Throws `App\Modules\Crypto\Reversible\Exceptions\CryptoDecryptionFailedException` for *any* failure (tag mismatch, wrong key, corruption).
*   **Missing Key:** Throws `CryptoKeyNotFoundException` if the requested `key_id` is unknown.
*   **Invalid Context:** Throws `InvalidContextException` if the context string is empty or missing versioning.

### Explicit Non-Goals
*   **Key Loading:** The core services (`ReversibleCryptoService`, `KeyRotationService`) do not load configuration or environment variables; they rely on injection.
*   **Storage:** The crypto layer returns DTOs and does not handle database persistence.
*   **Serialization:** The layer does not serialize the encrypted payload (e.g., to Base64 or JSON); it returns raw binary DTOs.
*   **Mixed Mode:** The `ReversibleCryptoService` cannot perform hashing, and the `PasswordService` cannot perform encryption.

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
