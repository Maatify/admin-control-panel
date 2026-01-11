# 🤖 AI-ONLY Documentation Index — Admin Control Panel

> **Audience:** AI Executors only (ChatGPT / Codex / Claude / Jules)  
> **Mode:** STRICT / NON-INTERACTIVE  
> **Status:** LOCKED  
> **Purpose:** Safe, deterministic execution without ambiguity

---

## 🔒 ABSOLUTE START (NON-NEGOTIABLE)

Any AI executor **MUST** read the following files  
**IN THIS EXACT ORDER** before doing ANY work.

### Mandatory Reading Order

1. **docs/PROJECT_CANONICAL_CONTEXT.md**  
   → Canonical Memory / Source of Truth  
   → Defines AS-IS behavior, security invariants, task playbooks

2. **docs/ADMIN_PANEL_CANONICAL_TEMPLATE.md**  
   → Target model for Pages & APIs (Phase 14+)  
   → UI / API / Permission / LIST rules

3. **docs/API_PHASE1.md**  
   → Authoritative API contract  
   → Any endpoint not documented here is **NON-EXISTENT**

❌ Skipping any file above = INVALID EXECUTION  
❌ Guessing or inferring behavior = FORBIDDEN

---

## 🧭 Authority Model (AI-Relevant Only)

Higher levels ALWAYS override lower levels.

| Level  | File / Folder                     | Meaning for AI              |
|--------|-----------------------------------|-----------------------------|
| **A0** | PROJECT_CANONICAL_CONTEXT.md      | Absolute authority          |
| **A1** | ADMIN_PANEL_CANONICAL_TEMPLATE.md | Target rules                |
| **A2** | API_PHASE1.md                     | API truth                   |
| **B**  | docs/adr/                         | WHY decisions (no behavior) |
| **C**  | docs/architecture/                | Explanation only            |
| **D**  | docs/phases/                      | Historical locks            |
| **E**  | docs/audits/                      | Verification only           |
| **F**  | docs/tests/                       | Validation reference        |

📌 In case of conflict:  
**PROJECT_CANONICAL_CONTEXT.md ALWAYS WINS**

---

## 📜 ADR Rules (STRICT)

- ADRs explain **WHY**, never **HOW**
- ADRs never override Canonical Context
- ADRs never introduce behavior
- ADRs are immutable once accepted

AI MUST NOT:
- Implement logic from ADR alone
- Change behavior based on ADR
- Treat ADR as executable spec

---

## 🚫 Forbidden AI Behaviors

AI executors MUST NOT:

- Invent APIs not documented in `API_PHASE1.md`
- Infer permissions or routes from code
- Change security behavior implicitly
- Use implementation details as authority
- Assume TARGET = AS-IS unless explicitly stated

---

## 🚀 Minimal Execution Reading Paths

### AI Executor — READ-ONLY / REVIEW
1. PROJECT_CANONICAL_CONTEXT.md  
2. Relevant ADR  
3. Audit / Phase docs (if needed)

---

### AI Executor — IMPLEMENTATION / FIX
1. docs/index.ai.md  
2. PROJECT_CANONICAL_CONTEXT.md  
3. ADMIN_PANEL_CANONICAL_TEMPLATE.md  
4. API_PHASE1.md  
5. Relevant ADR

---

## 🚨 Enforcement Statement

If ANY of the following occur:

- Missing documentation
- Conflicting rules
- Ambiguous behavior
- Undocumented endpoint
- Unclear authority

AI MUST:
```

STOP EXECUTION
REPORT BLOCKER
ASK FOR CLARIFICATION

```

Proceeding anyway is a **Canonical Violation**.

---

## ✅ Final AI Contract

This file is the **ONLY entry point** for AI executors.

Its goals:
- Zero ambiguity
- Deterministic execution
- No architectural drift
- No security violations

---

**Status:** LOCKED  
**Modification requires:** Explicit architectural approval
