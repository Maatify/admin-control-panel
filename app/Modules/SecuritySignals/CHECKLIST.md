# Library-Readiness Checklist

- [x] **Directory Structure**: `Recorder`, `DTO`, `Contract` separated.
- [x] **Dependency Safety**: No dependence on framework helpers (request/auth).
- [x] **DTO Strictness**: All inputs/outputs are DTOs.
- [x] **Fail-Open**: Recorder catches `Throwable`.
- [x] **Policy Isolated**: Validation logic in `SecuritySignalsDefaultPolicy`.
- [x] **Documentation**: `PUBLIC_API.md` exists.
- [x] **Static Analysis**: Passes `phpstan --level=max`.
