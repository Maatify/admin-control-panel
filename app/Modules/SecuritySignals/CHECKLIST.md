# Compliance Checklist

- [x] **One-Domain Rule**: Exclusively handles `security_signals`.
- [x] **Pure Library**: No application wiring, controllers, or middleware.
- [x] **Strict DTOs**: All inputs are immutable DTOs (`SecuritySignalDTO`).
- [x] **Honest Infrastructure**: Writer throws `SecuritySignalWriteException` on failure.
- [x] **Canonical Schema**: Uses exact copy of `security_signals` schema.
- [x] **No Secrets**: Metadata is JSON encoded; consumer must ensure no secrets are passed.
