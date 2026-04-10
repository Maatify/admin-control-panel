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

    -- 🔥 NEW: unique enforcement marker (MySQL-safe partial unique)
    -- If is_active=1 => active_guard=1, else NULL.
    -- UNIQUE allows unlimited NULLs, so multiple inactive rows are allowed.
                           active_guard TINYINT(1)
                               GENERATED ALWAYS AS (IF(is_active = 1, 1, NULL)) STORED,

    -- Whether acceptance is required for this document version
                           requires_acceptance TINYINT(1) NOT NULL DEFAULT 0,

    -- Publish timestamp (NULL = draft)
                           published_at DATETIME NULL,

    -- 🧊 NEW: archive marker (NULL = visible/active candidate, NOT NULL = archived/hidden)
                           archived_at DATETIME NULL,

                           created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                           updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    -- Prevent duplicate version per type
                           UNIQUE KEY uq_documents_type_version (document_type_id, version),

    -- ✅ Correct: one active doc per type (enforced only when active)
                           UNIQUE KEY uq_documents_one_active_per_type (document_type_id, active_guard),

                           INDEX idx_documents_type (document_type_id),

    -- Keep as non-unique for filtering/sorting (optional but useful)
                           INDEX idx_documents_type_active (document_type_id, is_active),

                           INDEX idx_documents_type_key (type_key),

    -- 🧊 Archive filters
                           INDEX idx_documents_archived_at (archived_at),
                           INDEX idx_documents_type_archived (document_type_id, archived_at),

    /* 🔥 NEW: Enforcement fast scan */
                           INDEX idx_documents_enforcement (
                                                            is_active,
                                                            requires_acceptance,
                                                            published_at
                               ),

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

    /* 🔥 NEW: Fast accepted document lookup */
                                     INDEX idx_acceptance_actor_doc (
                                                                     actor_type,
                                                                     actor_id,
                                                                     document_id
                                         ),

                                     CONSTRAINT fk_document_acceptance_document
                                         FOREIGN KEY (document_id)
                                             REFERENCES documents(id)
                                             ON DELETE RESTRICT

    -- NOTE:
    -- No FK to actor tables intentionally
    -- Module must remain domain-agnostic

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Immutable legal acceptance audit log for any actor type. Kernel-grade decoupled design.';


INSERT IGNORE INTO `documents` (`id`, `document_type_id`, `type_key`, `version`, `is_active`, `requires_acceptance`, `published_at`) VALUES
                                                                                                                                  (1, 1, 'terms', 'v1', 1, 1, CURRENT_TIMESTAMP),
                                                                                                                                  (2, 2, 'privacy', 'v1', 1, 1, CURRENT_TIMESTAMP),
                                                                                                                                  (3, 3, 'about', 'v1', 1, 0, CURRENT_TIMESTAMP),
                                                                                                                                  (4, 4, 'cookie', 'v1', 1, 0, CURRENT_TIMESTAMP),
                                                                                                                                  (5, 5, 'refund', 'v1', 1, 0, CURRENT_TIMESTAMP),
                                                                                                                                  (6, 6, 'cancellation', 'v1', 1, 0, CURRENT_TIMESTAMP);
--
-- Dumping data for table `document_types`
--

INSERT IGNORE INTO `document_types` (`id`, `key`, `requires_acceptance_default`, `is_system`, `created_at`, `updated_at`) VALUES
                                                                                                                       (1, 'terms', 1, 1, '2026-03-28 11:24:21', '2026-03-28 11:35:02'),
                                                                                                                       (2, 'privacy', 1, 1, '2026-03-28 11:27:13', '2026-03-28 11:34:58'),
                                                                                                                       (3, 'about-us', 0, 1, '2026-03-28 11:29:04', NULL),
                                                                                                                       (4, 'cookie-policy', 0, 1, '2026-03-28 11:32:55', NULL),
                                                                                                                       (5, 'refund-policy', 1, 1, '2026-03-28 11:35:36', NULL),
                                                                                                                       (6, 'cancellation-policy', 1, 1, '2026-03-28 11:37:47', NULL);

--
-- Dumping data for table `document_translations`
--

