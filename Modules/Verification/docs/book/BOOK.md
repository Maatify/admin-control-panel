# Verification Module Documentation

Welcome to the comprehensive documentation book for the `Maatify\Verification` module.

This book provides in-depth explanations of the architecture, design decisions, and integration patterns for managing the complete lifecycle of One-Time Passwords (OTPs) and temporary verification codes.

## Table of Contents

* **[Chapter 1: Overview](01_overview.md)**
  High-level concepts, goals, and what the module aims to solve.

* **[Chapter 2: Architecture](02_architecture.md)**
  Detailed explanation of the layer boundaries (Domain, Infrastructure, Bootstrap) and responsibilities.

* **[Chapter 3: Domain Model](03_domain_model.md)**
  In-depth look at the core entities, Data Transfer Objects (DTOs), and strongly-typed Enums representing the verification state.

* **[Chapter 4: Verification Lifecycle](04_verification_lifecycle.md)**
  The strict business rules governing the lifecycle of a verification code from creation to expiration or usage.

* **[Chapter 5: Code Generation](05_code_generation.md)**
  How codes are generated, securely hashed, and how policies (TTL, limits) are applied.

* **[Chapter 6: Code Validation](06_code_validation.md)**
  The validation process, including constant-time comparison, attempt tracking, and anti-brute-force mechanisms.

* **[Chapter 7: Repository Layer](07_repository_layer.md)**
  Data persistence strategies, the repository contract, and the default PDO infrastructure.

* **[Chapter 8: Container Bindings](08_container_bindings.md)**
  How to connect the module to Dependency Injection containers using the provided Bootstrap layer.

* **[Chapter 9: Extension Points](09_extension_points.md)**
  Techniques for extending the module, such as adding custom policies, alternate storage mechanisms, or new identifier types.

* **[Chapter 10: Integration Patterns](10_integration_patterns.md)**
  Real-world usage examples and best practices for integrating the module inside larger applications.

* **[Chapter 11: Common Pitfalls](11_common_pitfalls.md)**
  Mistakes to avoid when implementing the module, such as storing plaintext codes or mishandling failures.
