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

### 2. Middleware Pipeline (Observed & Canonical)

The middleware pipeline is now explicitly registered via `AdminRoutes::register()`.

**Canonical Pipeline (Execution Order):**

1.  `RequestIdMiddleware` (Infrastructure - Generates ID)
2.  `RequestContextMiddleware` (Infrastructure - Initializes Context)
3.  `HttpRequestTelemetryMiddleware` (Infrastructure - Observability)
4.  `InputNormalizationMiddleware` (Canonical Boundary - Sanitizes Input)
5.  `RecoveryStateMiddleware` (System State - Checks Recovery Mode)
6.  `UiRedirectNormalizationMiddleware` (UI only - Error Redirection)
7.  `RememberMeMiddleware` (Auth - Cookie persistence)
8.  `SessionGuardMiddleware` (Identity - Loads Admin)
9.  `AdminContextMiddleware` (Context - Hydrates Request Attribute)
10. `SessionStateGuardMiddleware` (State - Enforces Step-Up)
11. `ScopeGuardMiddleware` (Context - Enforces Scope)
12. `AuthorizationGuardMiddleware` (RBAC - Enforces Permissions)

**Host Application Integration:**

Host applications MUST use `AdminRoutes::register($app)` to mount the Admin Panel.
This method accepts an optional `AdminMiddlewareOptionsDTO` to control the infrastructure middleware stack.

```php
AdminRoutes::register($app, new AdminMiddlewareOptionsDTO(
    withInfrastructure: true // Default: Registers RequestId/Context/Telemetry
));
```

This ensures that the canonical middleware pipeline is always applied correctly, preventing unintentional omissions.

**Execution Model Note:**

The middleware pipeline follows Slim’s **LIFO (Last-In-First-Out)** execution model.
Middleware is registered in reverse order of the list above.

* `RequestIdMiddleware` is added LAST (runs FIRST).
* `AuthorizationGuardMiddleware` is added EARLY (runs LAST before controller).
---

### 2.1 Internal Context Plumbing (LOCKED)

The request attribute `admin_id` is a **strictly internal implementation detail**
used exclusively for context transformation between middleware layers.

**Rules (NON-NEGOTIABLE):**

* `admin_id` MAY ONLY be:
  * **Produced** by `SessionGuardMiddleware`
  * **Consumed** by `AdminContextMiddleware`
* Controllers, Services, Readers, and Guards MUST NOT:
  * Access `getAttribute('admin_id')`
  * Depend on `admin_id` directly in any form

**Authoritative Identity Source:**

* All application layers MUST consume:
  ```php
  $request->getAttribute(\App\Context\AdminContext::class)
  ```

* `AdminContext` is the **single source of truth** for admin identity beyond middleware.

Any direct usage of `admin_id` outside the middleware boundary is a
**CANONICAL VIOLATION**.

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

* **Scope**: `audit_logs` are strictly reserved for **Authority Changes**, **Security-Impacting Actions**, and **Admin Responsibility Events**.
* **Exclusion**: Routine non-security CRUD or UI-driven mutations are **NOT** automatically audit entries unless they impact authority or security posture.
* **Mechanism**: When required, auditing uses `AuthoritativeSecurityAuditWriterInterface` within the same `PDO` transaction as the mutation.

---

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

* **Purpose**: High-volume **security signals and security-related events**.

  * Login
  * Logout
  * Failed authentication
  * Step-up failures
* **Interface**: `SecurityEventLoggerInterface`
* **Storage**: Database only (`security_events` table).
* **Severity**: Info / Warning / Error
* **Behavior**:

  * Best-effort
  * MUST NOT block user-facing flows except for **CRITICAL** failures

**Rules:**

* Security events are **not authoritative**
* They are **queryable and aggregatable**
* They MUST NOT replace or duplicate audit logs
* Filesystem logging is **FORBIDDEN** for security events

---

### Context Injection Rule (HARD)

