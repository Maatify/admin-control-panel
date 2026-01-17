# ADR-011: Data Access Logs (Deferred Category)

**Status:** ACCEPTED (DEFERRED)  
**Date:** 2026-01  
**Project:** maatify/admin-control-panel  
**Decision Type:** Architectural  
**Supersedes:** None  
**Superseded By:** None

---

## 1. Context

The Admin Control Panel implements a strict, canonical logging architecture
with clearly separated responsibilities:

- Audit Logs
- Security Events
- Activity Logs
- Telemetry
- PSR-3 Diagnostic Logging

During implementation and review, a recurring concern emerged regarding the
ability to answer **data exposure questions**, such as:

- Who viewed a specific customer record?
- Which admin accessed sensitive data?
- Was a particular record exposed internally?
- Can we trace potential insider data leakage?

None of the existing logging categories can answer these questions **correctly**
without violating their intended semantics.

---

## 2. Problem Statement

### 2.1 Why Existing Logs Are Insufficient

| Log Type        | Why It Fails for Data Access |
|-----------------|------------------------------|
| Audit Logs      | Authoritative, state-change only; reads do not qualify |
| Security Events | Risk signals, not access history |
| Activity Logs   | Track actions, not data exposure |
| Telemetry       | Ephemeral, non-compliant, non-authoritative |
| PSR-3           | Diagnostic only, not business events |

Attempting to repurpose any of the above for data access tracking leads to:

- Semantic corruption
- False narratives
- Compliance risk
- Incomplete forensic trails

---

## 3. Decision

### 3.1 Introduce a Distinct Concept: **Data Access Logs**

We formally recognize **Data Access Logs** as a **separate architectural
concern**, distinct from all existing logging categories.

Data Access Logs are intended to track:

> **Who accessed sensitive data, when, and in what context**

They are fundamentally different from:
- Actions (Activity Logs)
- State changes (Audit Logs)
- Risk signals (Security Events)
- System behavior (Telemetry)

---

## 4. Status: Deferred (Not Implemented)

### 4.1 Explicit Deferral

This ADR **does not implement** Data Access Logs.

Instead, it:
- Defines the concept
- Locks the semantic boundary
- Forbids approximation using existing logs

No schema, service, or code exists at this time.

---

## 5. Explicit Prohibitions (Hard Rules)

Until Data Access Logs are formally implemented:

- ❌ Activity Logs MUST NOT be used to track record views
- ❌ Audit Logs MUST NOT log read-only access
- ❌ Security Events MUST NOT be used for access history
- ❌ Telemetry MUST NOT be treated as access evidence
- ❌ PSR-3 MUST NOT capture user access behavior

Examples of **forbidden patterns**:
- “customer_viewed” as Activity Log
- “record_opened” as Activity Log
- “export_generated” as Activity Log
- Inferring access from list/query endpoints

Any such usage is a **hard architectural violation**.

---

## 6. Why a Separate Category Is Required

Data Access Logs have unique characteristics:

- Extremely high cardinality
- Read-heavy (far more frequent than writes)
- Strong privacy implications
- Legal and compliance sensitivity
- Different retention and anonymization rules
- Different query and indexing patterns

Mixing them with other logs:
- Pollutes timelines
- Breaks intent
- Creates false causality
- Increases breach impact

---

## 7. Future Implementation Requirements (Non-Binding)

When Data Access Logs are implemented in the future, they MUST:

- Have a dedicated ADR (extension of this one)
- Use a dedicated table or storage
- Support explicit consent and privacy review
- Define retention and redaction policies
- Avoid transactional coupling with business logic
- Be explicitly opt-in per endpoint or use-case

---

## 8. Impact on Current Codebase

### 8.1 Immediate Impact

- No code changes required
- No migrations required
- No refactoring required

### 8.2 Behavioral Impact

Developers MUST:
- Avoid logging data access entirely
- Avoid “helpful” approximations
- Rely on existing logs only for their defined purposes

---

## 9. Consequences

### Positive
- Clear semantic boundaries
- Reduced compliance risk
- Cleaner audit trails
- Future-proof architecture

### Negative
- No immediate visibility into data access
- Requires future design and implementation effort

This trade-off is **intentional and accepted**.

---

## 10. Decision Summary

> Data access tracking is **important**, but **dangerous if done incorrectly**.

We choose correctness over premature visibility.

Data Access Logs are:
- Architecturally recognized
- Explicitly deferred
- Strictly forbidden to emulate

Until properly designed:
> **They do not exist.**

---

## 11. Final Rule

> **Absence is safer than semantic corruption.**

This ADR is **canonical** and **binding**.
