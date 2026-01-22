# Diagnostics Telemetry: Testing Strategy

This module is designed to be tested in isolation.

## 1. Unit Testing (Logic & Policy)

**Target:** `Recorder`, `Policy`, `DTOs`.

- **Policy Logic:**
  - Test `normalizeActorType` with valid, invalid, and empty strings.
  - Verify regex sanitization (`A-Z0-9_.:-`).
  - Verify fallback to `ANONYMOUS` for empty strings.
  - Test `normalizeSeverity` truncation (max 16 chars).
  - Test `validateMetadataSize` (64KB limit).

- **Recorder Logic:**
  - Mock `DiagnosticsTelemetryLoggerInterface` and `ClockInterface`.
  - Call `record(...)` with various inputs.
  - Verify `write(...)` is called on the writer with the correct `DiagnosticsTelemetryEventDTO`.
  - Verify write-side truncation (pass long strings and check the DTO passed to the writer).
  - Verify `duration_ms` normalization (< 0 becomes 0).
  - **Exception Handling:** Throw `DiagnosticsTelemetryStorageException` from the mock writer and verify `record()` does NOT throw (best-effort behavior).

## 2. Integration Testing (Persistence)

**Target:** `Infrastructure\Mysql\**Repository`.

- **Setup:**
  - Use `Database/schema.diagnostics_telemetry.sql` to create the table in a test DB (MySQL/SQLite).

- **Write Test:**
  - Instantiate `DiagnosticsTelemetryLoggerMysqlRepository` with a real PDO.
  - Write an event.
  - Assert row exists in DB with expected columns (especially `occurred_at` in UTC).

- **Read Test:**
  - Insert sample rows.
  - Instantiate `DiagnosticsTelemetryQueryMysqlRepository`.
  - Call `read(...)`.
  - Verify DTOs are hydrated correctly.
  - Verify Cursor Paging:
    - Read limit 1.
    - Take last event's time/id as cursor.
    - Read next page.
    - Verify strict ordering.

## 3. Extensibility Testing

- **Custom Policy:**
  - Create a class implementing `DiagnosticsTelemetryPolicyInterface`.
  - Inject into Recorder/Repository.
  - Verify custom rules applied.

- **Custom Enums:**
  - Pass a custom class implementing `DiagnosticsTelemetryActorTypeInterface` to `record()`.
  - Verify it flows through to the writer.

## 4. Static Analysis

- **Tool:** PHPStan.
- **Level:** Max.
- **Goal:** 0 errors.
- **Focus:** Strict types, nullable checks, array offset safety in Repositories.
