# 📘 Documentation Index — Admin Control Panel

> **Status:** OFFICIAL / LOCKED  
> **Scope:** Entire Documentation Tree  
> **Audience:** Humans & AI Executors  
> **Purpose:** Single authoritative entry point for all documentation  
> **Last Updated:** 2026-01-11

---

## 🔒 START HERE — MANDATORY (NON-NEGOTIABLE)

Any human or AI working on this project **MUST** start here.

### Absolute Reading Order

1. **docs/PROJECT_CANONICAL_CONTEXT.md**  
   → Canonical Memory / Source of Truth  
   → Defines AS-IS behavior, security invariants, task playbooks

2. **docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md**  
   → Target model for Pages & APIs (Phase 14+)  
   → UI / API / Permission / DataTable rules

3. **docs/API_PHASE1.md**  
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
| **A2** | API_PHASE1.md                     | HIGH      | API Contracts & Canonical LIST/QUERY    |
| **B**  | docs/adr/                         | MEDIUM    | Architectural Decisions (WHY)           |
| **C**  | docs/architecture/                | LOW       | Analysis & Explanations                 |
| **D**  | docs/phases/                      | LOW       | Historical Phase Locks                  |
| **E**  | docs/audits/                      | LOW       | Verification & Compliance Reports       |
| **F**  | docs/tests/                       | LOW       | Test Plans & Canonical Tests            |
| **G**  | docs/security/                    | LOW       | Derived Security Documentation          |
| **H**  | docs/ui/                          | LOW       | UI & Frontend Notes (Non-authoritative) |

📌 In case of conflict:  
**PROJECT_CANONICAL_CONTEXT.md ALWAYS WINS**

---

## 🧱 Canonical Subsystem Design Documents

The following documents define **locked canonical designs** for specific
cross-cutting subsystems.

They MUST be followed whenever implementing, modifying, or reviewing code within their scope.

### Logging & Observability

- **docs/architecture/UNIFIED_LOGGING_DESIGN.md**
   - Canonical design for all logging subsystems:
     Audit, SecurityEvents, ActivityLog, Telemetry
   - Defines:
      - Ownership rules
      - Exception policies
      - Module vs Domain boundaries
      - Migration constraints
  - Violations are considered **architectural hard blockers** and MUST be rejected

📌 These documents do NOT override:
- PROJECT_CANONICAL_CONTEXT.md
- ADMIN_PANEL_CANONICAL_TEMPLATE.md
- API_PHASE1.md


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
> > API_PHASE1.md
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

### 📁 docs/phases/
- Historical phase locks
- Completion reports
- Read-only
- Used to know what is frozen

---

### 📁 docs/audits/
- Compliance & verification reports
- Confirm correctness
- Do NOT define behavior

---

### 📁 docs/tests/
- Canonical test plans
- Query / LIST contracts
- Validation reference only
- Tests do NOT define architecture

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
3. ADMIN_PANEL_CANONICAL_TEMPLATE.md
4. API_PHASE1.md
5. Relevant ADR
6. Relevant Phase Lock

---

### AI Executor (STRICT)
1. docs/index.md
2. PROJECT_CANONICAL_CONTEXT.md
3. ADMIN_PANEL_CANONICAL_TEMPLATE.md
4. API_PHASE1.md
5. Relevant ADR

---

### Reviewer / Auditor
1. PROJECT_CANONICAL_CONTEXT.md
2. Relevant ADR
3. Audit Reports
4. Phase Locks

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
