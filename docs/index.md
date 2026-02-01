# 📘 Documentation Index — Admin Control Panel

> **Status:** OFFICIAL / LOCKED  
> **Scope:** Entire Documentation Tree  
> **Audience:** Humans & AI Executors  
> **Purpose:** Single authoritative entry point for all documentation  
> **Last Updated:** 2026-01-31

---

## 🔒 START HERE — MANDATORY (NON-NEGOTIABLE)

Any human or AI working on this project **MUST** start here.

### Absolute Reading Order

1. **docs/PROJECT_CANONICAL_CONTEXT.md**  
   → Canonical Memory / Source of Truth  
   → Defines AS-IS behavior, security invariants, task playbooks

2. **docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md**  
   → Target model for Pages & APIs (Phase 14+)  
   → UI / API / Permission / LIST rules

3. **docs/API.md**
   → Authoritative API contract  
   → Any endpoint not documented here is considered **NON-EXISTENT**

❌ Executing any task without reading the above is INVALID  
❌ Guessing or inferring behavior is FORBIDDEN

---

## 🧭 Documentation Authority Levels

Documentation is strictly layered.  
**Higher levels ALWAYS override lower levels.**

| Level  | File / Folder                     | Authority | Description                             |
|--------|-----------------------------------|-----------|-----------------------------------------|
| **A0** | PROJECT_CANONICAL_CONTEXT.md      | ABSOLUTE  | Canonical Memory & Security Truth       |
| **A1** | ADMIN_PANEL_CANONICAL_TEMPLATE.md | HIGH      | Target Architecture (Pages & APIs)      |
| **A1** | KERNEL_BOUNDARIES.md              | HIGH      | Core Kernel Security Boundaries         |
| **A1** | docs/auth/                        | HIGH      | Authentication & Step-Up Specifications |
| **A2** | API.md                            | HIGH      | API Contracts & Canonical LIST/QUERY    |
| **B**  | docs/adr/                         | MEDIUM    | Architectural Decisions (WHY)           |
| **C**  | docs/architecture/                | LOW       | Analysis & Explanations                 |
| **G**  | docs/security/                    | LOW       | Derived Security Documentation          |
| **H**  | docs/ui/                          | LOW       | UI & Frontend Notes (Non-authoritative) |

📌 In case of conflict:  
**PROJECT_CANONICAL_CONTEXT.md ALWAYS WINS**

---

## 🤖 AI-Specific Execution Rules

> **Audience:** AI Executors only (ChatGPT / Codex / Claude / Jules)
> **Mode:** STRICT / NON-INTERACTIVE

### Authority Model for AI

- **ADRs with status `ACCEPTED (DEFERRED)` represent a binding future decision and MUST NOT be implemented, approximated, or emulated until explicitly activated by a new ADR.**
- AI MUST NOT:
    - Implement logic from ADR alone
    - Change behavior based on ADR
    - Treat ADR as executable spec

### Verification Notification Dispatcher (AI Awareness)

**Authority:** ADR-014 — Verification Notification Dispatcher (**ACCEPTED**)

AI executors MUST:
- Treat `VerificationNotificationDispatcher` as the **only allowed entry point** for sending verification notifications.
- Assume all verification notifications are:
    - **Asynchronous**
    - **Queue-based**
    - **Best-effort**
- MUST NOT send emails directly or write to queues directly.

### Forbidden AI Behaviors

AI executors MUST NOT:
- Invent APIs not documented in `API.md`
- Infer permissions or routes from code
- Change security behavior implicitly
- Use implementation details as authority

---

## 🧱 Canonical Subsystem Design Documents

The following documents define **locked canonical designs** for specific
cross-cutting subsystems.

**NOTE: These documents override the general "Low Authority" status of the `architecture/` folder.**
They MUST be followed whenever implementing, modifying, or reviewing code within their scope.

### Logging & Observability

- **docs/architecture/logging/UNIFIED_LOGGING_DESIGN.md**
   - Canonical design for all logging subsystems:
     Audit, SecurityEvents, ActivityLog, Telemetry
   - Defines:
      - Ownership rules
      - Exception policies
      - Module vs Domain boundaries
      - Migration constraints
  - Violations are considered **architectural hard blockers** and MUST be rejected

### Input Validation

- **docs/architecture/input-validation.md**
    - Canonical design for request validation and error mapping.

### Notification Delivery

- **docs/architecture/notification-delivery.md**
    - Canonical infrastructure for async email delivery and queue management.

📌 These documents do NOT override:
- PROJECT_CANONICAL_CONTEXT.md
- ADMIN_PANEL_CANONICAL_TEMPLATE.md
- API.md


---

## 📜 ADR (Architecture Decision Records) Rules

Folder: `docs/adr/`

ADRs document **WHY decisions were made**, not HOW to implement them.

### ADR Rules
- ADRs do NOT define implementation
- ADRs do NOT override Canonical Context
- ADRs do NOT introduce behavior
- ADRs are immutable once accepted

### Conflict Resolution Order
```

PROJECT_CANONICAL_CONTEXT.md

> ADMIN_PANEL_CANONICAL_TEMPLATE.md
> > API.md
> > ADR
> > Architecture Notes

```

---

## 🧠 Folder-by-Folder Purpose

### 📁 docs/architecture/
- Analysis, explanations, breakdowns
- NOT authoritative
- Used for understanding only

---

### 📁 docs/security/
- Authentication architecture
- Failure semantics
- System ownership
- MUST align with Canonical Context
- MUST NOT introduce new rules

---

### 📁 docs/ui/
- Frontend notes (JS, UI helpers)
- Non-authoritative
- Convenience documentation only

---

## 🚀 Reading Paths (Role-Based)

### Backend Developer
1. docs/index.md
2. PROJECT_CANONICAL_CONTEXT.md
3. KERNEL_BOUNDARIES.md
4. docs/auth/
5. ADMIN_PANEL_CANONICAL_TEMPLATE.md
6. API.md
7. Relevant ADR

---

### AI Executor (STRICT)
1. docs/index.md
2. PROJECT_CANONICAL_CONTEXT.md
3. KERNEL_BOUNDARIES.md
4. docs/auth/
5. ADMIN_PANEL_CANONICAL_TEMPLATE.md
6. API.md
7. Relevant ADR

---

### Reviewer / Auditor
1. PROJECT_CANONICAL_CONTEXT.md
2. Relevant ADR
3. Audit Reports

---

## 🚨 Enforcement Rules

- ❌ No undocumented API usage
- ❌ No inferred permissions or routes
- ❌ No behavior inferred from code alone
- ❌ No deviation without explicit ADR
- ❌ No execution without Canonical Context

Any violation is considered:
**Architecture Violation / Security Risk**

---

## ✅ Final Statement

This file (`docs/index.md`) is the **single navigation authority**
for the documentation tree.

It exists to:
- Eliminate ambiguity
- Protect security invariants
- Enable safe AI execution
- Enable fast and correct human onboarding

---

**Status:** LOCKED  
**Changes require:** Explicit architectural approval
