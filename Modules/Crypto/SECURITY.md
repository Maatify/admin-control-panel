# Security Policy

## Supported Versions

Currently, only the latest major version of the Crypto module receives security updates.

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Cryptographic Guarantees

This module provides the following strict cryptographic guarantees:

1.  **Authenticated Encryption (AEAD):** All reversible encryption uses AEAD algorithms (e.g., XChaCha20-Poly1305 or AES-256-GCM). Data cannot be tampered with without detection.
2.  **Domain Separation (HKDF):** Context-based encryption derives unique, independent keys for different domains (contexts). A compromised key in one domain does not compromise data in another.
3.  **Secure Password Hashing:** Passwords are hashed using state-of-the-art algorithms (Argon2id) and support an optional, highly recommended global pepper (HMAC-SHA256) to mitigate offline attacks.
4.  **Fail-Closed Design:** Any cryptographic failure (e.g., missing keys, invalid tags, malformed data, unsupported algorithms) results in an immediate exception. The module will never fall back to an insecure state or return partial/corrupted data.
5.  **Seamless Key Rotation:** The system supports multiple keys with distinct states (Active, Inactive, Retired) to allow seamless decryption of legacy data while ensuring new data is always encrypted with the current Active key.

## Safe Usage Warnings

-   **Protect Your Root Keys and Peppers:** The security of this module depends entirely on the secrecy of your injected root keys and password peppers. Do not hardcode them. Store them securely (e.g., in a secrets manager or environment variables) and inject them at runtime.
-   **Use Contexts (`cryptoProvider->context(...)`) over Direct Encryption:** Always prefer context-based encryption to ensure proper domain separation. Only use direct encryption (`cryptoProvider->direct(...)`) if you have a specific, justifiable reason to bypass HKDF.
-   **Do Not Ignore Exceptions:** Ensure your application handles exceptions thrown by the Crypto module gracefully. A thrown exception indicates a serious security condition (e.g., attempted tampering, missing key).
-   **Do Not Modify Core Algorithms:** The core algorithms and primitives are intentionally locked down. Do not attempt to bypass them or introduce custom, unverified cryptographic logic.

## Reporting a Vulnerability

If you discover a security vulnerability within this module, please report it immediately.

**Do not open a public issue.**

Please send an email to the security contact for this repository. We will acknowledge receipt of your vulnerability report and strive to send you regular updates about our progress. If you do not receive a response within 48 hours, please follow up.
