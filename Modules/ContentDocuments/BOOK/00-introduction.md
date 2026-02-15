# 00 - Introduction

This module provides a kernel-grade solution for managing versioned, multi-language static documents. It is designed to be highly reliable, immutable where necessary, and decoupled from the application's user/actor model.

## Scope

The primary goals of this module are:
1.  **Version Control**: Manage multiple versions (Draft, Published, Active, Archived) of a document type.
2.  **Multi-Language Content**: Store translations for each version.
3.  **Legal Acceptance**: Track who accepted which version, without knowing *who* the actor is (agnostic design).
4.  **Immutability**: Ensure that once a document is published, its content cannot be changed, preserving the integrity of what was accepted.

## Non-Goals

- **UI Components**: This module provides the backend logic and data structures. It does not provide frontend UI components.
- **User Management**: It does not manage users or authentication. It accepts an `ActorIdentity` value object to represent any actor.

## Terminology

| Term | Definition |
| :--- | :--- |
| **Document Type** | A logical category of document, identified by a unique key (e.g., `terms`, `privacy`, `refunds`). |
| **Version** | A specific iteration of a document type (e.g., `v1.0`, `2023-01-01`). Versions are unique per type. |
| **Active Document** | The single version of a Document Type that is currently enforceable and presented to users. |
| **Published** | A state indicating the version is finalized and ready for activation. Content is immutable. |
| **Archived** | A state indicating the version is no longer in use. Content remains immutable. |
| **Translation** | Localized content (title, body, SEO metadata) for a specific document version and language. |
| **Acceptance** | An immutable record stating that a specific `ActorIdentity` has agreed to a specific `DocumentVersion`. |
| **ActorIdentity** | A Value Object representing an entity (User, Admin, System) via a type string and numeric ID (e.g., `user:123`). |
