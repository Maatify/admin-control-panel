# Markdown Reality Conformance Audit

## 1) Executive Summary

- **Overall Documentation Health:** **UNRELIABLE / STALE**
- **Total MD Files Scanned:** 100+
- **Findings Count:**
  - **REALITY VIOLATION:** 5 (Major Architecture, Interfaces, Tables, Routes, Directory Structure)
  - **STALE DOCUMENTATION:** High (Most "Canonical" docs reference old paths)
  - **OVER-CANONICALIZATION:** High (Docs claim "Locked" status on non-existent files)

**Critical Assessment:**
The project has undergone a fundamental architectural shift from a **Monolithic DDD** structure (referenced in docs as `app/Domain`, `app/Infrastructure`) to a **Modular Architecture** (implemented as `app/Modules/AdminKernel`, `app/Modules/AuditTrail`, etc.).

This drift renders the "Canonical" documentation (`docs/PROJECT_CANONICAL_CONTEXT.md`, `docs/KERNEL_BOUNDARIES.md`) factually incorrect in almost every structural claim. While the *logic* and *patterns* (DDD, Layers) might remain, the *locations*, *namespaces*, and *interfaces* are completely different in reality.

---

## 2) Per-File Findings Table

| Severity | Category | File Path | Section / Claim | Observed Reality | Impact | Fix Recommendation |
|:---|:---|:---|:---|:---|:---|:---|
| **BLOCKER** | **REALITY VIOLATION** | `docs/PROJECT_CANONICAL_CONTEXT.md` | **A) Project Snapshot** / Directory Map | Docs claim `app/Domain`, `app/Infrastructure`, `app/Bootstrap`. Reality is `app/Modules/AdminKernel/Domain`, `app/Modules/AdminKernel/Infrastructure`, `app/Modules/AdminKernel/Bootstrap`. | **ALL** path references are broken. Developers cannot find files. | Update Directory Map to reflect Modular structure (`Maatify\AdminKernel`). |
| **BLOCKER** | **REALITY VIOLATION** | `docs/PROJECT_CANONICAL_CONTEXT.md` | **D.1 Audit Logs** | Claims interface `AuthoritativeSecurityAuditWriterInterface` and table `audit_logs`. | Reality is `AuthoritativeAuditOutboxWriterInterface` (in `Maatify\AuthoritativeAudit`) and table `authoritative_audit_outbox` (Outbox Pattern). | Developers will try to inject non-existent interfaces. | Rename interface and update table description to "Outbox + Materialized View". |
| **HIGH** | **REALITY VIOLATION** | `docs/PROJECT_CANONICAL_CONTEXT.md` | **E) Routing** / Route Definitions | Claims `routes/web.php` is the definition file. | `routes/web.php` does NOT exist. Routes are registered via `Maatify\AdminKernel\Http\Routes\AdminRoutes::register()`. | Developers cannot find where to register routes. | Document `AdminRoutes` and `web.php` absence (or restore it if it should exist). |
| **HIGH** | **STALE DOCUMENTATION** | `docs/KERNEL_BOUNDARIES.md` | **3. CORE (LOCKED)** | References `App\Domain\Service\AdminAuthenticationService` and `App\Domain\Contracts\*`. | These classes reside in `Maatify\AdminKernel\Domain\...`. The `App\` namespace maps to `app/` which is mostly empty/modules. | Canonical "Locks" apply to ghosts. | Update namespaces to `Maatify\AdminKernel\...`. |
| **MEDIUM** | **STALE DOCUMENTATION** | `docs/ONBOARDING.md` | **7 Currently Exposed Routes** | Claims routes are exposed in `routes/web.php`. | `routes/web.php` is missing. Routes are likely default in `AdminRoutes`. | Misleading instruction. | Update to reference `AdminRoutes` or the Host App's actual entry point. |
| **MEDIUM** | **REALITY VIOLATION** | `docs/PROJECT_CANONICAL_CONTEXT.md` | **Input Normalization** | Claims `InputNormalizationMiddleware` is a "Canonical Boundary". | Middleware exists in `App\Modules\InputNormalization`, but docs imply it's part of the global `app/Http` stack. | Confusion on where this middleware lives. | Clarify `App\Modules\InputNormalization` namespace. |

---

## 3) Contradiction Matrix

| File A (Claim) | File B (Claim) | Conflict | Reality Alignment |
|:---|:---|:---|:---|
| `docs/PROJECT_CANONICAL_CONTEXT.md` ("Directory Map: `app/Domain`, `app/Infrastructure`") | `composer.json` ("Autoload: `Maatify\AdminKernel` -> `app/Modules/AdminKernel`") | Docs describe Monolith; Composer maps Modules. | **Composer** aligns with Reality (`app/Modules` exists). |
| `docs/ONBOARDING.md` ("Routes in `routes/web.php`") | `public/index.php` (Entry Point) | Docs claim `routes/web.php` exists. Entry point uses `AdminKernel::bootWithOptions`. | **Code** aligns. `routes/web.php` is missing. `AdminKernel` handles routing. |
| `docs/PROJECT_CANONICAL_CONTEXT.md` ("`audit_logs` table") | `database/schema.logging.sql` | Docs claim simple table. Schema defines `authoritative_audit_outbox` + `authoritative_audit_log`. | **Schema** aligns. Implementation is Outbox-based. |

---

## 4) Over-Canonicalization Findings

**"FROZEN" Phases on Moved Code:**
- Docs claim **Phase 1-13 (Core Security/Auth)** is **FROZEN**.
- The code for Auth (`AdminAuthenticationService`, `PasswordService`) has physically moved from `App\Domain` to `Maatify\AdminKernel\Domain`.
- **Finding:** The "Frozen" claim effectively locks a directory that no longer exists. The strict rules technically don't apply to the new location unless interpreted by intent.

**"LOCKED" Boundaries:**
- `docs/KERNEL_BOUNDARIES.md` lists strictly "LOCKED" components using incorrect `App\` namespaces.
- Developers following this might assume `Maatify\AdminKernel` components are fair game because they aren't explicitly listed (by correct name).

---

## 5) Missing or Underdocumented Reality

- **Modular Architecture:** The entire concept of `app/Modules/` containing the core logic (`AdminKernel`, `AuditTrail`, `AuthoritativeAudit`) is undocumented in the high-level docs.
- **AdminRoutes Registration:** The mechanism of `AdminRoutes::register($app)` is the actual routing entry point, not `routes/web.php`.
- **Infrastructure Injection:** The `AdminMiddlewareOptionsDTO` allows injecting infrastructure middleware, but docs don't fully explain how `index.php` wires this (it shows commented out code).

---

## 6) Safe Docs-Only Repair Strategy

1.  **Architecture Update**: Rewrite the "Directory Map" in `docs/PROJECT_CANONICAL_CONTEXT.md` to reflect the **Modular Architecture** (`app/Modules/...`).
2.  **Namespace Correction**: Bulk find-and-replace `App\Domain\` with `Maatify\AdminKernel\Domain\` (and similar) in all "Canonical" docs.
3.  **Interface Renaming**: Update `AuthoritativeSecurityAuditWriterInterface` to `AuthoritativeAuditOutboxWriterInterface` in all docs.
4.  **Routing Clarification**: Update `docs/ONBOARDING.md` and `docs/PROJECT_CANONICAL_CONTEXT.md` to state that `AdminRoutes` provides the default routes, and `routes/web.php` is optional/deprecated or for Host overrides.
5.  **Audit Model Refinement**: Update `docs/PROJECT_CANONICAL_CONTEXT.md` to explicitly describe the **Outbox Pattern** for auditing, matching `app/Modules/AuthoritativeAudit/README.md`.
