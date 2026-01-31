# Markdown Reality Conformance Audit

## 1) Executive Summary
- **Overall documentation health**: **MIXED / UNRELIABLE**
- **Total MD files scanned**: 153
- **Findings count**:
    - REALITY VIOLATION: 5 (Critical)
    - STALE DOCUMENTATION: 20+ (Phase/Refactor docs)
    - UNENFORCED CLAIM: 2
    - CONTRADICTION: 3

The core architectural documentation (`PROJECT_CANONICAL_CONTEXT.md`) contains **critical hallucinations** regarding the Audit and Security Logging systems. While the intent and architectural patterns (DDD, Layering, Middleware) are generally respected, the *specific* implementation details (Table names, Interfaces, Fail-Closed guarantees, Crypto Contexts) have diverged significantly from the documentation.

The "Phase" and "Refactor" documentation is largely stale, referencing classes that have been renamed or removed.

## 2) Per-File Findings Table

| Severity | Category | File Path | Claim | Observed Reality | Evidence | Impact | Fix Recommendation |
|:---|:---|:---|:---|:---|:---|:---|:---|
| **BLOCKER** | REALITY VIOLATION | `docs/PROJECT_CANONICAL_CONTEXT.md` | "Audit logs MUST be written... Fail-closed (any failure aborts the transaction)" | `AuditTrailRecorder` catches exceptions and logs to fallback (Fail-Open). | `app/Modules/AuditTrail/Recorder/AuditTrailRecorder.php` (Line 132) | Security guarantees are false. Auditing failures will silently proceed. | Update docs to reflect Fail-Open reality OR (preferred) fix code to enforce Fail-Closed. |
| **HIGH** | REALITY VIOLATION | `app/Modules/Email/Queue/PdoEmailQueueWriter.php` | `CryptoContext::EMAIL_RECIPIENT_V1` ('notification:email:recipient:v1') | Code uses hardcoded string `'email:recipient:v1'`. | `app/Modules/Email/Queue/PdoEmailQueueWriter.php` vs `app/Domain/Security/CryptoContext.php` | **Data Corruption Risk**. Decryption will fail if service uses Registry but Writer uses hardcoded string. | Update Code to use `CryptoContext` constant. |
| **HIGH** | REALITY VIOLATION | `docs/PROJECT_CANONICAL_CONTEXT.md` | Interface: `AuthoritativeSecurityAuditWriterInterface` | Interface does not exist. Closest is `AuditTrailLoggerInterface` in Module. | `app/Modules/AuditTrail/Contract/AuditTrailLoggerInterface.php` | Developers will fail to find the required interface. | Rename doc reference to `AuditTrailLoggerInterface`. |
| **HIGH** | REALITY VIOLATION | `docs/PROJECT_CANONICAL_CONTEXT.md` | Storage: `audit_logs` table. | Table is named `audit_trail` (and `authoritative_audit_log` exists but is different). | `database/schema.sql`, `AuditTrailLoggerMysqlRepository.php` | SQL queries based on docs will fail. | Update docs to refer to `audit_trail`. |
| **HIGH** | REALITY VIOLATION | `docs/PROJECT_CANONICAL_CONTEXT.md` | Interface: `SecurityEventLoggerInterface` | Interface does not exist. Likely `SecuritySignalsLoggerInterface`. | `app/Modules/SecuritySignals/Contract/SecuritySignalsLoggerInterface.php` | Developers will fail to find the required interface. | Rename doc reference. |
| **MEDIUM** | REALITY VIOLATION | `docs/PROJECT_CANONICAL_CONTEXT.md` | Storage: `security_events` table. | Table is named `security_signals`. | `database/schema.sql` | Incorrect schema reference. | Update docs to `security_signals`. |
| **MEDIUM** | UNENFORCED CLAIM | `docs/PROJECT_CANONICAL_CONTEXT.md` | Encrypted outputs are represented exclusively by `EncryptedPayloadDTO`. | `PdoEmailQueueWriter` manually unpacks array from `CryptoProvider`. | `app/Modules/Email/Queue/PdoEmailQueueWriter.php` | Weakens type safety guarantees. | Update docs to "Should be..." or update code to use DTO. |
| **LOW** | STALE DOCUMENTATION | `docs/refactor/*.md`, `docs/phases/*.md` | Various references to `App\Domain\Contracts\CryptoFacadeInterface`, `FakeNotificationSender`, etc. | These classes no longer exist. | `scripts/audit_docs.py` output | Confuses new developers looking at history. | Mark these files as `[ARCHIVED]` or delete. |

