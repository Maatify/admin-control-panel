# Testing Strategy

## Unit Tests

*   **Target:** `BehaviorTraceRecorder`, `BehaviorTraceDefaultPolicy`.
*   **Goal:** Verify normalization, truncation, and exception swallowing.
*   **Mocks:** Mock `BehaviorTraceLoggerInterface` and `ClockInterface`.

## Integration Tests

*   **Target:** `BehaviorTraceLoggerMysqlRepository`.
*   **Goal:** Verify MySQL writes and reads.
*   **Setup:** Use a real test database (SQLite or MySQL).

## Static Analysis

*   **Tool:** PHPStan (Level Max).
*   **Goal:** Zero errors.
