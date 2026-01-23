# Testing Strategy

## Unit Tests

**Target:**
- `BehaviorTraceRecorder`
- `BehaviorTraceDefaultPolicy`
- DTOs

**Strategy:**
- Mock `BehaviorTraceLoggerInterface` and `ClockInterface`.
- Verify `record()` calls `write()` on the logger with correct DTOs.
- Verify Policy normalizes ActorType and validates Payload size.
- Verify `record()` catches exceptions (Fail-Open).

## Integration Tests

**Target:**
- `BehaviorTraceMysqlRepository`

**Strategy:**
- Use a real database (SQLite in-memory or MySQL).
- Verify `write()` persists data correctly.
- Verify `read()` retrieves data with correct hydration and order.
- Verify Cursor pagination logic.

## Verification Script (Manual)

A script can be used to instantiate the Recorder with a real Repository and verified against a local DB.
(See `tests/` directory in the project root for examples if available).
