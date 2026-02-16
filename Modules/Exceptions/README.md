# maatify/exceptions

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg) ![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)

**Enterprise-grade, hardened exception handling library for PHP applications.**

`maatify/exceptions` provides a strictly typed, immutable taxonomy for application errors, enforcing semantic correctness, security, and consistent HTTP mapping. It is designed to prevent "taxonomy drift" and ensure that critical system errors are never masked by lower-severity wrappers.

---

## 🚀 Key Features

*   **Strict Taxonomy:** Exceptions are categorized into 9 distinct families (System, Validation, Auth, etc.) backed by `ErrorCategoryEnum`.
*   **Guarded Overrides:** Prevents developers from accidentally mismatching error codes or HTTP statuses.
*   **Escalation Protection:** Automatically escalates severity when a critical exception is wrapped in a lighter one (e.g., a Database failure wrapped in a generic runtime exception will typically retain 500 status).
*   **Zero Dependencies:** Pure PHP implementation. No framework coupling.
*   **PSR-4 Compliant:** Ready for immediate Composer autoloading.

---

## 📦 Installation

```bash
composer require maatify/exceptions
```

---

## 📖 Usage

### Basic Usage

Throwing a predefined exception:

```php
use Maatify\Exceptions\Exception\Validation\InvalidArgumentMaatifyException;

throw new InvalidArgumentMaatifyException('The email format is invalid.');
// Result:
// Category: VALIDATION
// HTTP Status: 400
// Error Code: INVALID_ARGUMENT
```

### Advanced Usage (Wrapping)

When catching a low-level error and re-throwing, the library automatically handles severity escalation:

```php
use Maatify\Exceptions\Exception\System\DatabaseConnectionMaatifyException;
use Maatify\Exceptions\Exception\BusinessRule\BusinessRuleMaatifyException;

try {
    // Simulate a critical database failure (System / 503)
    throw new DatabaseConnectionMaatifyException('Connection timeout');
} catch (DatabaseConnectionMaatifyException $e) {
    // Attempting to wrap it in a "softer" business exception
    // Note: BusinessRuleMaatifyException is abstract, so we use an anonymous class for this example
    throw new class('Unable to process order', 0, $e) extends BusinessRuleMaatifyException {};
}

// RESULT:
// The final exception will report:
// Category: SYSTEM (Escalated from BusinessRule)
// HTTP Status: 503 (Escalated from 422)
// This ensures monitoring tools see the root cause (System Failure), not a generic Business Rule error.
```

---

## 📚 Documentation

Detailed documentation is available in the [BOOK/](BOOK/) directory:

1.  [Introduction](BOOK/01_Introduction.md)
2.  [Architecture](BOOK/02_Architecture.md)
3.  [Taxonomy](BOOK/03_Taxonomy.md)
4.  [Exception Families](BOOK/04_Exception_Families.md)
5.  [Override Rules](BOOK/05_Override_Rules.md)
6.  [Escalation Protection](BOOK/06_Escalation_Protection.md)
7.  [Security Model](BOOK/07_Security_Model.md)
8.  [Best Practices](BOOK/08_Best_Practices.md)
9.  [Extending The Library](BOOK/09_Extending_The_Library.md)
10. [API Integration Guide](BOOK/10_API_Integration_Guide.md)
11. [Testing Strategy](BOOK/11_Testing_Strategy.md)
12. [Versioning Policy](BOOK/12_Versioning_Policy.md)
13. [Packagist Metadata](BOOK/13_Packagist_Metadata.md)

---

## 🛡️ Guarantees

*   **Category Immutability:** An exception's category is defined by its class and cannot be overridden at runtime.
*   **Status Class Safety:** You cannot force a 4xx exception to return a 5xx status code manually, or vice versa.
*   **Escalation Determinism:** Severity calculation is deterministic and side-effect free.

---

## 📄 License

This library is open-sourced software licensed under the **MIT license**.
