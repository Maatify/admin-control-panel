# 📚 Architecture Decision Records (ADR)

This directory contains the **authoritative set of Architecture Decision Records (ADRs)**
for the **Admin Control Panel** project.

Each ADR documents a **significant, irreversible architectural decision**,
including its context, rationale, consequences, and explicit guardrails.

All ADRs in this directory are considered **binding** unless explicitly superseded
by a newer ADR.

---

## 📌 ADR Index

| ADR ID  | Title                                           | Status               | Scope                  |
|---------|-------------------------------------------------|----------------------|------------------------|
| ADR-001 | Reversible Cryptography Architecture            | ACCEPTED / LOCKED    | Crypto Core            |
| ADR-002 | Key Rotation Architecture                       | ACCEPTED / LOCKED    | Crypto Policy          |
| ADR-003 | HKDF Context-Based Key Derivation               | ACCEPTED / LOCKED    | Crypto Isolation       |
| ADR-004 | Password Hashing Architecture                   | ACCEPTED / LOCKED    | Authentication         |
| ADR-005 | Crypto DX Layer & Unification                   | ACCEPTED             | Developer Experience   |
| ADR-006 | Canonical Input Normalization Boundary          | ACCEPTED / LOCKED    | Input Pipeline         |
| ADR-007 | Notification Module Scope & History Coupling    | ACCEPTED             | Notification System    |
| ADR-008 | Email Delivery as Independent Channel Queue     | ACCEPTED / LOCKED    | Messaging / Delivery   |
| ADR-009 | Telegram Delivery as Independent Channel Queue  | ACCEPTED / LOCKED    | Messaging / Delivery   |
| ADR-010 | Crypto Key Rotation Wiring & Bootstrap          | ACCEPTED / LOCKED    | Crypto Integration     |
| ADR-011 | Data Access Logs (Deferred Category)            | ACCEPTED (DEFERRED)  | Logging / Compliance   |

---

## 🧭 How to Use ADRs

### For Developers
- **Read relevant ADRs before modifying core modules**
- Treat ADRs as **hard constraints**, not suggestions
- If a change conflicts with an ADR, **stop and escalate**

### For Reviewers
- Any architectural change MUST reference an ADR
- Changes without ADR coverage are considered **architecture drift**

### For New Decisions
- New architectural decisions REQUIRE:
  1. A new ADR file
  2. Clear status (`PROPOSED`, `ACCEPTED`, `LOCKED`)
  3. Explicit consequences and guardrails

---

## 🔒 Status Semantics

- **PROPOSED**  
  Decision under discussion, not yet binding

- **ACCEPTED**  
  Decision is approved and must be followed

- **LOCKED**  
  Decision is frozen; changes require a new ADR

- **ACCEPTED (DEFERRED)**  
  Decision is architecturally recognized and binding,
  but **explicitly not implemented yet**.
  Any attempt to approximate or emulate it is forbidden.

---

## 🧱 Module-Local ADRs

Some ADRs are duplicated under module directories for developer proximity:

```

app/Modules/<ModuleName>/docs/

```

These are **mirrors of the canonical ADRs** in this directory and MUST NOT diverge.

---

## 🚫 What ADRs Are NOT

ADRs are NOT:
- Implementation guides
- Coding standards
- API documentation
- Feature specifications

They exist solely to document **why the system is shaped the way it is**.

---

## 🏁 Final Note

If you are unsure whether a change is allowed:

> **Check the ADRs first.**

If the answer is not clear:

> **A new ADR is required.**
