# Cross-Module Exception Architecture Survey

This document provides a factual architectural survey of exception handling patterns across the repository, focusing on `MaatifyException`, module-level base exceptions, and policy binding.

## 1. Module-Level Base Exceptions

The following modules define a base exception class:

| Module | Base Exception Class | Extends | Policy Binding | File Path |
| :--- | :--- | :--- | :--- | :--- |
| **AdminKernel** | `AdminKernelException` | `MaatifyException` | `defaultPolicy()` static override | `app/Modules/AdminKernel/Domain/Exception/AdminKernelException.php` |
| **AppSettings** | `AppSettingsBusinessRuleException` | `BusinessRuleMaatifyException` | Constructor Injection (`policy: AppSettingsErrorPolicy::instance()`) | `Modules/AppSettings/Exception/AppSettingsBusinessRuleException.php` |
| **ContentDocuments** | `ContentDocumentsException` | `BusinessRuleMaatifyException` | Constructor Injection (`policy: ContentDocumentsErrorPolicy::instance()`) | `Modules/ContentDocuments/Domain/Exception/ContentDocumentsException.php` |
| **I18n** | `I18nBusinessRuleException` | `BusinessRuleMaatifyException` | Constructor Injection (`policy: I18nErrorPolicy::instance()`) | `Modules/I18n/Exception/I18nBusinessRuleException.php` |
| **LanguageCore** | `LanguageCoreBusinessRuleException` | `BusinessRuleMaatifyException` | Constructor Injection (`policy: LanguageCoreErrorPolicy::instance()`) | `Modules/LanguageCore/Exception/LanguageCoreBusinessRuleException.php` |
| **AbuseProtection** | `AbuseProtectionSecurityException` | `SecurityMaatifyException` | Constructor Injection (`policy: AbuseProtectionErrorPolicy::instance()`) | `Modules/AbuseProtection/Exception/AbuseProtectionSecurityException.php` |

**Note:** Modules like `AppSettings`, `ContentDocuments`, etc., often have multiple base exceptions for different categories (e.g., `AppSettingsNotFoundException` extending `ResourceNotFoundMaatifyException`), all following the Constructor Injection pattern.

## 2. ErrorPolicy Patterns

All identified ErrorPolicy implementations follow the Singleton pattern and implement `ErrorPolicyInterface`.

| Module | Policy Class | Binding Mechanism | Escalation Override | Severity Override | File Reference |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **AdminKernel** | `AdminKernelErrorPolicy` | `defaultPolicy()` in Base Exception | No | Delegates to Default | `app/Modules/AdminKernel/Domain/Policy/AdminKernelErrorPolicy.php` |
| **AppSettings** | `AppSettingsErrorPolicy` | Constructor Injection | No | Delegates to Default | `Modules/AppSettings/Domain/Policy/AppSettingsErrorPolicy.php` |
| **ContentDocuments** | `ContentDocumentsErrorPolicy` | Constructor Injection | No | Delegates to Default | `Modules/ContentDocuments/Domain/Policy/ContentDocumentsErrorPolicy.php` |
| **I18n** | `I18nErrorPolicy` | Constructor Injection | No | Delegates to Default | `Modules/I18n/Domain/Policy/I18nErrorPolicy.php` |
| **LanguageCore** | `LanguageCoreErrorPolicy` | Constructor Injection | No | Delegates to Default | `Modules/LanguageCore/Domain/Policy/LanguageCoreErrorPolicy.php` |
| **AbuseProtection** | `AbuseProtectionErrorPolicy` | Constructor Injection | No | Delegates to Default | `Modules/AbuseProtection/Domain/Policy/AbuseProtectionErrorPolicy.php` |

## 3. Constructor Override Analysis

Most module-level exceptions override the constructor to inject the policy and handle metadata.

| Exception Class | Reason for Override | Added Parameters | Policy Injection | Meta Preprocessing |
| :--- | :--- | :--- | :--- | :--- |
| `AppSettingsBusinessRuleException` | Inject Policy | `array $meta` | Manual (`policy: ...`) | Passed to parent |
| `ContentDocumentsException` | Inject Policy | `array $meta` | Manual (`policy: ...`) | Passed to parent |
| `I18nBusinessRuleException` | Inject Policy | None | Manual (`policy: ...`) | None |
| `LanguageCoreBusinessRuleException` | Inject Policy | None | Manual (`policy: ...`) | None |
| `AbuseProtectionSecurityException` | Inject Policy | None | Manual (`policy: ...`) | None |
| `AdminKernelException` | **Does NOT Override** | N/A | **Static Method Override** | N/A |

## 4. Escalation Policy Usage

*   **Global Default:** `DefaultEscalationPolicy` is the only implementation of `EscalationPolicyInterface` found in the codebase.
*   **Overrides:** No module overrides the escalation policy. All rely on the default injected via `MaatifyException`.

## 5. defaultErrorCode Usage

*   **Base Exceptions:** None of the scanned module-level base exceptions define `defaultErrorCode`. They are all `abstract`.
*   **Concrete Subclasses:** Concrete exception classes (e.g., `InvalidDocumentStateException`) are expected to define the error code, either via constructor argument or internal logic (though specific concrete implementations were not exhaustively detailed in this survey).

## 6. Pattern Frequency Summary

| Pattern | Modules Using It | Count |
| :--- | :--- | :--- |
| **Constructor Policy Injection** | AppSettings, ContentDocuments, I18n, LanguageCore, AbuseProtection | 5 |
| **Static defaultPolicy() Override** | AdminKernel | 1 |
| **Escalation Override** | None | 0 |
| **Severity Delegation** | All (AdminKernel, AppSettings, ContentDocuments, I18n, LanguageCore, AbuseProtection) | 6 |
