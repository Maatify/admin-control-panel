# Changelog

All notable changes to the `Maatify\Verification` module will be documented in this file.

The format is based on **Keep a Changelog**
and this project follows **Semantic Versioning (SemVer)**.

---

## [0.1.0] - Initial Module Extraction

### Added
- **Standalone Verification Module:** Introduced the `Maatify\Verification` module as an independent component for managing OTPs and temporary verification codes.
- **Domain Contracts:** Added core interfaces including:
  - `VerificationCodeGeneratorInterface`
  - `VerificationCodeValidatorInterface`
  - `VerificationCodeRepositoryInterface`
  - `VerificationCodePolicyResolverInterface`
- **Domain DTOs:**
  - `VerificationCode`
  - `GeneratedVerificationCode`
  - `VerificationResult`
  - `VerificationPolicy`
- **Strongly Typed Enums:**
  - `IdentityTypeEnum`
  - `VerificationPurposeEnum`
  - `VerificationCodeStatus`
- **Domain Services:**
  - `VerificationCodeGenerator`
  - `VerificationCodeValidator`
  - `VerificationCodePolicyResolver`
- **Default Persistence Layer:** Added `PdoVerificationCodeRepository` as the default infrastructure implementation.
- **Container Integration:** Introduced `VerificationBindings` for easy Dependency Injection container integration.
- **Security Auditing:** Added IP tracking fields:
  - `createdIp`
  - `usedIp`
  to record where verification codes are generated and redeemed.
- **Comprehensive Documentation:**
  - `README.md`
  - `HOW_TO_USE.md`
  - `CHANGELOG.md`
  - Full architecture book under `docs/book/`.

### Changed
- **Extraction from AdminKernel:** The verification subsystem was extracted from `AdminKernel` and reorganized into `Modules/Verification`.
- **Namespace Migration:** All classes migrated to the `Maatify\Verification` namespace.
- **Lifecycle Enforcement:** Hardened lifecycle rules for verification codes:
  - Only one active code per identity + purpose.
  - Automatic expiration of previous codes on regeneration.
  - Strict attempt tracking and expiration on brute-force attempts.
- **Framework Decoupling:** The module now operates independently of AdminKernel, relying only on generic PHP constructs and `maatify/shared-common`.

### Removed
- **AdminKernel Coupling:** Removed all dependencies on internal AdminKernel structures to enable standalone usage.

---

## Future Plans

Planned improvements for upcoming releases include:

- Redis repository implementation
- Pluggable identity providers
- Verification rate limiting
- Framework adapters
- Additional verification strategies (e.g. Magic Links, TOTP)
