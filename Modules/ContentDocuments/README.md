# Maatify/ContentDocuments

**Kernel-Grade Content Document Management**

This library provides a robust system for managing versioned, multi-language static documents (e.g., Terms of Service, Privacy Policy) and tracking their acceptance by various actors (users, customers, admins) in a decoupled, audit-friendly manner.

---

## Documentation Contract

This README serves as a high-level identity summary and architectural contract.

**All authoritative usage rules, lifecycle definitions, and runtime behaviors are defined in:**
👉 [**Maatify/ContentDocuments/BOOK/**](./BOOK.md) (The Usage Book)
👉 [**Maatify/ContentDocuments/HOW_TO_USE.md**](./HOW_TO_USE.md) (Integration Guide)

**Contract Rules:**
1.  **The Book is Authoritative:** If this README and the Book diverge, the Book is the source of truth.
2.  **Kernel-Grade Decoupling:** This module is designed to be actor-agnostic. It tracks acceptance via `ActorIdentity` (Type + ID) and does not contain foreign keys to user tables.

---

## 1. Core Concepts

### Logical Document Types
Documents are grouped by stable logical keys (e.g., `terms`, `privacy`, `refund_policy`). The system enforces that only one version of a type can be **Active** at any given time.

### Explicit Versioning
Every document is a versioned instance (e.g., `v1.0`, `2023-01-01`). Content changes require a new version.

### Actor-Agnostic Acceptance
The system tracks *who* accepted *what version* using a generic `actor_type` (string) and `actor_id` (int) schema. This allows a single document system to serve Users, Admins, Partners, etc., without schema coupling.

---

## 2. Architecture

The module adheres to a strict layered architecture:

*   **Domain (`Domain/`):** Entities, Value Objects, and Contracts.
*   **Application (`Application/`):** High-level services (Lifecycle, Enforcement, Acceptance).
*   **Infrastructure (`Infrastructure/`):** Persistence and implementation details.

---

## 3. Database Schema

The system relies on 4 core tables:

1.  **`document_types`**: logical definitions.
2.  **`documents`**: versioned instances.
3.  **`document_translations`**: localized content.
4.  **`document_acceptance`**: immutable audit log.

---

## 4. Integration

### Quick Start
Refer to **[HOW_TO_USE.md](HOW_TO_USE.md)** for:
*   Wiring Services & Repositories
*   Creating & Publishing Versions
*   Enforcing Acceptance
