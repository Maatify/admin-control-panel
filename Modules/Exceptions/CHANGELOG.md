# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - Initial Stable Release

### Added
- Core `ApiAwareExceptionInterface` contract.
- Base `MaatifyException` class with guarded taxonomy logic.
- `ErrorCategoryEnum` and `ErrorCodeEnum` with strict validation.
- Full suite of standard exception families:
  - `Authentication`
  - `Authorization`
  - `BusinessRule`
  - `Conflict`
  - `NotFound`
  - `RateLimit`
  - `System`
  - `Unsupported`
  - `Validation`
- Automatic severity escalation system in `MaatifyException`.
- `SessionExpiredMaatifyException` under Authentication family.
- `BusinessRuleMaatifyException` explicitly defaults to `BUSINESS_RULE_VIOLATION`.

### Security
- Implemented category immutability to prevent taxonomy drift.
- Enforced strict HTTP status family matching (4xx vs 5xx).
- Prevented severity downgrades when wrapping exceptions.