* All **Audit** and **Security** events MUST receive `request_id`
  via **constructor injection**.
* `request_id` MUST NOT be:
  * Optional
  * Nullable
  * Generated lazily
* Missing or invalid `request_id` MUST cause the operation to
  **fail-closed immediately**.

This rule is enforced at the DTO level and is considered
**SECURITY-CRITICAL**.

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
```

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

### D.5 Log Retention & Cleanup

* Log rotation and cleanup apply **ONLY** to:

  * Application & infrastructure logs (PSR-3)
  * Activity logs (`activity_logs`)
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
| Activity Logs    | Database   | NO            | ❌ NO  |
| App / Infra Logs | Filesystem | NO            | ✅ YES |

This separation is **ARCHITECTURE-LOCKED**.

---

## 🧾 D.7 Activity Logs (`activity_logs`) — Operational User Activity Tracking

**Status:** ARCHITECTURE-APPROVED / ACTIVE
**Scope:** Admin Panel (UI + API Mutations)
**Nature:** Observational / Non-Authoritative
**Audience:** Operations, Management, Compliance, Support

---

## 📌 Purpose (Why this exists)

`activity_logs` provide a **human-readable, queryable trail**
of **what admins and employees do inside the system**.

They answer questions such as:

* مين عمل تعديل؟
* عمل تعديل فين؟
* عدّل بيانات مين؟
* إمتى التعديل حصل؟
* هل التعديل كان Create / Update / Delete؟
* إيه الصفحة أو المورد اللي حصل فيه التغيير؟

`activity_logs` are **NOT** a security mechanism
and **NOT** a legal source of truth.

They exist purely for **staff activity tracking and operational transparency**.

---

## 🧭 What Activity Logs Are (and Are NOT)

### ✅ Activity Logs ARE

* A **staff activity timeline**
* A **management & monitoring tool**
* A way to understand **who changed what**
* A bridge between raw DB changes and human understanding

### ❌ Activity Logs are NOT

* Audit logs
* Security events
* Authorization records
* Transaction guards
* A replacement for `audit_logs`

---

## 🧱 Storage & Interface

* **Storage:** Database (`activity_logs` table)
* **Nature:** Best-effort (non-transactional)
* **Interface:** `ActivityLoggerInterface` (Infrastructure concern)

### Required Core Fields (Conceptual)

| Field             | Meaning                                  |
|-------------------|------------------------------------------|
| `actor_admin_id`  | Who performed the action                 |
| `actor_role`      | Role at time of action (snapshot)        |
| `action`          | create / update / delete / view          |
| `resource_type`   | admins, users, products, orders, etc     |
| `resource_id`     | ID of the affected entity                |
| `target_admin_id` | If acting *on another admin*             |
| `summary`         | Human-readable description               |
| `changes`         | Lightweight before/after diff (optional) |
| `source`          | ui / api                                 |
| `created_at`      | Timestamp                                |

---

## 🧠 Canonical Use Cases (MANDATORY)

Activity Logs MUST be written for:

* CRUD operations performed by admins
* Editing another admin’s data
* Editing user/customer data
* Assigning or modifying business data (prices, products, settings)
* Any action where **management may later ask “who did this?”**

---

## 🚫 Explicit Exclusions (NON-NEGOTIABLE)

Activity Logs MUST NOT be used for:

❌ Authentication attempts
❌ Login / logout
❌ Permission changes
❌ Role grants / revokes
❌ Security failures
❌ Step-Up / OTP / Recovery Mode
❌ System bootstrap actions

These belong to:

| Concern              | Correct Log       |
|----------------------|-------------------|
| Authority decisions  | `audit_logs`      |
| Security attempts    | `security_events` |
| Crashes / exceptions | PSR-3 logs        |

---

## 🔄 Relationship to Audit Logs (CRITICAL DISTINCTION)

| Aspect           | Audit Logs            | Activity Logs           |
|------------------|-----------------------|-------------------------|
| Purpose          | Authority & Security  | Staff behavior tracking |
| Transactional    | YES (HARD)            | NO                      |
| Fail-Closed      | YES                   | NO                      |
| Legal / Forensic | YES                   | NO                      |
| Volume           | Low                   | High                    |
| Audience         | Security / Compliance | Management / Ops        |

> **Rule:**
> If both logs apply → **BOTH are written**
> (Audit for authority, Activity for visibility)

---

## ⚙️ Executor Responsibilities

### Backend Executors (Controllers / Services)

* MUST emit Activity Logs for:

  * Successful CRUD mutations
* MUST NOT:

  * Block execution if activity logging fails
  * Wrap activity logging inside DB transactions
  * Treat activity logs as authoritative

---

### Notification / Delivery Executors

* ❌ MUST NOT write Activity Logs

---

### System / CLI Executors

* ❌ MUST NOT write Activity Logs
  (Bootstrap, migrations, maintenance are out of scope)

---

## 🧪 Testing Rules

* Tests MAY assert presence of activity logs
* Tests MUST NOT fail if activity logging is unavailable
* Activity logs are **non-blocking by design**

---

## 🧠 Design Rationale

We intentionally separate:

* **Authority (Audit)**
* **Security (Security Events)**
* **Operations (Activity Logs)**

To avoid:

* Audit log pollution
* Legal ambiguity
* Security signal noise
* Misuse of PSR-3 logs

This separation is **ARCHITECTURE-INTENTIONAL**.

---

## 🔒 Architectural Status

* This section is **APPROVED**
* This section does **NOT** weaken existing audit or security guarantees
* This section introduces **zero coupling** with Auth or Security layers

Any change requires:

* Architectural review
* Documentation update
* Explicit approval

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

**Execution Model Note:**

The middleware pipeline follows Slim’s **LIFO (Last-In-First-Out)** execution model.

This guarantees:

* `RequestIdMiddleware` runs BEFORE `RequestContextMiddleware`
* `SessionGuardMiddleware` runs BEFORE `AdminContextMiddleware`

Middleware order MUST be evaluated based on execution order,
not registration order.

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
```

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

