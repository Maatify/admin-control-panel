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
- **ADRs with status `ACCEPTED (DEFERRED)` represent a binding future decision and MUST NOT be implemented, approximated, or emulated until explicitly activated by a new ADR.**

AI MUST NOT:
- Implement logic from ADR alone
- Change behavior based on ADR
- Treat ADR as executable spec

---

## 📬 Verification Notification Dispatcher (AI Awareness)

The system uses a **centralized verification notification dispatcher**:

**Component:**  
`App\Application\Verification\VerificationNotificationDispatcher`

**Authority:**  
ADR-014 — Verification Notification Dispatcher (**ACCEPTED**)

### Purpose (WHY – NOT HOW)

This dispatcher exists to:

- Enforce a **single, canonical notification path** for verification codes
- Decouple:
   - Verification code generation
   - Delivery channel selection (Email / Telegram / future)
- Guarantee that:
   - Controllers NEVER write directly to delivery queues
   - Verification logic NEVER depends on delivery mechanics
- Preserve future extensibility without behavioral drift

### AI Execution Rules (STRICT)

AI executors MUST:

- Treat `VerificationNotificationDispatcher` as the **only allowed entry point**
  for sending verification notifications.
- Assume all verification notifications are:
   - **Asynchronous**
   - **Queue-based**
   - **Best-effort**
- Pass:
   - `identityType`
   - `identityId` (string, DB-compatible)
   - `VerificationPurposeEnum`
   - `recipient`
   - `plainCode`
   - free-form `context`
   - `language`

AI executors MUST NOT:

- Send emails directly
- Write to `email_queue` or `telegram_queue` directly
- Branch delivery logic inside controllers
- Infer delivery behavior from queue schema
- Assume synchronous delivery
- Treat dispatcher behavior as authoritative business logic

### Boundary Clarification

- The dispatcher:
   - **Coordinates delivery only**
   - **Does NOT validate OTPs**
   - **Does NOT change system state**
   - **Does NOT log security or audit events**
- All failures inside the dispatcher are:
   - Non-blocking
   - Logged via PSR-3 **diagnostic logging ONLY**

### Canonical Constraint

> Any verification-related notification mechanism introduced in the future
> (SMS, Push, Webhook, etc.)
> MUST be integrated **through this dispatcher**.

Implementing parallel notification paths is a **hard architectural violation**.

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
5. Relevant ADR (**pay special attention to `ACCEPTED (DEFERRED)` status**)

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
