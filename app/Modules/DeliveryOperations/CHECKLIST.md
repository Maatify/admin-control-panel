# Compliance Checklist

- [x] **One-Domain Rule**: Exclusively handles `delivery_operations`.
- [x] **Pure Library**: No application wiring.
- [x] **Strict DTOs**: Input/Output is DTO based.
- [x] **Fail-Open Recorder**: Recorder catches `Throwable`.
- [x] **Honest Infrastructure**: Writer throws `DeliveryOperationsStorageException`.
- [x] **Canonical Schema**: Exact copy of `delivery_operations` schema.
- [x] **Dependency Injection**: Logger and Clock are injected.
- [x] **No Secrets**: Metadata validation expected at app layer (JSON limit).
