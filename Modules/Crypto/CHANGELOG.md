# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - Initial Release

### Added
- **Password Hashing:** Robust password hashing using Argon2id with global HMAC-SHA256 pepper support.
- **Reversible Encryption:** Symmetric encryption services ensuring authenticated encryption (AEAD) using strong defaults (e.g., Sodium XChaCha20-Poly1305).
- **HKDF Key Derivation:** Cryptographically secure key derivation for domain separation based on explicit context strings (RFC 5869).
- **Key Rotation:** Built-in support for key rotation, managing Active, Inactive, and Retired key states seamlessly.
- **Crypto Context Providers (DX):** Developer Experience (DX) facade (`CryptoProvider`) to simplify wiring and ensure safe usage of HKDF and Reversible primitives.
- **DI Container Bindings:** Standardized DI container registration via `CryptoBindings`.
