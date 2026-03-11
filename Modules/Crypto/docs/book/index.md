# Crypto Module Book

Welcome to the comprehensive documentation for the Crypto module. This module provides a robust, security-first set of cryptographic primitives designed for standalone extraction and use.

## Table of Contents

1.  [Architecture & Design Principles](architecture.md)
2.  [Password Hashing](password_hashing.md)
3.  [Reversible Encryption](reversible_encryption.md)
4.  [HKDF Key Derivation](hkdf.md)
5.  [Key Rotation](key_rotation.md)
6.  [Security Model](security_model.md)

## Core Philosophy

The Crypto module is built on the following core principles:

-   **Security First:** Defaults are secure. The module explicitly forbids weak algorithms or insecure configurations.
-   **Fail-Closed:** Any error in a cryptographic operation (e.g., missing key, invalid ciphertext, tampering detection) results in an exception. There are no silent failures.
-   **Domain Separation:** Extensive use of HKDF ensures that keys used for one purpose cannot be reused for another, limiting the blast radius of a potential key compromise.
-   **Stateless Operations:** The module relies entirely on injected secrets and configuration. It does not maintain internal state or persist data.
-   **Explicit Configuration:** Everything, from context strings to key versions, must be explicitly defined. Implicit behavior is avoided.
