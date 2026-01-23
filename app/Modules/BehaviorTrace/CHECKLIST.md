# BehaviorTrace Module Checklist

This checklist certifies that the module adheres to the Canonical Logging Standard.

## 1. Directory Structure
- [x] **Contract/**: Interfaces only.
- [x] **DTO/**: Immutable Data Transfer Objects.
- [x] **Database/**: Canonical SQL Schema present.
- [x] **Infrastructure/**: Storage drivers (MySQL).
- [x] **Recorder/**: Entry point logic.
- [x] **Enum/**: Allowed values.

## 2. Dependency Safety
- [x] **No Framework Coupling**: Does not use `request()`, `auth()`, or DI container helpers.
- [x] **No Domain Leaks**: Does not reference User, Order, or other business entities.
- [x] **Explicit Dependencies**: Uses `ClockInterface`, `LoggerInterface`.

## 3. Recorder Logic
- [x] **Fail-Open**: `record()` catches `Throwable` and returns `void`.
- [x] **Sanitization**: Truncates strings to schema limits (UTF-8 safe).
- [x] **Policy Enforcement**: Delegates validation to `PolicyInterface`.
- [x] **DTO Construction**: Converts primitives to DTOs immediately.

## 4. DTO Strictness
- [x] **Immutable**: All properties are `readonly`.
- [x] **Typed**: No `mixed` types (except metadata array content).
- [x] **Canonical**: Maps 1:1 to `operational_activity` table.

## 5. Storage (Infrastructure)
- [x] **Interface Bound**: Implements `BehaviorTraceWriterInterface`.
- [x] **No Logic**: Performs only serialization and persistence.
- [x] **Exception Handling**: Wraps PDO exceptions in `BehaviorTraceStorageException`.

## 6. Read-Side
- [x] **Primitive**: Cursor-based access only.
- [x] **Stateless**: No offset/page logic.
- [x] **Resilient**: Handles corrupt/unknown enums gracefully.

## 7. Documentation
- [x] **PUBLIC_API.md**: Defines the strict boundary.
- [x] **README.md**: Usage examples.
- [x] **CANONICAL_ARCHITECTURE.md**: Design philosophy.