## 3) Contradiction Matrix

| File A Claim | File B Claim | Conflict | Alignment |
|:---|:---|:---|:---|
| `docs/PROJECT_CANONICAL_CONTEXT.md`: Audit Log table is `audit_logs`. | `database/schema.sql`: Table is `audit_trail`. | Table name mismatch. | `database/schema.sql` is truth (it is the schema). |
| `docs/PROJECT_CANONICAL_CONTEXT.md`: Audit Logs are **Fail-Closed**. | `app/Modules/AuditTrail/Recorder/AuditTrailRecorder.php`: Catches exceptions and logs to fallback. | Security guarantee vs Implementation. | Code is truth (Fail-Open behavior observed). |
| `app/Domain/Security/CryptoContext.php`: `EMAIL_RECIPIENT_V1` = `'notification:email:recipient:v1'` | `app/Modules/Email/Queue/PdoEmailQueueWriter.php`: `RECIPIENT_CONTEXT` = `'email:recipient:v1'` | **Critical Key Mismatch**. | `CryptoContext.php` is the intended authority, but Code is writing wrong value. |

## 4) Over-Canonicalization Findings

*   **"KERNEL-GRADE" Claims**: `PROJECT_CANONICAL_CONTEXT.md` uses "LOCKED", "HARD RULE", "NON-NEGOTIABLE" extensively.
    *   *Reality*: `AuditTrail` and `SecuritySignals` implementations violate these "HARD RULES" (Interface names, Fail-Closed, Table names).
    *   *Finding*: The documentation is asserting an aspirational strictness that the code has drifted away from (or never fully met).

*   **"Frozen" Phases**:
    *   *Claim*: Phase 1-13 Frozen.
    *   *Reality*: Code matches the *logic* described, but there is no mechanism enforcing "Frozen" status (e.g., file permissions, git hooks). It is a process rule, not a code rule.

## 5) Missing or Underdocumented Reality

*   **`DiagnosticsTelemetry`**: Existing in `app/Modules/DiagnosticsTelemetry` and used in `SessionQueryController`, but `PROJECT_CANONICAL_CONTEXT.md` only briefly mentions `HttpRequestTelemetryMiddleware`. The Telemetry module itself is underdocumented in the central context.
*   **`InputNormalizationMiddleware`**: Heavily emphasized in docs, but the actual logic resides in a module `app/Modules/InputNormalization`. The docs imply it's a core kernel feature, but it's modularized.

## 6) Safe Docs-Only Repair Strategy

1.  **Update `docs/PROJECT_CANONICAL_CONTEXT.md`**:
    *   Replace `audit_logs` -> `audit_trail`.
    *   Replace `security_events` -> `security_signals`.
    *   Replace `AuthoritativeSecurityAuditWriterInterface` -> `AuditTrailLoggerInterface`.
    *   Replace `SecurityEventLoggerInterface` -> `SecuritySignalsLoggerInterface`.
    *   **Downgrade** "Fail-closed" claim for Audit Logs to "Best-effort / Fail-Open" (to match reality) **OR** add a visible "⚠️ IMPLEMENTATION GAP" warning.

2.  **Archive Stale Docs**:
    *   Move `docs/refactor/*.md` and `docs/phases/*.md` to `docs/archive/` or add a header: `> ⚠️ **ARCHIVED**: This document describes a past state and may refer to missing code.`

3.  **Flag Crypto Context Mismatch**:
    *   This requires a **CODE FIX**, not just a doc fix. The documentation (`CryptoContext`) is likely "Correct" in intent, and the code (`PdoEmailQueueWriter`) is buggy.
