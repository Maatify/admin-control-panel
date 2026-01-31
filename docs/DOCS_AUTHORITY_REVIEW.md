# /docs Authority Review Report

## 1. Executive Summary
- Is the authority hierarchy in `/docs` clear and unambiguous? **NO**

## 2. Authority Classification

### Canonical Documents
The following documents define binding rules, contracts, and architecture locks.

- **`docs/index.md`**
  - **Justification:** Explicitly defines itself as the "single navigation authority" and sets the A0-A2 authority levels.
- **`docs/PROJECT_CANONICAL_CONTEXT.md`**
  - **Justification:** Designated as Level A0 (Absolute Authority) by `index.md`. Defines core security invariants and context rules.
- **`docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md`**
  - **Justification:** Designated as Level A1. Defines the mandatory template for all UI pages and API endpoints.
- **`docs/API.md`**
  - **Justification:** Designated as Level A2. Defines the authoritative API contract; undocumented endpoints are considered non-existent.
- **`docs/KERNEL_BOUNDARIES.md`**
  - **Justification:** Defines the "LOCKED" vs "EXTENSIBLE" components of the system. Explicitly states that violations are kernel breaches.
- **`docs/security/authentication-architecture.md`**
  - **Justification:** Self-identifies as a "normative security specification" and "LOCKED".
- **`docs/architecture/logging/UNIFIED_LOGGING_DESIGN.md`** (and subordinates)
  - **Justification:** Identified in `index.md` as a "Locked Canonical Design" for the logging subsystem.
- **`docs/architecture/input-validation.md`**
  - **Justification:** Referenced as "Status: ARCHITECTURE-APPROVED / ACTIVE" and "Canonical Spec" in `PROJECT_CANONICAL_CONTEXT.md`.
- **`docs/architecture/notification-delivery.md`**
  - **Justification:** Referenced as "Canonical Spec" and "Status: ARCHITECTURE-LOCKED" in `PROJECT_CANONICAL_CONTEXT.md`.

### Supporting / Reference Documents
The following documents are guides, checklists, or explanations with no independent authority.

- **`docs/CONTRIBUTING.md`**
  - **Justification:** Explicitly states "Status: Helper / Operational Checklist" and "Nature: Non-binding".
- **`docs/ONBOARDING.md`** (and `ONBOARDING-AR.md`)
  - **Justification:** Status is "Current State" / "Guide". Refers readers to `API.md` and `routes/web.php` for authority.
- **`docs/KERNEL_BOOTSTRAP.md`**
  - **Justification:** Explains the mechanics of the kernel boot process ("Overview") but delegates policy to the host application.
- **`docs/UI_EXTENSION.md`**
  - **Justification:** "Guide" on how to override UI components; relies on the contracts defined elsewhere.

### Obsolete / Legacy Documents
The following documents reflect superseded models and create risk of confusion.

- **`docs/architecture/audit-model.md`**
  - **Justification:** Defines `audit_logs` table and string-based `target_type`. This is explicitly superseded by the "Locked" `UNIFIED_LOGGING_DESIGN.md` which enforces `authoritative_audit_log` and strict domains.
- **`docs/telemetry-logging.md`**
  - **Justification:** Defines `telemetry_traces` table. Superseded by `UNIFIED_LOGGING_DESIGN.md` which mandates `diagnostics_telemetry`.

## 3. Authority Conflicts

- **Audit Storage Table:**
  - `PROJECT_CANONICAL_CONTEXT.md` (Level A0) mandates the use of the `audit_logs` table.
  - `UNIFIED_LOGGING_DESIGN.md` (Locked Subsystem) mandates `authoritative_audit_log` (read) and `authoritative_audit_outbox` (write).
  - **Conflict:** A developer following A0 will violate the Locked Subsystem spec, and vice versa.

- **Document Status:**
  - `PROJECT_CANONICAL_CONTEXT.md` header states "**Status:** Draft / Living Document".
  - `index.md` classifies it as "**Level A0** (ABSOLUTE Authority)".
  - **Conflict:** Ambiguity regarding whether the rules are frozen or subject to change.

## 4. High-Risk Documents

- **`docs/architecture/audit-model.md`**
  - **Risk:** Reads like a valid architectural spec but describes a deprecated data model that conflicts with the new security logging standard.
- **`docs/PROJECT_CANONICAL_CONTEXT.md`**
  - **Risk:** While A0, it contains specific table references (`audit_logs`) that are factually incorrect according to the newer Unified Logging spec, creating a "poison pill" in the highest authority document.

## 5. Overall Risk Assessment
**HIGH**
