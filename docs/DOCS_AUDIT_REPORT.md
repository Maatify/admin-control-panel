# /docs Directory Audit Report

## 1) File Map

```
docs/
├── ADMIN_PANEL_CANONICAL_TEMPLATE.md
├── API_PHASE1.md
├── AUDIT_COMMIT_HISTORY.md
├── KERNEL_BOOTSTRAP.md
├── KERNEL_BOUNDARIES.md
├── ONBOARDING-AR.md
├── ONBOARDING.md
├── PROJECT_CANONICAL_CONTEXT.md
├── PROJECT_TASK_CHECKLIST.md
├── UI_EXTENSION.md
├── index.ai.md
├── index.md
├── telemetry-logging.md
├── API/
│   └── ROLES_TOGGLE.md
├── adr/
│   ├── ADR-001-Reversible-Crypto-Design.md
│   ├── ADR-002-Key-Rotation-Architecture.md
│   ├── ADR-003-HKDF.md
│   ├── ADR-004-Password-Hashing-Architecture.md
│   ├── ADR-005-Crypto-DX-Layer.md
│   ├── ADR-006-input-normalization.md
│   ├── ADR-007-notification-scope-and-history-coupling.md
│   ├── ADR-008-Email-Delivery-Independent-Queue.md
│   ├── ADR-009-Telegram-Delivery-Independent-Queue.md
│   ├── ADR-010-Crypto-Key-Rotation-Wiring.md
│   ├── ADR-011-data-access-logs.md
│   ├── ADR-012-unified-verification-codes.md
│   ├── ADR-013-test-rbac-seeding-exception.md
│   ├── ADR-014-verification-notification-dispatcher.md
│   ├── ADR-015-ui-extensibility.md
│   ├── ADR-016-kernel-runtime-wiring.md
│   ├── ADR-017-kernel-boot-ownership.md
│   └── README.md
├── architecture/
│   ├── ARCHITECTURAL_CONFLICT_RESOLUTION_POLICY.md
│   ├── audit-model.md
│   ├── input-validation.md
│   ├── notification-delivery.md
│   ├── notification-routing.md
│   ├── phase8-observability.md
│   ├── analysis/
│   │   └── validation-schema-contract-analysis.md
│   ├── logging/
│   │   ├── ASCII_FLOW_LEGENDS.md
│   │   ├── CANONICAL_LOGGER_DESIGN_STANDARD.md
│   │   ├── GLOBAL_LOGGING_RULES.md
│   │   ├── LOGGING_ASCII_OVERVIEW.md
│   │   ├── LOGGING_LIBRARY_STRUCTURE_CANONICAL.md
│   │   ├── LOGGING_MODULE_BLUEPRINT.md
│   │   ├── LOG_DOMAINS_OVERVIEW.md
│   │   ├── LOG_STORAGE_AND_ARCHIVING.md
│   │   ├── README.md
│   │   ├── UNIFIED_LOGGING_DESIGN.md
│   │   ├── unified-logging-system.ar.md
│   │   └── unified-logging-system.en.md
│   ├── notification/
│   │   ├── channel-preference-resolution.md
│   │   └── multi-channel-resolution-rules.md
│   └── security/
│       └── PERMISSION_STRATEGY.md
├── auth/
│   ├── auth-flow.md
│   ├── failure-semantics.md
│   ├── remember-me.md
│   └── step-up-matrix.md
├── refactor/
│   └── REFACTOR_PLAN_CRYPTO_AND_DB_CENTRALIZATION.md
├── security/
│   ├── authentication-architecture.md
│   ├── phase-c2.1-auth-review.md
│   └── system-ownership.md
├── tests/
│   ├── canonical-admins-query.test-plan.md
│   ├── canonical-list-query.as-is-map.md
│   └── canonical-sessions-query.test-plan.md
└── ui/
    ├── UI_EXTENSIBILITY_PHASE2.md
    └── js/
        ├── SELECT2.md
        └── data_table/
            ├── README.md
            └── README_AR.md
```

## 2) Per-File Assessment

### Root Documents

*   **`docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md`**
    *   *Purpose*: Defines the standard for Pages, APIs, and Permissions.
    *   *Assessment*: **EDIT**
    *   *Justification*: Core template, but references `API_PHASE1.md` (which should be renamed).
    *   *Action*: Update reference to `API.md`.

*   **`docs/API_PHASE1.md`**
    *   *Purpose*: Authoritative API contract and Canonical List/Query definition.
    *   *Assessment*: **EDIT**
    *   *Justification*: "Phase 1" in title implies temporal scope, but content is the "Timeless" API reference.
    *   *Action*: Rename to `API.md`. Remove "Phase" references.

