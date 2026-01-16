# Telemetry Architecture Deep Map

> **Status:** Documentation
> **Scope:** Telemetry Subsystem (Code-Level, Flow-Level)
> **Source:** Codebase Analysis (AS-IS)

---

## 1. Executive Summary

The **Telemetry Subsystem** is a specialized, **best-effort**, **observability-focused** mechanism designed to capture internal application performance metrics (e.g., HTTP request duration) and high-level logical flows (e.g., Authentication outcomes) without storing PII or blocking the main request flow.

It is **strictly separated** from the authoritative `audit_logs` (Security/Compliance) and operational `activity_logs` (Staff Actions).

**Key Characteristics:**
*   **Best-Effort:** Failures are swallowed; they never break the user request.
*   **Privacy-First:** Sensitive identifiers (like emails) are hashed using HKDF before storage.
*   **Observational:** Data is for diagnostics and monitoring, not for business decisions or authority.
*   **Insert-Only:** Telemetry data is immutable and append-only.

---

## 2. Layer-by-Layer Breakdown

The architecture follows a strict layered approach:

### 2.1 Entry Points (Allowed Emitters)
Telemetry originates primarily from:
*   **Middleware:** `HttpRequestTelemetryMiddleware` (captures `HTTP_REQUEST_END`, duration, status code).
*   **Controllers:** `AuthController` (captures `AUTH_LOGIN_SUCCESS`, `AUTH_LOGIN_FAILURE`).
*   **Factory:** `HttpTelemetryRecorderFactory` is the standard way to instantiate recorders.

**Forbidden Emitters:**
*   **Domain Services:** Telemetry is an *Application* concern. Domain services should not depend on `TelemetryRecorder` directly (though `TelemetryAuditLogger` exists for Audit, that is distinct). The codebase shows Telemetry usage is confined to HTTP/Application layers.

### 2.2 Application Layer (Context Enrichment)
This layer bridges the HTTP world (`RequestContext`) and the Domain world (`TelemetryRecordDTO`).

*   **`HttpTelemetryRecorderFactory`**: Creates request-scoped recorders.
*   **`HttpTelemetryAdminRecorder`**:
    *   **Requires:** `RequestContext` + `Admin ID`.
    *   **Role:** Enriches DTO with `requestId`, `userAgent`, `ipAddress` from context. Sets `ActorType::ADMIN`.
*   **`HttpTelemetrySystemRecorder`**:
    *   **Requires:** `RequestContext`.
    *   **Role:** Used when no admin is authenticated (e.g., login failure, system errors). Sets `ActorType::SYSTEM`.

### 2.3 Domain Layer (Business Logic & Transformation)
*   **Interface:** `TelemetryRecorderInterface` (Contract).
*   **Implementation:** `TelemetryRecorder`.
*   **Responsibility:**
    1.  Accepts `TelemetryRecordDTO` (Domain object).
    2.  Transforms it into `TelemetryEventDTO` (Module object).
    3.  Delegates to `TelemetryLoggerInterface`.
    4.  **Error Handling:** Catches and swallows `TelemetryStorageException`. This is the core "Fail-Safe" mechanism.

### 2.4 Module Layer (Persistence & Privacy)
*   **Privacy:** `TelemetryEmailHashService` (via `TelemetryEmailHasherInterface`)
    *   Used by Controllers *before* recording.
    *   Hashes PII (email) using **HKDF** + **Key Rotation**.
    *   Ensures telemetry traces cannot be used to harvest raw emails.
*   **Persistence:** `TelemetryLoggerMysqlRepository` (via `TelemetryLoggerInterface`).
    *   **Action:** Performs the actual SQL `INSERT` into `telemetry_traces`.
    *   **Behavior:** Throws `TelemetryStorageException` on failure (which is caught upstream).

---

## 3. End-to-End Flow

1.  **Request Arrival:** HTTP Request hits `HttpRequestTelemetryMiddleware`. `RequestContext` is initialized.
2.  **Recorder Creation:** Middleware/Controller calls `HttpTelemetryRecorderFactory` to get an Admin or System recorder.
3.  **Context Injection:** Factory injects the current `RequestContext` into the recorder.
4.  **Event Recording:**
    *   Caller invokes `record($actorId, $eventType, $severity, $metadata)`.
    *   **PII Hashing (if applicable):** Caller uses `TelemetryEmailHasher` to hash emails and places the hash in `$metadata`.
