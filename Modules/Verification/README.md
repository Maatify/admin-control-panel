# Maatify Verification Module

## Overview

The `Maatify\Verification` module is a framework-agnostic verification code component designed for managing One-Time Passwords (OTPs) and temporary codes. It handles the complete lifecycle of verification codes, including generation, secure storage (hashing), validation, attempt tracking, expiration, and IP address auditing.

## Architecture Summary

This module follows Domain-Driven Design (DDD) principles with strict separation between the domain logic and infrastructure concerns:

- **Domain Layer:** Contains core contracts (`VerificationCodeGeneratorInterface`, `VerificationCodeValidatorInterface`), DTOs (`VerificationCode`, `GeneratedVerificationCode`), and Services (`VerificationCodeGenerator`, `VerificationCodeValidator`) implementing the business rules for verification codes.
- **Infrastructure Layer:** Implements data persistence. By default, it includes `PdoVerificationCodeRepository` for database storage.
- **Bootstrap Layer:** Provides dependency injection container bindings (`VerificationBindings`) to ease integration.

## Module Structure

```
Modules/Verification/
├── Bootstrap/                 # DI Container bindings
│   └── VerificationBindings.php
├── Domain/
│   ├── Contracts/             # Core interfaces
│   ├── DTO/                   # Data Transfer Objects
│   ├── Enum/                  # Strongly-typed Enums for state and types
│   └── Service/               # Business logic for generation and validation
├── Infrastructure/
│   └── Repository/            # Persistence implementations (e.g., PDO)
├── docs/                      # Extensive documentation
└── composer.json              # Standalone package definition
```

## Quick Usage

To quickly get started with the module, register the bindings in your DI container and use the provided services:

```php
use Maatify\Verification\Bootstrap\VerificationBindings;
use Maatify\Verification\Domain\Contracts\VerificationCodeGeneratorInterface;
use Maatify\Verification\Domain\Contracts\VerificationCodeValidatorInterface;
use Maatify\Verification\Domain\Enum\IdentityTypeEnum;
use Maatify\Verification\Domain\Enum\VerificationPurposeEnum;

// 1. Register bindings
VerificationBindings::register($containerBuilder);

// 2. Generate a verification code
/** @var VerificationCodeGeneratorInterface $generator */
$generator = $container->get(VerificationCodeGeneratorInterface::class);

$generated = $generator->generate(
    IdentityTypeEnum::Email,
    'user@example.com',
    VerificationPurposeEnum::EmailVerification,
    '192.168.1.100' // Optional IP tracking
);

// Send $generated->plainCode to the user...

// 3. Validate a verification code
/** @var VerificationCodeValidatorInterface $validator */
$validator = $container->get(VerificationCodeValidatorInterface::class);

$result = $validator->validate(
    IdentityTypeEnum::Email,
    'user@example.com',
    VerificationPurposeEnum::EmailVerification,
    '123456', // The plain code provided by the user
    '192.168.1.101' // Optional IP tracking for usage
);

if ($result->success) {
    // Verification successful
} else {
    // Verification failed: $result->reason
}
```

## Further Documentation

- [How to Use](HOW_TO_USE.md) - Practical integration patterns.
- [Changelog](CHANGELOG.md) - History and evolution of the module.

### Documentation Book

Comprehensive architecture and integration guides are available in the Book:

| Chapter | Description |
|---|---|
| [Table of Contents](docs/book/BOOK.md) | Main entry point for the documentation book. |
| [01. Overview](docs/book/01_overview.md) | High-level module concepts and goals. |
| [02. Architecture](docs/book/02_architecture.md) | Detailed layer boundaries and responsibilities. |
| [03. Domain Model](docs/book/03_domain_model.md) | Entities, DTOs, and Enums representing the verification state. |
| [04. Verification Lifecycle](docs/book/04_verification_lifecycle.md) | The strict lifecycle rules from creation to expiration. |
| [05. Code Generation](docs/book/05_code_generation.md) | How codes are generated, hashed, and policies applied. |
| [06. Code Validation](docs/book/06_code_validation.md) | The validation process, attempt tracking, and anti-brute-force. |
| [07. Repository Layer](docs/book/07_repository_layer.md) | Data persistence strategies and infrastructure. |
| [08. Container Bindings](docs/book/08_container_bindings.md) | Connecting the module to Dependency Injection containers. |
| [09. Extension Points](docs/book/09_extension_points.md) | Extending policies, storage, and identifier types. |
| [10. Integration Patterns](docs/book/10_integration_patterns.md) | Real-world usage inside larger applications. |
| [11. Common Pitfalls](docs/book/11_common_pitfalls.md) | Mistakes to avoid when implementing the module. |
