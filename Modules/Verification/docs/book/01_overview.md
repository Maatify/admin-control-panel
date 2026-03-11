# Chapter 1: Overview

The `Maatify\Verification` module is a framework-agnostic component designed for generating, validating, and managing the lifecycle of One-Time Passwords (OTPs) and temporary verification codes.

## Core Goals

- **Security by Design:** Codes are never stored in plain text. A secure hash (`sha256`) is stored instead, similar to password management. Constant-time comparison prevents timing attacks during validation.
- **Anti-Brute Force Built-In:** The module strictly manages attempts. After a defined number of failed validations, the code is permanently expired, even if it hasn't reached its TTL (Time-To-Live).
- **Single Active Code Guarantee:** Whenever a new code is generated for a specific user and purpose, all previously active codes for that same combination are instantly invalidated. This significantly shrinks the attack surface.
- **Auditing and Traceability:** The module tracks both the IP address used to generate a code (`createdIp`) and the IP address used to successfully validate it (`usedIp`).
- **Framework Agnosticism:** With zero dependencies on heavy frameworks (relying only on `maatify/shared-common`), it can be integrated into any PHP 8.2+ application via its standard PSR container bindings.

## Typical Use Cases

- **Email Verification:** Sending a 6-digit code to a user to confirm they own an email address.
- **Two-Factor Authentication (2FA) Fallbacks:** Providing a temporary OTP if an authenticator app is unavailable.
- **External Account Linking:** Confirming possession of a third-party account (e.g., linking a Telegram channel).
- **Password Resets:** Sending a secure code to authorize a password change.

## High-Level Workflow

1.  **Request:** A user requests an action requiring verification.
2.  **Generation:** The application calls the `VerificationCodeGeneratorInterface`. The generator determines the policy (TTL, max attempts), invalidates old codes, generates a random 6-digit string, hashes it, stores the hash in the repository, and returns the plain text code back to the application.
3.  **Delivery:** The application delivers the plain text code (e.g., via Email, SMS) to the user.
4.  **Submission:** The user submits the code back to the application.
5.  **Validation:** The application calls the `VerificationCodeValidatorInterface` with the submitted code. The validator hashes the input, performs a constant-time comparison against the stored hash, increments attempts on failure, and marks the code as used on success.