## 🧪 I) Testing & Verification Model (CANONICAL)

**Status:** ARCHITECTURE-LOCKED / MANDATORY
**Applies to:** All API endpoints, security-sensitive flows, and database-backed operations

Testing in this project is **not optional** and **not advisory**.
It is a **core architectural mechanism** used to verify correctness, security, and fail-closed behavior of the system.

Any implementation that violates the rules in this section is considered an
**ARCHITECTURE VIOLATION**, regardless of functional correctness.

---

### I.1 Testing Scope & Classification

The system recognizes the following test categories:

| Test Type            | Purpose                                    | Mandatory     |
|----------------------|--------------------------------------------|---------------|
| Unit Tests           | Pure logic (DTOs, helpers, pure functions) | Optional      |
| Integration Tests    | Services + Repositories + DB               | Required      |
| Endpoint / E2E Tests | Full HTTP pipeline verification            | **MANDATORY** |

**Endpoint / Integration Tests are the authoritative verification mechanism**
for system behavior.

Unit tests alone are **insufficient** for validating this system.

---

### I.2 Endpoint / Integration Tests (MANDATORY)

Every API endpoint that:

* Mutates state
* Reads protected data
* Enforces authorization
* Triggers audit or security events
* Participates in authentication or step-up flows

**MUST** have at least one corresponding **Endpoint Test**.

Endpoint Tests MUST:

* Execute via the **HTTP layer**
* Pass through the **full middleware pipeline**
* Use real request objects, headers, cookies, and payloads
* Exercise real controllers, services, repositories, and guards

❌ Calling services or repositories directly is **FORBIDDEN**
❌ Bypassing middleware is **FORBIDDEN**

---

### I.3 Database Usage Rules for Tests

