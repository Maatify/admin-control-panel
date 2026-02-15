# Database Schema

The system is built on 4 core tables designed for referential integrity and performance.

## 1. `document_types`
Represents the stable, logical identity of a document.
*   `id`: Internal PK.
*   `key`: The string key used in code (e.g., `terms`, `privacy`). **Unique**.
*   `is_system`: Flag indicating if this type is built-in.

## 2. `documents`
Represents a specific version of a Document Type.
*   `document_type_id`: FK to `document_types`.
*   `version`: Semantic version string (e.g., `v1.0`).
*   `is_active`: Boolean. Only one row per `document_type_id` should be true (enforced by Service).
*   `requires_acceptance`: Boolean. If true, users are forced to accept this version.
*   `published_at`: Timestamp. If NULL, the document is a **Draft**.

## 3. `document_translations`
Holds the actual text content.
*   `document_id`: FK to `documents`.
*   `language_id`: FK to `languages` (from `LanguageCore`).
*   `title`, `content`: The payload.

## 4. `document_acceptance`
The immutable audit log.
*   `actor_type`, `actor_id`: The decoupled identity of the accepter.
*   `document_id`: The specific version accepted.
*   `version`: Redundant copy of the version string for easier auditing.
*   `accepted_at`: Timestamp.
*   `ip_address`, `user_agent`: Audit metadata.

**Note:** There is a unique constraint on `(actor_type, actor_id, document_id, version)` to prevent duplicate acceptance records.
