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

### 2. Middleware Pipeline (Observed)
All protected routes passed through `routes/web.php` groups are observed to follow this sequence:
1.  `UiRedirectNormalizationMiddleware` (UI only)
2.  `RememberMeMiddleware`
3.  `SessionGuardMiddleware` (Identity)
4.  `SessionStateGuardMiddleware` (State/Step-Up)
5.  `ScopeGuardMiddleware` (Context)
6.  `AuthorizationGuardMiddleware` (RBAC)

### 3. Auditing (Authority & Security Only)
*   **Scope**: `audit_logs` are strictly reserved for **Authority Changes**, **Security-Impacting Actions**, and **Admin Responsibility Events**.
*   **Exclusion**: Routine non-security CRUD or UI-driven mutations are **NOT** automatically audit entries unless they impact authority or security posture.
*   **Mechanism**: When required, auditing uses `AuthoritativeSecurityAuditWriterInterface` within the same `PDO` transaction as the mutation.

---

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

## 📄 F) Pagination Contract (Canonical)

Defined by `SessionQueryController` implementation.

### Request (JSON)
```json
{
  "page": 1,
  "per_page": 20,
  "filters": {
    "status": "active",
    "search": "..."
  }
}
```

### Response (JSON)
```json
{
  "data": [ ... ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 100
  }
}
```

*   **Implementation**: Server-side only. `LIMIT :limit OFFSET :offset`.

---

## 🎨 G) UI/Twig Contract

### 1. Controller Pattern (Observed)
*   **UI Controllers** (`App\Http\Controllers\Ui\`): Render Twig templates. No DB access observed.
*   **Base Layout**: `templates/layouts/base.twig`.
*   **Scripts**: Injected via `{% block scripts %}`.

### 2. Data Flow (Target Pattern)
*   **Page Load**: Renders skeleton (HTML).
*   **Data Fetch**: Client-side JS calls `POST /api/{resource}/query`.
*   **Actions**: Client-side JS calls `POST /api/{resource}/{action}`.

---

## 🗄️ H) Database & Repositories Contract

### 1. Architecture
*   **Access**: `PDO` only. No ORM observed.
*   **Injection**: Repositories injected via Interface into Services.
*   **Strictness**: `declare(strict_types=1)`. Explicit return types.

### 2. Repositories
*   **Location**: `app/Infrastructure/Repository/`.
*   **Pattern**: Methods return Domain Objects or DTOs.
*   **Transactions**: Services manage transactions, Repositories accept `PDO` in constructor (shared connection).

---

## 🏆 I) Canonical Templates

**Reference**: `docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md`

### Target State (Phase 14+)
*   **Page Types**: LIST, CREATE, EDIT, VIEW.
*   **Routing**: strict `GET /{resource}` (UI) and `POST /api/{resource}/query` (API).
*   **Permissions**: 1:1 mapping with routes.

### CURRENT STATE vs CANONICAL GAP ANALYSIS
*   **Compliance**:
    *   `Sessions` (Phase 14.3) is fully compliant.
*   **Gaps (Observed)**:
    *   `Admins`, `Roles`, `Permissions` pages are currently placeholders (`coming soon`). They do not yet implement the Canonical API-First pattern.
    *   Legacy `Web\*Controller` classes (e.g., `LogoutController`) exist alongside `Ui*Controller` wrappers.

---

## 📝 J) Task Playbook

### 1. Add New Admin Panel Page (UI)
*   **Files**:
    *   Create `app/Http/Controllers/Ui/Ui{Resource}Controller.php`.
    *   Create `templates/pages/{resource}.twig`.
    *   Update `routes/web.php` (Group: Protected UI).
*   **Target**: Follow the Canonical Template pattern (View -> API).

### 2. Add New Protected API Endpoint
*   **Files**:
    *   Create `app/Http/Controllers/Api/{Resource}{Action}Controller.php`.
    *   Create `App/Domain/DTO/{Resource}/{Action}RequestDTO.php`.
    *   Update `routes/web.php` (Group: `/api`, Middleware: `AuthorizationGuardMiddleware`).
*   **Security**: Ensure `AuthorizationGuardMiddleware` and proper Permission name.

### 3. Add New DB Table
*   **Files**:
    *   Update `database/schema.sql` (Canonical Schema).
    *   Create `scripts/migrations/xxx_add_table.sql` (if strict migration required).
*   **Code**: Create `app/Infrastructure/Repository/Pdo{Resource}Repository.php` and Interface in `app/Domain/Contracts/`.

---

## ⚔️ K) CONFLICTS
*   **Web vs Ui Controllers**: `app/Http/Controllers/Web/` contains legacy logic. `app/Http/Controllers/Ui/` is the new standard.
    *   *Conflict*: `LoginController` is in `Web` but wrapped by `UiLoginController`.
    *   *Resolution*: Prefer `Ui` controllers for all new UI routes. Keep `Web` only for legacy support until fully migrated.

---

## ❓ L) OPEN QUESTIONS
*   **Asset Management**: How are frontend assets (JS/CSS) specifically for `sessions.twig` managed? The file content is not visible, but `SessionListController` exists. It implies inline scripts or a pattern not yet fully documented.
*   **Legacy Data Loading**: Do the legacy "Web" controllers handle data loading inside the controller (server-side)? Verification needed before refactoring.

---

## 🔎 Evidence Index
*   **Routing**: `routes/web.php`
*   **DI/Config**: `app/Bootstrap/Container.php`
*   **Session List Pattern**: `app/Http/Controllers/Ui/SessionListController.php`, `app/Http/Controllers/Api/SessionQueryController.php`
*   **Audit Model**: `docs/architecture/audit-model.md`, `app/Domain/Contracts/AuthoritativeSecurityAuditWriterInterface.php`
*   **Canonical Template**: `docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md`
*   **Placeholders**: `templates/pages/admins.twig`, `templates/pages/roles.twig`