Endpoint and Integration Tests MUST interact with a **real database engine**.

Allowed options:

* Dedicated test database (e.g. `admin_control_panel_test`)
* Ephemeral database instance
* SQLite (only if behavior matches production semantics)

❌ Using development or production databases is **STRICTLY FORBIDDEN**

Tests MUST NOT rely on mocks or fakes for:

* Database access
* Authorization
* Auditing
* Security events

---

### I.4 Isolation & Transaction Boundaries

Each test MUST be fully isolated.

Isolation MUST be achieved by one of the following mechanisms:

#### Option A — Transaction Rollback (Preferred)

* Begin a transaction before the test
* Execute the endpoint
* Assert results
* Roll back the transaction

#### Option B — Database Reset

* Truncate affected tables
* Reload schema or fixtures
* Ensure a clean state before each test

**Hard Rule:**

> No test may leave persistent side effects that affect another test.

Test execution order MUST NOT matter.

---

### I.5 Security & Fail-Closed Verification

Endpoint Tests MUST explicitly verify **fail-closed behavior**.

This includes asserting that:

* Unauthorized access is denied
* Missing permissions are rejected
* Invalid state transitions are blocked
* Audit failures abort the transaction
* Security invariants are enforced

If a failure occurs during:

* Authorization
* Audit logging
* Security validation

The operation MUST fail completely.

A test that only asserts “success paths” is considered **INCOMPLETE**.

---

### I.6 Explicit Prohibitions (NON-NEGOTIABLE)

The following are **STRICTLY FORBIDDEN** in tests:

❌ Mocking authorization decisions
❌ Mocking audit writers
❌ Mocking security event loggers
❌ Skipping middleware
❌ Direct service invocation instead of HTTP
❌ Sharing database state across tests
❌ Tests that depend on execution order
❌ Tests without cleanup or rollback

Violations invalidate the test regardless of assertions.

---

### I.7 Definition of “Done” (Testing)

An endpoint or feature is considered **DONE** only when:

* Endpoint / Integration Tests exist
* Tests pass against a real database
* Fail-closed behavior is verified
* Authorization and audit rules are exercised
* No test leaves residual state

UI implementation MUST NOT proceed
until the corresponding endpoint tests are complete and passing.

---

### I.8 Architectural Authority

This section is **CANONICAL**.

Any change to testing strategy, scope, or enforcement requires:

* Explicit architectural decision
* Documentation update
* Reviewer approval

Ad-hoc or convenience-based testing approaches are **NOT ACCEPTABLE**.

---

## 🏆 J) Canonical Templates

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

## 📝 K) Task Playbook

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

## ⚔️ L) CONFLICTS

* **Web vs Ui Controllers**: `app/Http/Controllers/Web/` contains legacy logic. `app/Http/Controllers/Ui/` is the new standard.

  * *Conflict*: `LoginController` is in `Web` but wrapped by `UiLoginController`.
  * *Resolution*: Prefer `Ui` controllers for all new UI routes. Keep `Web` only for legacy support until fully migrated.

---

## ❓ M) OPEN QUESTIONS

* **Asset Management**: How are frontend assets (JS/CSS) specifically for `sessions.twig` managed? The file content is not visible, but `SessionListController` exists. It implies inline scripts or a pattern not yet fully documented.
* **Legacy Data Loading**: Do the legacy "Web" controllers handle data loading inside the controller (server-side)? Verification needed before refactoring.

---

## 🧩 N) Cross-Cutting Concerns (Canonical)

The system defines several modules that cross application boundaries and affect multiple layers.

---

### **0. Input Normalization (CANONICAL BOUNDARY)**

**Status:** ARCHITECTURE-LOCKED / MANDATORY
**Applies to:** All Web & API requests
**Authoritative Decision:** `docs/adr/ADR-006-input-normalization.md`

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
> `docs/adr/ADR-006-input-normalization.md`

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

### **3. Cryptography & Secrets Handling (CANONICAL)**

