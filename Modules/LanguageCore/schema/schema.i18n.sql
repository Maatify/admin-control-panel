SET FOREIGN_KEY_CHECKS=0;

/* ===========================
 * DROP TABLES (Leaf → Root)
 * =========================== */
DROP TABLE IF EXISTS language_settings;
DROP TABLE IF EXISTS languages;

SET FOREIGN_KEY_CHECKS=1;

/* ==========================================================
 * LANGUAGE CORE (KERNEL-GRADE BASELINE)
 * ----------------------------------------------------------
 * Purpose:
 * - Provide a system-wide language identity layer
 * - Decouple language identity from translation logic
 * - Support UI, region logic, fallback chains, and multi-language systems
 *
 * Design Principles:
 * - Language identity is stable and minimal
 * - No translation data stored here
 * - No UI enforcement logic in kernel decisions
 * - Safe for standalone extraction as a reusable library
 * ========================================================== */


/* ==========================================================
 * 1) Languages (IDENTITY ONLY)
 * ----------------------------------------------------------
 * Represents a language as a stable identity.
 *
 * Examples:
 * - en
 * - en-US
 * - ar
 * - ar-EG
 *
 * Rules:
 * - Must remain minimal
 * - Must not contain translation data
 * - Must not include presentation-only logic
 * ========================================================== */

CREATE TABLE languages (
                           id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Human-readable display name (UI-level only)
                           name VARCHAR(64) NOT NULL,

    -- Canonical language code (BCP 47 / ISO-compatible)
    -- Examples: en, en-US, ar, ar-EG
                           code VARCHAR(16) NOT NULL,

    -- Activation flag (used by application layer)
                           is_active TINYINT(1) NOT NULL DEFAULT 1,

    -- Optional fallback (regional → base language)
    -- Example: ar-EG → ar
                           fallback_language_id INT UNSIGNED NULL,

                           created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                           updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    -- Enforce one canonical row per language code
                           UNIQUE KEY uq_languages_code (code),

                           CONSTRAINT fk_languages_fallback
                               FOREIGN KEY (fallback_language_id)
                                   REFERENCES languages(id)
                                   ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='System-wide language identity. No translation or UI logic. Kernel-grade stable concept.';


/* ==========================================================
 * 2) Language Settings (UI / PRESENTATION ONLY)
 * ----------------------------------------------------------
 * Optional table for presentation-level concerns:
 * - text direction
 * - display order
 * - icon / flag
 *
 * MUST NOT:
 * - Influence authorization
 * - Influence translation logic
 * - Influence kernel business rules
 * ========================================================== */

CREATE TABLE language_settings (
                                   language_id INT UNSIGNED PRIMARY KEY,

    -- Text direction for UI rendering
                                   direction ENUM('ltr','rtl') NOT NULL DEFAULT 'ltr',

    -- Optional icon / flag path or URL
                                   icon VARCHAR(255) NULL,

    -- UI sort order (lower = earlier)
                                   sort_order INT NOT NULL DEFAULT 0,

                                   CONSTRAINT fk_language_settings_language
                                       FOREIGN KEY (language_id)
                                           REFERENCES languages(id)
                                           ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='UI-only language presentation settings. Not part of kernel logic.';
