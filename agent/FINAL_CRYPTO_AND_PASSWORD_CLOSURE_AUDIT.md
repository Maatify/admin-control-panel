# FINAL CRYPTO AND PASSWORD CLOSURE AUDIT

## 1. Executive Summary
**ARCHITECTURALLY CLOSED: NO**

The audit confirms that CRITICAL violations remain in the codebase. The legacy `EMAIL_BLIND_INDEX_KEY` is still being injected into controllers, and `hash_hmac` is still being used directly within controllers for blind index derivation. The system is NOT ready for architectural closure.

## 2. Checklist Status

| ID | Check Description | Status |
|----|-------------------|--------|
| A | Controllers must contain ZERO `hash_hmac` | **NO** |
| B | Controllers must contain ZERO `openssl_*` | **YES** |
| C | Controllers must contain ZERO direct crypto key usage/injection | **NO** |
| D | Container must NOT inject `EMAIL_BLIND_INDEX_KEY` into controllers | **NO** |
| E | Blind index derivation must exist ONLY in `AdminIdentifierCryptoService` | **NO** |
| F | No remaining references to `EMAIL_ENCRYPTION_KEY` (runtime) or `email_encrypted` (code) | **YES** |
| G | ENV fail-closed rules enforced | **PARTIAL/NO** |

## 3. Findings

### CRITICAL: Direct Crypto Usage in Controllers
The following controllers directly use `hash_hmac` for blind index derivation, violating rule #1 and #3.

*   `app/Http/Controllers/AuthController.php`
    *   Contains: `$blindIndex = hash_hmac('sha256', $dto->email, $this->blindIndexKey);`
*   `app/Http/Controllers/Web/EmailVerificationController.php`
    *   Contains: `$blindIndex = hash_hmac('sha256', $email, $this->blindIndexKey);`
*   `app/Http/Controllers/Web/LoginController.php`
    *   Contains: `$blindIndex = hash_hmac('sha256', $dto->email, $this->blindIndexKey);`

### CRITICAL: Injection of Crypto Key Material
The Dependency Injection Container injects the raw `EMAIL_BLIND_INDEX_KEY` into controllers, violating rule #2.

*   `app/Bootstrap/Container.php`
    *   In `AuthController` definition: `$_ENV['EMAIL_BLIND_INDEX_KEY']` is injected.
    *   In `LoginController` definition: `$_ENV['EMAIL_BLIND_INDEX_KEY']` is injected.
    *   In `EmailVerificationController` definition: `$_ENV['EMAIL_BLIND_INDEX_KEY']` is injected.

### ENV Configuration
*   `app/Bootstrap/Container.php`: `EMAIL_BLIND_INDEX_KEY` is still required in `dotenv` validation, implying legacy dependency persists.
*   `app/Bootstrap/Container.php`: `ADMIN_IDENTIFIER_PEPPER` is used in `AdminIdentifierCryptoServiceInterface` definition but is NOT enforced in `dotenv->required()`.

## 4. Final Hard Statement (Blockers)

The following BLOCKERS must be resolved before this topic can be closed:

1.  `app/Http/Controllers/AuthController.php` contains `hash_hmac` usage and accepts injected key material.
2.  `app/Http/Controllers/Web/LoginController.php` contains `hash_hmac` usage and accepts injected key material.
3.  `app/Http/Controllers/Web/EmailVerificationController.php` contains `hash_hmac` usage and accepts injected key material.
4.  `app/Bootstrap/Container.php` injects `EMAIL_BLIND_INDEX_KEY` into the above controllers.
5.  `app/Bootstrap/Container.php` fails to enforce `ADMIN_IDENTIFIER_PEPPER` presence via `dotenv->required()`.