**Status:** ARCHITECTURE-LOCKED / CLOSED (Post-Audit)
**Scope:** All layers (Controllers, Readers, Domain, Infrastructure, Workers)

The system defines a **single, unified cryptography contract** for handling:
sensitive data, secrets, identifiers, encrypted payloads, and key rotation.

Cryptography is treated as an **infrastructure capability**, not a feature.

---

#### Authority Boundary (HARD RULE)

Controllers, Readers, and Repositories MUST NOT:

❌ Perform cryptographic operations (hashing/encryption/decryption)
❌ Know cryptographic algorithms
❌ Receive or access key material (direct ENV keys)
❌ Call low-level crypto primitives (`openssl_*`, `hash_*`, `random_bytes`, HKDF)

**Exception (explicitly allowed):**

* Low-level primitives MAY exist **only** inside approved Crypto modules and the canonical password service (e.g., HKDF implementation, PasswordService internals).
* Application layers must treat crypto as an intent-driven capability and MUST NOT re-implement primitives.

All reversible-encryption and identifier-crypto intent MUST be executed via **Crypto Application Services** only.

---

#### Crypto Application Services (MANDATORY ABSTRACTION)

Crypto Application Services are the ONLY allowed entry point for **application-layer intent** involving:

* Reversible encryption / decryption (data-at-rest)
* Identifier encryption / decryption
* Blind index derivation

**Allowed services:**

* `App\Application\Crypto\AdminIdentifierCryptoServiceInterface`
* `App\Application\Crypto\NotificationCryptoServiceInterface`
* `App\Application\Crypto\TotpSecretCryptoServiceInterface`

**Password note (canonical exception):**

* Password hashing/verification is governed by `App\Domain\Service\PasswordService` (Argon2id + Pepper Ring).
* `App\Application\Crypto\PasswordCryptoServiceInterface` MAY exist as a wrapper/adapter, but password correctness and policy remain owned by `PasswordService`.

Rules:

* These services are thin adapters (intent → crypto operation).
* They MAY use the Crypto pipeline (`CryptoProvider`, `CryptoContext`, rotation, HKDF) internally.
* They MUST NOT contain business logic.
* Controllers/Readers/Repositories MUST NOT derive blind indexes, encrypt, or decrypt directly.

---

#### Crypto Context Registry (LOCKED)

All reversible encryption operations MUST use predefined, versioned contexts from:

`App\Domain\Security\CryptoContext`

Rules:

* Contexts MUST be referenced via `CryptoContext` constants only.
* Inline/literal context strings are STRICTLY FORBIDDEN.
* Adding/changing a context requires ADR + security review.

---

#### Reversible Encryption (Data-at-Rest)

Used for:

* Email recipients & payloads
* TOTP seeds
* Encrypted identifiers (PII)

Characteristics:

* Reversible (AES-GCM)
* Uses Key Rotation
* Uses context-based key derivation (HKDF)
* Stores `key_id` with encrypted payload
* Decryption fails hard if key is missing or payload is invalid

Encrypted outputs are represented exclusively by:

`App\Domain\DTO\Crypto\EncryptedPayloadDTO`

---

#### One-Way Secrets (Passwords)

Used for:

* Passwords (Argon2id + pepper)
* OTP/verifications (one-way hashing as applicable)

Rules:

* No reversible encryption for passwords/OTP
* Pepper is REQUIRED for passwords
* Password verification is deterministic

**Pepper Ring (LOCKED):**

* `PASSWORD_PEPPERS` (JSON map of pepper_id → pepper_secret)
* `PASSWORD_ACTIVE_PEPPER_ID`
* Stored hashes retain their `pepper_id`
* Verification uses stored `pepper_id` (NO trial across peppers)
* Upgrade-on-login upgrades legacy/old pepper_id to active pepper_id

---

#### Blind Indexing (Identifiers)

Blind indexes are used for exact-match lookup without decryption.

