# 01 - Architecture

The Content Documents module follows a Domain-Driven Design (DDD) layered architecture, ensuring clear separation of concerns, testability, and portability.

## 1. Layering

### Domain Layer (`Modules/ContentDocuments/Domain`)
- **Entities**: Pure PHP objects representing the core business concepts (`Document`, `DocumentTranslation`, `DocumentAcceptance`).
- **Value Objects**: Immutable objects encapsulating domain primitives (`DocumentTypeKey`, `DocumentVersion`, `ActorIdentity`).
- **Contracts**: Interface definitions for Repositories and Services, decoupling the domain from implementation details.
- **Exceptions**: Domain-specific exceptions (`DocumentVersionImmutableException`, `DocumentNotFoundException`).

### Application Layer (`Modules/ContentDocuments/Application`)
- **Services**: Orchestrate domain logic and business rules (`DocumentLifecycleService`, `DocumentTranslationService`, `DocumentAcceptanceService`).
- **DTOs**: Data Transfer Objects used to pass data in and out of the module boundaries (`DocumentDTO`, `DocumentViewDTO`).
- **Facade**: The primary entry point for consumers (`ContentDocumentsFacade`), simplifying interaction with the internal services.

### Infrastructure Layer (`Modules/ContentDocuments/Infrastructure`)
- **Persistence**: Concrete implementations of Repository interfaces using PDO (`PdoDocumentRepository`, `PdoDocumentTranslationRepository`).
- **Transaction Management**: Handling database transactions (`PdoTransactionManager`).

## 2. Contract Boundaries

The module communicates with the rest of the system primarily through:
- **Facade Interface**: `ContentDocumentsFacadeInterface`
- **DTOs**: Ensure type safety and structure stability.
- **Exceptions**: Specific exceptions are thrown for domain errors, allowing consumers to handle them gracefully.

## 3. Dependency Directions

- **Domain** depends on **nothing** external (pure PHP).
- **Application** depends on **Domain** and **Shared Common Contracts** (e.g., `ClockInterface`).
- **Infrastructure** depends on **Domain** and **Application** (interfaces).
- **External Consumers** (Controllers, other modules) depend on **Application (Facade/DTOs)**.

## 4. Extraction Safety

This module is designed to be **kernel-grade**:
- **Actor-Agnostic**: It does not depend on `users` or `customers` tables. It uses `ActorIdentity` (type + ID) to refer to any entity.
- **Decoupled Auth**: It does not depend on the authentication system. Authorization checks are the responsibility of the consumer.
- **Self-Contained Schema**: All required tables are defined within the module's schema.
