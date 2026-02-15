# 03 - Database Model

This chapter documents the database schema for the `ContentDocuments` module. It includes table definitions and critical constraints.

## Tables

### 1. `document_types`
Stores the logical document definitions.

- `id`: INT (PK)
- `key`: VARCHAR(64) - Unique key (e.g., `terms`, `privacy`).
- `requires_acceptance_default`: TINYINT(1) - Default setting for new versions.
- `is_system`: TINYINT(1) - System vs user-defined.
- `created_at`, `updated_at`: DATETIME

### 2. `documents`
Stores versioned instances of a document type.

- `id`: INT (PK)
- `document_type_id`: INT (FK to `document_types.id`)
- `type_key`: VARCHAR(64) - Redundant copy for readability/debugging.
- `version`: VARCHAR(32) - Semantic version string.
- `is_active`: TINYINT(1) - Boolean flag.
- `active_guard`: TINYINT(1) GENERATED ALWAYS AS (IF(is_active = 1, 1, NULL)) STORED - **Critical for uniqueness**.
- `requires_acceptance`: TINYINT(1)
- `published_at`: DATETIME (NULL = Draft)
- `archived_at`: DATETIME (NULL = Active/Visible)
- `created_at`, `updated_at`: DATETIME

**Unique Constraints:**
- `uq_documents_type_version`: (`document_type_id`, `version`) - Prevents duplicate versions per type.
- `uq_documents_one_active_per_type`: (`document_type_id`, `active_guard`) - Ensures **only one active document** exists per type at any given time (relies on NULL handling in unique index).

### 3. `document_translations`
Stores localized content.

- `id`: INT (PK)
- `document_id`: INT (FK to `documents.id` CASCADE DELETE)
- `language_id`: INT (FK to `languages.id` CASCADE DELETE)
- `title`: VARCHAR(255)
- `meta_title`: VARCHAR(255)
- `meta_description`: TEXT
- `content`: LONGTEXT
- `created_at`, `updated_at`: DATETIME

**Unique Constraints:**
- `uq_document_language`: (`document_id`, `language_id`) - One translation per language per version.

### 4. `document_acceptance`
Stores immutable audit logs of acceptance.

- `id`: BIGINT (PK)
- `actor_type`: VARCHAR(64)
- `actor_id`: BIGINT UNSIGNED
- `document_id`: INT (FK to `documents.id`)
- `version`: VARCHAR(32) - Redundant copy for audit integrity.
- `accepted_at`: DATETIME
- `ip_address`: VARCHAR(45)
- `user_agent`: VARCHAR(255)

**Unique Constraints:**
- `uq_actor_document_version`: (`actor_type`, `actor_id`, `document_id`, `version`) - Prevents duplicate acceptance records for the same version.

**Indexes:**
- `idx_acceptance_actor_doc`: (`actor_type`, `actor_id`, `document_id`) - Fast lookup for enforcement checks.
