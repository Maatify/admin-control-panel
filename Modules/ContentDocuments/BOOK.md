# Maatify/ContentDocuments: The Book

This documentation serves as the authoritative source for the **ContentDocuments** module. It details the architectural decisions, database schemas, and runtime behaviors.

---

## Table of Contents

### 1. [Introduction](./BOOK/01_introduction.md)
   - Scope and Purpose
   - Kernel-Grade Philosophy
   - Use Cases (Terms, Privacy)

### 2. [Architecture](./BOOK/02_architecture.md)
   - Domain-Driven Design Layers
   - Actor Agnostic Identity
   - Dependency Isolation

### 3. [Database Schema](./BOOK/03_database_schema.md)
   - Entity Relationships
   - Types vs. Documents
   - Translations & Audit Logs

### 4. [Lifecycle Management](./BOOK/04_lifecycle_management.md)
   - Versioning Strategy
   - Publish vs. Activate
   - The "One Active Version" Rule

### 5. [Acceptance & Enforcement](./BOOK/05_acceptance_and_enforcement.md)
   - Determining Required Acceptance
   - Audit Trail Integrity
   - Performance Considerations