INSERT IGNORE INTO `document_translations` (`id`, `document_id`, `language_id`, `title`, `meta_title`, `meta_description`, `content`, `created_at`,
                                      `updated_at`) VALUES
                                                                                                                                                               (1, 1, 2, 'شروط الخدمة', 'شروط الخدمة', 'شروط الخدمة', '<p>اكتب شروط الخدمة من لوحة التحكم</p>', '2026-03-28 11:26:09', NULL),
                                                                                                                                                               (2, 1, 1, 'Terms of Service', 'Terms of Service', 'Terms of Service', '<p>Insert Terms of Service for admin panel</p>', '2026-03-28 11:26:53', NULL),
                                                                                                                                                               (3, 2, 2, 'شروط الخصوصية', 'شروط الخصوصية', 'شروط الخصوصية', '<p>ادخل&nbsp;شروط الخصوصية من لوحة التحكم</p>', '2026-03-28 11:28:01', NULL),
                                                                                                                                                               (4, 2, 1, 'Privacy Policy', 'Privacy Policy', 'Privacy Policy', '<p>insert&nbsp;Privacy Policy from admin panel</p>', '2026-03-28 11:28:41', NULL),
                                                                                                                                                               (5, 3, 2, 'عنا', 'عنا', 'عنا', '<p>ادخل معلومات عنا من لوحة التحكم</p>', '2026-03-28 11:29:56', NULL),
                                                                                                                                                               (6, 3, 1, 'About us', 'About us', 'About us', '<p>insert&nbsp;About us from admin panel</p>', '2026-03-28 11:30:20', NULL),
                                                                                                                                                               (7, 4, 1, 'Cookie Policy', 'Cookie Policy', 'Cookie Policy', '<p>insert&nbsp;Cookie Policy from admin panel</p>', '2026-03-28 11:33:31', NULL),
                                                                                                                                                               (8, 4, 2, 'سياسة ملفات تعريف الارتباط', 'سياسة ملفات تعريف الارتباط', 'سياسة ملفات تعريف الارتباط', '<p>ادخل سياسة ملفات تعريف الارتباط من لوحة التحكم</p>', '2026-03-28 11:34:29', NULL),
                                                                                                                                                               (9, 5, 1, 'Refund Policy', 'Refund Policy', 'Refund Policy', '<p>Insert&nbsp;Refund Policy from admin panel</p>', '2026-03-28 11:36:16', NULL),
                                                                                                                                                               (10, 5, 2, 'سياسة الاسترداد', 'سياسة الاسترداد', 'سياسة الاسترداد', '<p>ادخل <span class=\"HwtZe\" jsname=\"jqKxS\" jsaction=\"mouseup:Sxi9L,BR6jm; mousedown:qjlr0e\" lang=\"ar\"><span jsname=\"txFAF\" class=\"jCAhz ChMk0b\" jsaction=\"agoMJf:PFBcW;MZfLnc:P7O7bd;nt4Alf:pvnm0e,pfE8Hb,PFBcW;B01qod:dJXsye;H1e5u:iXtTIf;lYIUJf:hij5Wb;tSpjdb:qAKMYb\" jscontroller=\"BiTO4b\"><span class=\"ryNqvb\" jsname=\"W297wb\" jsaction=\"click:PDNqTc,GFf3ac,qlVvte;contextmenu:Nqw7Te,QP7LD; mouseout:Nqw7Te; mouseover:PDNqTc,c2aHje\">سياسة الاسترداد</span></span></span> من لوحة التحكم</p>', '2026-03-28 11:36:40', NULL),
                                                                                                                                                               (11, 6, 1, 'Cancellation Policy', 'Cancellation Policy', 'Cancellation Policy', '<p>insert&nbsp;Cancellation Policy from admin panel</p>', '2026-03-28 11:38:40', NULL),
                                                                                                                                                               (12, 6, 2, 'سياسة الإلغاء', 'سياسة الإلغاء', 'سياسة الإلغاء', '<p>ادخل <span class=\"HwtZe\" jsname=\"jqKxS\" jsaction=\"mouseup:Sxi9L,BR6jm; mousedown:qjlr0e\" lang=\"ar\"><span jsname=\"txFAF\" class=\"jCAhz\" jsaction=\"agoMJf:PFBcW;MZfLnc:P7O7bd;nt4Alf:pvnm0e,pfE8Hb,PFBcW;B01qod:dJXsye;H1e5u:iXtTIf;lYIUJf:hij5Wb;tSpjdb:qAKMYb\" jscontroller=\"BiTO4b\"><span class=\"ryNqvb\" jsname=\"W297wb\" jsaction=\"click:PDNqTc,GFf3ac,qlVvte;contextmenu:Nqw7Te,QP7LD; mouseout:Nqw7Te; mouseover:PDNqTc,c2aHje\">سياسة الإلغاء من لوحة التحكم</span></span></span><div class=\"OvtS8d\" jsname=\"yiB9Md\" jscontroller=\"msAMEf\"></div></p>', '2026-03-28 11:39:26', NULL);