5.  **DTO Construction:** `Http*Recorder` creates `TelemetryRecordDTO`, populating `requestId`, `ipAddress`, etc., from `RequestContext`.
6.  **Domain Hand-off:** `Http*Recorder` calls `TelemetryRecorder->record($dto)`.
7.  **Transformation:** `TelemetryRecorder` converts `TelemetryRecordDTO` -> `TelemetryEventDTO`.
8.  **Persistence Attempt:** `TelemetryRecorder` calls `TelemetryLoggerMysqlRepository->log($dto)`.
9.  **Database Write:** Repository executes `INSERT INTO telemetry_traces ...`.
10. **Error Handling:**
    *   If DB write fails -> Repository throws `TelemetryStorageException`.
    *   `TelemetryRecorder` catches Exception -> Returns `void`.
    *   Flow continues uninterrupted.

---

## 4. Dependency Map (Conceptual)

*   **Controllers / Middleware** depends on `HttpTelemetryRecorderFactory` AND `TelemetryEmailHasherInterface`.
*   **HttpTelemetryRecorderFactory** depends on `TelemetryRecorderInterface`.
*   **HttpTelemetry*Recorder** depends on `RequestContext` AND `TelemetryRecorderInterface`.
*   **TelemetryRecorder (Domain)** depends on `TelemetryLoggerInterface`.
*   **TelemetryLoggerMysqlRepository (Infra)** depends on `PDO`.
*   **TelemetryEmailHashService (Infra)** depends on `KeyRotationService` AND `HKDFService`.

**One-Way Constraints:**
*   **Domain** NEVER depends on **Application** (e.g., `RequestContext`).
*   **Domain** NEVER depends on **Infrastructure** concretions.
*   **Telemetry** NEVER depends on **Audit Logs** or **Activity Logs**.

---

## 5. Failure Behavior Matrix

| Layer | Component | Action on Failure | Outcome |
| :--- | :--- | :--- | :--- |
| **Infra** | `TelemetryLoggerMysqlRepository` | **Throws** `TelemetryStorageException` | Error bubbles up to Domain. |
| **Domain** | `TelemetryRecorder` | **Catches** `TelemetryStorageException` | **Swallows error**. Returns void. |
| **App** | `HttpTelemetry*Recorder` | Delegates to Domain | Success (void). |
| **Http** | `HttpRequestTelemetryMiddleware` | **Catches** `Throwable` | **Swallows all**. Ensures request completes. |
| **Http** | `AuthController` | **Catches** `Throwable` | **Swallows all**. Ensures login flows complete. |

**Verdict:** The system is strictly **Fail-Safe** (Best-Effort). Telemetry outages do NOT impact business availability.

---

## 6. Explicit Non-Responsibilities

Telemetry is **NOT**:

1.  **Audit Logs (`audit_logs`)**:
    *   Telemetry is *not* authoritative.
    *   Telemetry is *not* transactional (failures are ignored).
    *   Audit Logs MUST fail-closed; Telemetry MUST fail-open.

2.  **Activity Logs (`activity_logs`)**:
    *   Telemetry tracks *system* events (Request End, Login Failure).
    *   Activity Logs track *staff* actions (Created User, Deleted Product).

3.  **Security Events (`security_events`)**:
    *   While `AUTH_LOGIN_FAILURE` is recorded in Telemetry for *performance/diagnostics*, there is a separate `SecurityEventLoggerInterface` for security auditing. (Note: `AuthController` writes to both Activity Logs and Telemetry, and potentially Security Logs via `AdminAuthenticationService`).

4.  **PSR-3 Logs**:
    *   Telemetry is structured and DB-backed. PSR-3 logs are text-based and filesystem-backed (usually).

---

## 7. Final Consistency Verdict

The Telemetry subsystem **COMPLIES** with the Project Canonical Context.

*   **Respects Architecture:** strict separation of Http / Domain / Module / Infra.
*   **Respects Context:** `RequestContext` is injected only at the Application layer, not leaked to Domain.
*   **Respects Privacy:** PII is hashed using the Canonical Crypto pipeline (HKDF + Key Rotation).
*   **Respects Reliability:** Enforces best-effort behavior, ensuring it never causes a production outage.

**Status:** ✅ **CONSISTENT**
