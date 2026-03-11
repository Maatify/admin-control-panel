# Changelog

All notable changes to the `Maatify\Verification` module will be documented in this file.

## [Unreleased]

### Added
- **IP Tracking Additions:** Added `createdIp` and `usedIp` parameters to the core generation and validation interfaces (`VerificationCodeGeneratorInterface`, `VerificationCodeValidatorInterface`) and DTOs (`VerificationCode`). These changes allow auditing where verification codes are generated and redeemed, significantly improving the security tracking of the module.
- **Standalone Extraction Preparation:** Restructured the module to serve as a standalone, zero-dependency package (`maatify/verification`), enabling it to be consumed easily across different frameworks without requiring the full AdminKernel domain.
- **Module Bindings Introduction:** Introduced `VerificationBindings` in the `Bootstrap` layer, providing an immediate and standardized way to connect the module to Dependency Injection containers.

### Changed
- **Extraction from AdminKernel:** The entire Verification subsystem was extracted from the monolithic `AdminKernel` and moved into `Modules/Verification/`.
- **Namespace Migration:** Migrated all classes, contracts, DTOs, Enums, and implementations to the `Maatify\Verification` namespace.
- **Verification Lifecycle Stabilization:** Hardened the business logic rules inside `VerificationCodeValidator` and `VerificationCodeGenerator`. Generation now automatically invalidates prior active codes for the same identity and purpose, guaranteeing a single active code at any given time. Validation securely increments attempts upon failure and correctly expires the code when maximum attempts are exceeded, preventing brute-force attacks.

### Removed
- **Coupling to AdminKernel:** Removed tight coupling to internal AdminKernel structures, ensuring that the module operates entirely on generic PHP structures, DTOs, and strictly typed Enums.
