# BehaviorTrace: Testing Strategy

This document defines how the module must be tested to ensure compliance with the Canonical Logging Standard.

## 1. Unit Tests (Recorder & Policy)

**Scope:** `Maatify\BehaviorTrace\Recorder`
**Mocking:** Mock `BehaviorTraceWriterInterface`, `ClockInterface`, `LoggerInterface`.

### Test Cases:
1.  **Happy Path**: `record()` creates correct DTO and calls `writer->write()`.
2.  **Fail-Open**: `writer->write()` throws exception -> `record()` catches it and logs to fallback logger (does not throw).
3.  **Sanitization**: Input strings longer than DB limits are truncated (UTF-8 safe).
4.  **Policy Normalization**:
    *   Invalid ActorType -> Normalized/Sanitized.
    *   Valid ActorType -> Preserved.
5.  **Metadata Limits**:
    *   Metadata > 64KB -> Dropped/Replaced with error message.
    *   Invalid JSON -> Handled gracefully.

## 2. Integration Tests (Infrastructure)

**Scope:** `Maatify\BehaviorTrace\Infrastructure\Mysql`
**Environment:** Real MySQL Database (or SQLite in-memory if compatible).

### Test Cases:
1.  **Persistence**: `write()` inserts a row into `operational_activity`.
2.  **Round-Trip**: `write()` then `read()` returns the same data (DTO equality).
3.  **Serialization**: Metadata array is correctly JSON encoded on write and decoded on read.
4.  **Constraints**: Attempting to write NULL to non-nullable fields throws `BehaviorTraceStorageException`.

## 3. Contract Tests (Read-Side)

**Scope:** `BehaviorTraceQueryInterface`

### Test Cases:
1.  **Cursor Pagination**:
    *   Write 5 rows.
    *   Read limit 2 -> Returns first 2.
    *   Read limit 2, cursor(row 2) -> Returns next 2.
2.  **Resilience**:
    *   Manually insert invalid enum value (e.g., 'UNKNOWN_ACTOR') into DB.
    *   `read()` should not crash; should return sanitized DTO.

## 4. Architecture Tests (Static Analysis)

**Tool:** PHPStan / Psalm

### Checks:
1.  No dependency on `Illuminate\*` or `App\*`.
2.  Strict types enabled.
3.  All classes final or readonly where appropriate.
