# BehaviorTrace Compliance Checklist

## Logging Module Blueprint Compliance

- [x] **Directory Structure**: Strict separation of `Recorder`, `DTO`, `Contract`, `Infrastructure`.
- [x] **Dependency Safety**: No dependence on framework helpers (`request()`, `auth()`, `app()`).
- [x] **DTO Strictness**: All inputs/outputs are DTOs (except Recorder entry point which accepts primitives).
- [x] **Fail-Open**: Recorder catches `Throwable` and logs to fallback.
- [x] **Policy Isolated**: Validation logic is in `BehaviorTraceDefaultPolicy`.
- [x] **Primitive Reader**: `BehaviorTraceQueryInterface` with cursor-based pagination provided.
- [x] **Documentation**: `PUBLIC_API.md` and `CANONICAL_ARCHITECTURE.md` exist.

## Domain Rules Compliance

- [x] **One-Domain Rule**: Mapped strictly to `Operational Activity`.
- [x] **Side-Effect Free**: No emails, jobs, or business logic triggered.
- [x] **Mutations Only**: No read-logging logic implemented.
- [x] **Storage**: Uses `operational_activity` table (MySQL).
- [x] **Metadata/Payload**: Validated for size (64KB).

## Library Isolation

- [x] **Namespace**: `Maatify\BehaviorTrace` (isolated from `App`).
- [x] **Composer**: Autoloading configured.