*   **`docs/AUDIT_COMMIT_HISTORY.md`**
    *   *Purpose*: Git log dump.
    *   *Assessment*: **REMOVE**
    *   *Justification*: Audit/temporal file, explicitly excluded by scope rules.
    *   *Action*: Delete.

*   **`docs/KERNEL_BOOTSTRAP.md`**
    *   *Purpose*: Explains kernel booting.
    *   *Assessment*: **EDIT**
    *   *Justification*: Architectural document, but contains outdated namespaces (`App\` vs `Maatify\AdminKernel\`).
    *   *Action*: Update namespaces and paths.

*   **`docs/KERNEL_BOUNDARIES.md`**
    *   *Purpose*: Defines Core/Extensible/Internal boundaries.
    *   *Assessment*: **EDIT**
    *   *Justification*: Architectural document, but contains outdated namespaces.
    *   *Action*: Update namespaces and paths.

*   **`docs/ONBOARDING.md`**
    *   *Purpose*: Developer onboarding guide.
    *   *Assessment*: **KEEP**
    *   *Justification*: Essential, reality-aligned documentation.
    *   *Action*: None.

*   **`docs/ONBOARDING-AR.md`**
    *   *Purpose*: Arabic translation of Onboarding guide.
    *   *Assessment*: **KEEP**
    *   *Justification*: Accessibility/Localization.
    *   *Action*: None.

*   **`docs/PROJECT_CANONICAL_CONTEXT.md`**
    *   *Purpose*: The "Master Document" / Source of Truth.
    *   *Assessment*: **EDIT**
    *   *Justification*: Contains outdated namespaces (`App\Domain` instead of `Maatify\AdminKernel\Domain`), references "Phase 1-13" locks (should be generic Architecture locks), and needs alignment with Modular architecture.
    *   *Action*: Update namespaces, remove temporal phase references, align with library-first structure.

*   **`docs/PROJECT_TASK_CHECKLIST.md`**
    *   *Purpose*: Operational checklist for tasks.
    *   *Assessment*: **REMOVE**
    *   *Justification*: Better suited for a `CONTRIBUTING.md` or `PULL_REQUEST_TEMPLATE.md`. "Task checklist" is process-oriented, not timeless system documentation.
    *   *Action*: Merge content into new `docs/CONTRIBUTING.md`, then delete.

*   **`docs/UI_EXTENSION.md`**
    *   *Purpose*: Guide for extending UI via DI.
    *   *Assessment*: **EDIT**
    *   *Justification*: Valid content, outdated namespaces.
    *   *Action*: Update namespaces.

*   **`docs/index.ai.md`**
    *   *Purpose*: Specific instructions for AI agents.
    *   *Assessment*: **REMOVE**
    *   *Justification*: Redundant with `index.md` (which claims to target Humans & AI). Fragmentation of entry points risks drift.
    *   *Action*: Merge critical AI constraints into `docs/index.md` or root `AGENTS.md` (if allowed), then delete.

*   **`docs/index.md`**
    *   *Purpose*: Single authoritative entry point.
    *   *Assessment*: **EDIT**
    *   *Justification*: References deleted directories (`docs/phases`, `docs/audits`).
    *   *Action*: Update to reflect the new cleaned structure.

*   **`docs/telemetry-logging.md`**
    *   *Purpose*: Define telemetry architecture.
    *   *Assessment*: **KEEP**
    *   *Justification*: Timeless architectural definition.
    *   *Action*: None.

### Subdirectories

*   **`docs/API/ROLES_TOGGLE.md`**
    *   *Purpose*: Roles API documentation.
    *   *Assessment*: **EDIT**
    *   *Justification*: Orphaned file in `API/` subdir.
    *   *Action*: Move to `docs/api/ROLES.md` (lowercase dir) or merge into `docs/API.md`.

*   **`docs/adr/`** (All files)
    *   *Purpose*: Architecture Decision Records.
    *   *Assessment*: **KEEP**
    *   *Justification*: ADRs are historical but timeless in their reasoning. Essential context.
    *   *Action*: None.

*   **`docs/architecture/phase8-observability.md`**
    *   *Purpose*: Observability architecture.
    *   *Assessment*: **EDIT**
    *   *Justification*: "Phase 8" in title/content is temporal. Content distinguishes Audit vs Activity logs (valuable).
    *   *Action*: Rename to `observability-ux.md`. Remove phase references.

*   **`docs/architecture/...`** (Others)
    *   *Purpose*: Various architectural docs.
    *   *Assessment*: **KEEP**
    *   *Justification*: Most seem valid. `logging/` has many files, could be consolidated but acceptable.
    *   *Action*: None.

*   **`docs/auth/`** (All files)
    *   *Purpose*: Authentication mechanics.
    *   *Assessment*: **KEEP**
    *   *Justification*: Explains core flows (Step-up, Failure, etc.).
    *   *Action*: None.

*   **`docs/refactor/REFACTOR_PLAN_...md`**
    *   *Purpose*: A plan for a refactor.
    *   *Assessment*: **REMOVE**
    *   *Justification*: Execution plan, temporal. Not documentation.
    *   *Action*: Delete.

*   **`docs/security/phase-c2.1-auth-review.md`**
    *   *Purpose*: Security review of a specific phase.
    *   *Assessment*: **REMOVE**
    *   *Justification*: Audit/Review artifact. Explicitly excluded.
    *   *Action*: Delete.

*   **`docs/security/authentication-architecture.md`** & **`system-ownership.md`**
    *   *Purpose*: Security concepts.
    *   *Assessment*: **KEEP**
    *   *Justification*: Timeless security definitions.
    *   *Action*: None.

*   **`docs/tests/...`** (All files)
    *   *Purpose*: Test plans and maps.
    *   *Assessment*: **REMOVE**
    *   *Justification*: "Validation reference only". Test plans drift from code. Execution details should live with tests.
    *   *Action*: Delete.

*   **`docs/ui/UI_EXTENSIBILITY_PHASE2.md`**
    *   *Purpose*: UI extensibility design (Phase 2).
    *   *Assessment*: **REMOVE**
    *   *Justification*: "Phase 2" temporal document. Superseded by `UI_EXTENSION.md` (likely).
    *   *Action*: Delete (ensure unique content is merged to `UI_EXTENSION.md` if needed).

*   **`docs/ui/js/...`**
    *   *Purpose*: JS documentation.
    *   *Assessment*: **KEEP**
    *   *Justification*: Technical documentation for JS components.
    *   *Action*: None.

## 3) Global Findings

### A) Unnecessary in `/docs`
*   **Temporal Artifacts**: Files with "Phase", "Plan", "Checklist", "History", or "Audit" in their name or purpose.
    *   *Examples*: `AUDIT_COMMIT_HISTORY.md`, `refactor/`, `tests/`, `security/phase-*.md`.
*   **Redundant Indexes**: `index.ai.md` duplicates `index.md` purpose.

### B) Requires Modification
*   **Namespace Updates**: Almost all architectural docs reference `App\` namespace. Needs update to `Maatify\AdminKernel\` (and `Modules` structure).
*   **De-Phasing**: `API_PHASE1.md` and `phase8-observability.md` contain valuable "forever" content trapped in "temporal" filenames.
*   **Consolidation**: `PROJECT_TASK_CHECKLIST.md` should be standard `CONTRIBUTING.md`.

### C) Missing Global Docs
*   **`docs/CONTRIBUTING.md`**: To house contribution guidelines, task checklists, and process rules (replacing `PROJECT_TASK_CHECKLIST.md`).
*   **`docs/API.md`**: The renamed survivor of `API_PHASE1.md`.

## 4) Recommended Final Shape of /docs

*   `docs/index.md` (Updated)
*   `docs/PROJECT_CANONICAL_CONTEXT.md` (Updated namespaces/structure)
*   `docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md` (Updated refs)
*   `docs/API.md` (Renamed from `API_PHASE1.md`)
*   `docs/ONBOARDING.md`
*   `docs/ONBOARDING-AR.md`
*   `docs/CONTRIBUTING.md` (New, absorbs Checklist)
*   `docs/UI_EXTENSION.md` (Updated namespaces)
*   `docs/telemetry-logging.md`
*   `docs/KERNEL_BOOTSTRAP.md` (Updated namespaces)
*   `docs/KERNEL_BOUNDARIES.md` (Updated namespaces)
*   `docs/adr/` (Content preserved)
*   `docs/architecture/`
    *   `audit-model.md`
    *   `observability-ux.md` (Renamed from `phase8...`)
    *   `input-validation.md`
    *   `notification-delivery.md`
    *   `notification-routing.md`
    *   `ARCHITECTURAL_CONFLICT_RESOLUTION_POLICY.md`
    *   `analysis/`
    *   `logging/`
    *   `notification/`
    *   `security/`
*   `docs/auth/`
*   `docs/security/`
    *   `authentication-architecture.md`
    *   `system-ownership.md`
*   `docs/ui/`
    *   `js/`
*   `docs/api/` (Optional, for `ROLES_TOGGLE.md` -> `ROLES.md`, or merge to `API.md`)

**Removed:**
*   `docs/phases/` (if exists, verify removal)
*   `docs/audits/` (if exists, verify removal)
*   `docs/tests/`
*   `docs/refactor/`
*   `docs/AUDIT_COMMIT_HISTORY.md`
*   `docs/PROJECT_TASK_CHECKLIST.md`
*   `docs/index.ai.md`
*   `docs/security/phase-c2.1...`
*   `docs/ui/UI_EXTENSIBILITY_PHASE2.md`
