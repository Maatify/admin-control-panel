SET FOREIGN_KEY_CHECKS=0;

/* ===========================
 * DROP TABLES (Leaf → Root)
 * =========================== */
DROP TABLE IF EXISTS document_acceptance;
DROP TABLE IF EXISTS document_translations;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS document_types;

SET FOREIGN_KEY_CHECKS=1;


/* ==========================================================
 * CONTENT DOCUMENTS MODULE (KERNEL-GRADE BASELINE)
 * ----------------------------------------------------------
 * Purpose:
 * - Manage structured static site documents
 * - Support multi-language content
 * - Support versioning lifecycle
 * - Support publish/unpublish workflow
 * - Support actor-agnostic legal acceptance tracking
 *
 * Examples:
 * - terms
 * - privacy
 * - refunds
 * - about
 *
 * Design Principles:
 * - Document identity is stable
 * - Content is language-scoped
 * - Versioning is explicit
 * - Acceptance tracking is immutable
 * - No UI logic in schema
 * - No dependency on user/customer tables
 * - Safe for standalone extraction
 * ========================================================== */


/* ==========================================================
 * 1) Document Types (LOGICAL IDENTITY LAYER)
 * ----------------------------------------------------------
 * Represents stable logical identities for documents.
 *
 * Examples:
 * - terms
 * - privacy
 * - refunds
 * - about
 *
 * Rules:
 * - Key is stable and unique
 * - No dependency on any actor tables
 * - Optional defaults can be used by application layer
 * ========================================================== */

CREATE TABLE document_types (
                                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Stable logical key (terms, privacy, refunds, about)
                                `key` VARCHAR(64) NOT NULL,

    -- Default behavior hint (application may copy to versions)
                                requires_acceptance_default TINYINT(1) NOT NULL DEFAULT 0,

    -- System-defined vs custom-defined types
                                is_system TINYINT(1) NOT NULL DEFAULT 1,

                                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

                                UNIQUE KEY uq_document_types_key (`key`),

                                INDEX idx_document_types_key (`key`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Stable logical document identities (Terms, Privacy, Refunds, About). Kernel-safe identity root.';


/* ==========================================================
 * 2) Documents (VERSION LAYER)
 * ----------------------------------------------------------
 * Represents a versioned document instance for a given type.
 *
 * Rules:
 * - Multiple versions allowed per type
 * - Only ONE active version per type (enforced at app level)
 * - Version identity is explicit (semantic version)
 * ========================================================== */

CREATE TABLE documents (
                           id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Logical document type reference
                           document_type_id INT UNSIGNED NOT NULL,

    -- Optional redundant copy of type key (debug/readability)
    -- NOTE: source of truth remains document_type_id
                           type_key VARCHAR(64) NOT NULL,

    -- Semantic version (v1, v2, 2026-01, etc.)
                           version VARCHAR(32) NOT NULL,

    -- Whether this version is currently active
                           is_active TINYINT(1) NOT NULL DEFAULT 0,

    -- Whether acceptance is required for this document version
                           requires_acceptance TINYINT(1) NOT NULL DEFAULT 0,

    -- Publish timestamp (NULL = draft)
                           published_at DATETIME NULL,

                           created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                           updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    -- Prevent duplicate version per type
                           UNIQUE KEY uq_documents_type_version (document_type_id, version),

                           INDEX idx_documents_type (document_type_id),
                           INDEX idx_documents_type_active (document_type_id, is_active),
                           INDEX idx_documents_type_key (type_key),

                           CONSTRAINT fk_documents_document_type
                               FOREIGN KEY (document_type_id)
                                   REFERENCES document_types(id)
                                   ON DELETE RESTRICT

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Versioned structured documents per type. Kernel-safe version layer with explicit lifecycle.';


/* ==========================================================
 * 3) Document Translations (LANGUAGE CONTENT LAYER)
 * ----------------------------------------------------------
 * Holds localized content for each document version.
 *
 * Rules:
 * - One translation per language per document version
 * - No version logic here
 * - No UI logic here
 * - Depends only on documents + languages
 * ========================================================== */

CREATE TABLE document_translations (
                                       id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                                       document_id INT UNSIGNED NOT NULL,
                                       language_id INT UNSIGNED NOT NULL,

    -- Localized title
                                       title VARCHAR(255) NOT NULL,

    -- Optional SEO fields
                                       meta_title VARCHAR(255) NULL,
                                       meta_description TEXT NULL,

    -- Main HTML content
                                       content LONGTEXT NOT NULL,

                                       created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

                                       UNIQUE KEY uq_document_language (document_id, language_id),

                                       INDEX idx_document_translations_document (document_id),
                                       INDEX idx_document_translations_language (language_id),

                                       CONSTRAINT fk_document_translations_document
                                           FOREIGN KEY (document_id)
                                               REFERENCES documents(id)
                                               ON DELETE CASCADE,

                                       CONSTRAINT fk_document_translations_language
                                           FOREIGN KEY (language_id)
                                               REFERENCES languages(id)
                                               ON DELETE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Localized content per document version. Multi-language layer.';


/* ==========================================================
 * 4) Document Acceptance (LEGAL AUDIT LAYER)
 * ----------------------------------------------------------
 * Tracks which actor accepted which document version.
 *
 * Actor-Agnostic:
 * - Supports user / customer / distributor / admin
 * - No foreign key to actor tables (decoupled design)
 *
 * Legal Rules:
 * - Immutable log (insert-only)
 * - Version stored redundantly for integrity
 * ========================================================== */

CREATE TABLE document_acceptance (
                                     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Generic actor identity
                                     actor_type VARCHAR(64) NOT NULL,
                                     actor_id BIGINT UNSIGNED NOT NULL,

                                     document_id INT UNSIGNED NOT NULL,

    -- Capture version redundantly (legal integrity)
                                     version VARCHAR(32) NOT NULL,

                                     accepted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Legal traceability
                                     ip_address VARCHAR(45) NULL,
                                     user_agent VARCHAR(255) NULL,

    -- Prevent accepting same version twice
                                     UNIQUE KEY uq_actor_document_version (
                                                                           actor_type,
                                                                           actor_id,
                                                                           document_id,
                                                                           version
                                         ),

                                     INDEX idx_actor_lookup (actor_type, actor_id),
                                     INDEX idx_document_lookup (document_id),

                                     CONSTRAINT fk_document_acceptance_document
                                         FOREIGN KEY (document_id)
                                             REFERENCES documents(id)
                                             ON DELETE CASCADE

    -- NOTE:
    -- No FK to actor tables intentionally
    -- Module must remain domain-agnostic

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Immutable legal acceptance audit log for any actor type. Kernel-grade decoupled design.';
