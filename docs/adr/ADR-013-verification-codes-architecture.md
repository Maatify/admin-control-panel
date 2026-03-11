# ADR 013: Verification Codes Architectural Stabilization

## Context
As part of the preparation for module extraction, the system's `verification_codes` persistence logic must be verified to ensure it strictly respects domain boundaries.

## Decision
An architectural scan confirmed that **no direct SQL interaction** exists against `verification_codes` outside the designated verification infrastructure layer.

The core dependencies remain appropriately separated:
- Data persistence is solely handled by `PdoVerificationCodeRepository`.
- Code generation utilizes the repository exclusively through `VerificationCodeGenerator`.
- Code validation and lifecycle management utilizes the repository exclusively through `VerificationCodeValidator`.

Controllers, domain services outside the Verification layer, and application flow handlers remain entirely decoupled from raw SQL persistence.

## Consequences
- The verification code module is architecturally ready for extraction without any required refactoring to SQL or repository abstractions.
