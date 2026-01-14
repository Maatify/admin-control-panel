# FINAL CRYPTO AND PASSWORD CLOSURE AUDIT

**Date:** 2026-01-13
**Auditor:** Jules (AI Agent)
**Scope:** Full Repository Audit
**Status:** **CRITICAL VIOLATIONS FOUND**

---

## 1. Executive Summary

Is the system FULLY CLOSED? **NO**

While the core cryptographic infrastructure (Services, Key Rotation, Pepper Ring) is implemented correctly and follows the architectural guidelines, **critical violations** were found in the HTTP Controller layer. Specifically, controllers are bypassing the `AdminIdentifierCryptoService` and performing raw cryptographic operations (`hash_hmac`) using directly injected keys.

Immediate remediation is required to close these gaps before the topic can be considered architecturally closed.

---

## 2. Verified Closure Checklist

| Section | Topic | Status | Notes |
|:---:|:---|:---:|:---|
| **A** | **PASSWORD PEPPER CLOSURE** | **YES** | Core logic is correct in `PasswordService`. |
| **B** | **CRYPTO KEY RING & ROTATION** | **YES** | `KeyRotationService` and `CryptoProvider` are enforced. |
| **C** | **LEGACY EMAIL ENCRYPTION REMOVAL** | **YES** | `EMAIL_ENCRYPTION_KEY` removed, schema split columns verified. |
| **D** | **CRYPTO SERVICE AUTHORITY** | **NO** | **CRITICAL VIOLATION**: Controllers performing crypto. |
| **E** | **DTO CANONICALIZATION** | **YES** | `EncryptedPayloadDTO` is used correctly. |
| **F** | **TYPE SAFETY & FAIL-CLOSED** | **YES** | Readers handle binary/resource types safely. |
| **G** | **ENV & BOOT FAIL-CLOSED** | **YES** | Container enforces strict ENV presence. |

---

## 3. Findings

### 🔴 CRITICAL SEVERITY

#### 1. Controllers Performing Direct Cryptography (Violation of D.1 & A.6)
Controllers are using `hash_hmac` directly to derive Blind Indexes, bypassing the mandatory `AdminIdentifierCryptoService`.

*   **File:** `app/Http/Controllers/AuthController.php`
    *   **Line:** 41
    *   **Code:** `$blindIndex = hash_hmac('sha256', $dto->email, $this->blindIndexKey);`
*   **File:** `app/Http/Controllers/Web/LoginController.php`
    *   **Line:** 54
    *   **Code:** `$blindIndex = hash_hmac('sha256', $dto->email, $this->blindIndexKey);`
*   **File:** `app/Http/Controllers/Web/EmailVerificationController.php`
    *   **Line:** 159 (in `resend` method)
    *   **Code:** `$blindIndex = hash_hmac('sha256', $email, $this->blindIndexKey);`
    *   *(Note: The `index` and `verify` methods in this controller also access `$this->blindIndexKey` or are injected with it, though usage varies).*

#### 2. Direct Injection of Crypto Keys into Controllers (Violation of D.4)
The `EMAIL_BLIND_INDEX_KEY` is being injected directly into controllers via `App\Bootstrap\Container.php`, instead of being encapsulated within `AdminIdentifierCryptoService`.

*   **File:** `app/Bootstrap/Container.php`
*   **Violations:**
    *   `AuthController` definition: `$_ENV['EMAIL_BLIND_INDEX_KEY']`
    *   `LoginController` definition: `$_ENV['EMAIL_BLIND_INDEX_KEY']`
    *   `EmailVerificationController` definition: `$_ENV['EMAIL_BLIND_INDEX_KEY']`

---

### 🟠 MEDIUM SEVERITY

#### 3. Unused / Redundant Crypto Module Code
The class `App\Modules\Crypto\Password\PasswordHasher` exists and contains valid logic but is **unused** in the application. The application (via `Container.php`) wires `App\Domain\Service\PasswordService` instead.

*   **File:** `app/Modules/Crypto/Password/PasswordHasher.php`
*   **Status:** Dead code (referenced only in tests and documentation).
*   **Recommendation:** Should be deprecated or removed to avoid confusion about the "Canonical" password service.

---

## 4. Explicit Statement

These topics **CANNOT** be considered **ARCHITECTURALLY CLOSED** until the Critical Violations in the Controller layer are remediated.

Future discussion and refactoring are **REQUIRED** to:
1.  Update `AuthController`, `LoginController`, and `EmailVerificationController` to use `AdminIdentifierCryptoService`.
2.  Remove `EMAIL_BLIND_INDEX_KEY` injection from these controllers in `Container.php`.

Once these specific violations are addressed, the system will meet the strict audit requirements.