Rules:

* Blind index derivation MUST occur ONLY inside `AdminIdentifierCryptoService`.
* Controllers MUST NOT compute blind indexes.
* Controllers MUST NOT receive `EMAIL_BLIND_INDEX_KEY` or any blind-index secret.

Key:

* `EMAIL_BLIND_INDEX_KEY` (env) is read internally by `AdminIdentifierCryptoService` and is never injected into controllers.

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

Any deviation is a **Canonical Violation**.

---

#### ENV & Boot Fail-Closed (HARD)

The application MUST fail-closed if required crypto or password env is missing or malformed.

Required (conceptual):

* `CRYPTO_KEYS`
* `CRYPTO_ACTIVE_KEY_ID`
* `EMAIL_BLIND_INDEX_KEY`
* `PASSWORD_PEPPERS`
* `PASSWORD_ACTIVE_PEPPER_ID`
* Argon2 options / password hashing options as configured

Legacy env sources MUST NOT be used:

* `EMAIL_ENCRYPTION_KEY` (forbidden / removed)

---

## 🔐 Cryptography — Canonical Implementation (As-Built)

> ⚠️ **IMPORTANT — DESCRIPTIVE ONLY**
>
> This section documents the **current implementation (as-built)**.
> It is **NOT** a source of architectural authority and MUST NOT be used
> to justify refactoring, redesign, or deviation from the rules above.

### Entry Points (Observed)

Reversible encryption and identifier crypto are performed through the Crypto pipeline (internally consumed by the crypto application services):

* `App\Modules\Crypto\DX\CryptoProvider`

  * `context(string $context)` → reversible crypto service (HKDF-derived key)
  * Key rotation is handled internally via `KeyRotationService`

Password hashing/verification is performed via the canonical password service:

* `App\Domain\Service\PasswordService`

  * Argon2id + Pepper Ring
  * deterministic verification using stored `pepper_id`
  * upgrade-on-login to active pepper

### Reversible Encryption Pipeline (Context-Aware)

1. **Input:** Plaintext string + `CryptoContext::*` constant
2. **Root Key Resolution:** `KeyRotationService` resolves current ACTIVE key from:

  * `CRYPTO_KEYS` + `CRYPTO_ACTIVE_KEY_ID` (ONLY)
3. **Key Derivation (HKDF):**

  * Uses `hash_hmac('sha256')` in the approved HKDF implementation
  * Context must include versioning (e.g., `:v1`)
4. **Encryption:** AES-256-GCM (OpenSSL), with IV and TAG
5. **Output:** encryption metadata + `key_id`, mapped into `EncryptedPayloadDTO` by the application crypto services

### Failure Semantics (Fail-Closed)

* Encryption failure → throws (runtime exception)
* Decryption failure → throws (crypto-specific failure exception)
* Missing key_id → throws (key not found)
* Invalid/malformed context → throws (invalid context)

---

### Architectural Closure Statement (LOCKED)

As of the latest closure audit, controller-level crypto has been eliminated and the authority boundary is enforced:

* Controllers do not perform crypto
* Controllers do not receive key material
* Blind index derivation exists only inside `AdminIdentifierCryptoService`
* Crypto Application Services are the sole authority for reversible/identifier crypto intent

Further discussion/refactor on this topic is **FORBIDDEN** unless a new ADR is opened.

## 🔎 Evidence Index

* **Routing**: `routes/web.php`
* **DI/Config**: `app/Bootstrap/Container.php`
* **Session List Pattern**: `app/Http/Controllers/Ui/SessionListController.php`, `app/Http/Controllers/Api/SessionQueryController.php`
* **Audit Model**: `docs/architecture/audit-model.md`, `app/Domain/Contracts/AuthoritativeSecurityAuditWriterInterface.php`
* **Canonical Template**: `docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md`
* **Placeholders**: `templates/pages/admins.twig`, `templates/pages/roles.twig`
