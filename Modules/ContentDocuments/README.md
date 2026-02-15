# Content Documents Module

This module provides a robust, kernel-grade solution for managing versioned, multi-language static documents such as "Terms of Service", "Privacy Policy", or "About Us" pages.

It is designed to be **actor-agnostic**, meaning it does not depend on specific user or customer tables, making it portable and decoupled from the authentication/authorization layer.

## Key Features

- **Document Types**: Define logical document identities (e.g., `terms`, `privacy`) with stable keys.
- **Strict Versioning**: Supports semantic versioning (e.g., `v1.0`, `2023-01`) with a clear lifecycle.
- **Lifecycle Management**:
  - **Draft**: Editable content.
  - **Published**: Immutable, ready for activation.
  - **Active**: The single currently enforceable version for a type.
  - **Archived**: Historic versions, strictly immutable.
- **Multi-Language Support**: One version can have translations for multiple languages.
- **Legal Acceptance Tracking**: Immutable audit log of who accepted which version and when.
- **Immutability**: Enforced at the service level; once a document is published, its content and translations cannot be modified.

## Documentation

For a deep dive into the architecture, database model, and usage patterns, please refer to the **Module Book**:

👉 [**Read the Module Book**](BOOK/book.md)

## Entry Point

The primary entry point for this module is the **Facade**:

`Maatify\ContentDocuments\Application\Service\ContentDocumentsFacade`

It exposes all necessary capabilities for querying documents, managing the lifecycle, and recording acceptance.
