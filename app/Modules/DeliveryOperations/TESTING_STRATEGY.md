# Testing Strategy

## Unit Tests
*   **Recorder:** Verify it calls logger, generates UUID, sets time, and catches exceptions (swallows).
*   **DTO:** Verify immutability.
*   **Enums:** Verify values.

## Integration Tests
*   **Infrastructure:** Verify `PdoDeliveryOperationsWriter` inserts correctly into DB.
*   **Infrastructure:** Verify it throws exception on SQL error.

## Static Analysis
*   Must pass `phpstan analyse --level=max`.
