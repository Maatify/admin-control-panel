# Canonical Architecture - Security Signals

**Status:** Canonical / Locked
**Source of Truth:** `docs/architecture/logging/unified-logging-system.en.md`

## Domain Definition
*   **Name:** Security Signals
*   **Intent:** Best-effort security indicators.
*   **Control Flow:** MUST NOT impact control flow (Fail-Open).
*   **Secrets:** MUST NOT store passwords, tokens, or secrets.

## Layering
1.  **Contract:** `SecuritySignalLoggerInterface`
2.  **DTO:** `SecuritySignalDTO` (Immutable)
3.  **Infrastructure:** `PdoSecuritySignalWriter` (PDO)
4.  **Database:** `security_signals` (Canonical Schema)

## Rules
*   **One-Domain Rule:** Only `security_signals` are handled here.
*   **Fail-Open:** Swallowing of exceptions must happen at the Recorder boundary (consumer of this library).
*   **Infrastructure Honesty:** `PdoSecuritySignalWriter` throws exceptions on failure; it does not swallow.
