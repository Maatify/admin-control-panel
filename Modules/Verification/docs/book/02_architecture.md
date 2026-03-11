# Chapter 2: Architecture

The `Maatify\Verification` module is structured around Domain-Driven Design (DDD) principles. This ensures that the core business logic (how codes are generated, hashed, validated, and expired) remains completely decoupled from how those codes are stored or how the module is instantiated in a framework.

## Layer Boundaries

The module is divided into three distinct layers:

### 1. Domain Layer (`Maatify\Verification\Domain`)

This is the core of the module. It contains no external dependencies other than standard PHP structures and the `Maatify\SharedCommon` interfaces (specifically `ClockInterface`).

*   **Contracts:** Defines the public API of the module.
    *   `VerificationCodeGeneratorInterface`: The entry point for creating new codes.
    *   `VerificationCodeValidatorInterface`: The entry point for validating submitted codes.
    *   `VerificationCodeRepositoryInterface`: The abstraction for persistence. The domain does not know *how* codes are saved, only that they *can* be saved.
    *   `VerificationCodePolicyResolverInterface`: Determines the rules (TTL, max attempts) based on the purpose of the code.

*   **DTOs (Data Transfer Objects):** Immutable objects that carry data between layers.
    *   `VerificationCode`: The central state object representing a stored hash, its purpose, and its metadata.
    *   `GeneratedVerificationCode`: A composite object returning both the plain text code (for delivery) and the hashed `VerificationCode` entity (for storage).
    *   `VerificationResult`: A normalized response indicating success or failure.

*   **Enums:** Strongly typed enumerations ensuring that invalid state or purposes cannot be passed to the domain logic.
    *   `IdentityTypeEnum`, `VerificationCodeStatus`, `VerificationPurposeEnum`.

*   **Services:** The concrete implementations of the contracts where the actual business rules live.
    *   `VerificationCodeGenerator`, `VerificationCodeValidator`, `VerificationCodePolicyResolver`.

### 2. Infrastructure Layer (`Maatify\Verification\Infrastructure`)

This layer provides concrete implementations for the abstractions defined in the Domain layer.

*   **Repository:**
    *   `PdoVerificationCodeRepository`: A default implementation of `VerificationCodeRepositoryInterface` using standard PHP Data Objects (PDO). It knows how to translate the `VerificationCode` DTO into an SQL `INSERT`/`UPDATE`/`SELECT` statement.

### 3. Bootstrap Layer (`Maatify\Verification\Bootstrap`)

This layer acts as the glue, connecting the module to the wider application's Dependency Injection container.

*   `VerificationBindings`: A static class providing a `register` method that configures a `DI\ContainerBuilder` with all the necessary interface-to-implementation mappings required for the module to function out of the box.

## Flow of Control

1.  **Application Code** calls a method on `VerificationCodeGeneratorInterface`.
2.  The **Domain Service** (`VerificationCodeGenerator`) resolves policies, applies business rules (e.g., invalidating old codes), and creates a new `VerificationCode` DTO.
3.  The **Domain Service** calls the `VerificationCodeRepositoryInterface` to save the DTO.
4.  The **Infrastructure Implementation** (`PdoVerificationCodeRepository`) receives the DTO and executes the necessary database queries.
5.  Control returns to the **Application Code** with a `GeneratedVerificationCode` containing the plain text code for delivery.