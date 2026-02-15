# Architecture

The module follows a strict **Domain-Driven Design (DDD)** approach, separated into clear layers.

## 1. Domain Layer (`Domain/`)

This is the core of the business logic, containing:
*   **Entities:** `Document`, `DocumentTranslation`, `DocumentAcceptance`. Represents the state.
*   **Value Objects:** `ActorIdentity`, `DocumentTypeKey`, `DocumentVersion`. Represents immutable typed values.
*   **Contracts:** Interfaces for Repositories and Services.
*   **Exceptions:** Typed exceptions for specific failure modes (e.g., `DocumentNotFoundException`).

### The `ActorIdentity` Pattern
To ensure the module remains reusable across different projects (and different types of users within a project), it uses the `ActorIdentity` value object.

```php
// Decoupled from "User" or "Admin" classes
$actor = new ActorIdentity(actorType: 'customer', actorId: 55);
```

This allows the `document_acceptance` table to store records for any entity without foreign key constraints to external tables.

## 2. Application Layer (`Application/`)

This layer orchestrates the domain objects to perform business tasks.
*   **`DocumentLifecycleService`**: Manages the write-side (Creating, Publishing, Activating).
*   **`DocumentEnforcementService`**: Manages the read-side logic for "Does this user need to accept anything?".
*   **`DocumentAcceptanceService`**: Handles the write-side of recording an acceptance.

## 3. Infrastructure Layer (`Infrastructure/`)

Contains the concrete implementations of the Domain Contracts.
*   **Persistence:** MySQL/PDO implementations of the repositories.
