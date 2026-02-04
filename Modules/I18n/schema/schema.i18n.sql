SET FOREIGN_KEY_CHECKS=0;
/* ===========================
 * DROP TABLES (Leaf → Root)
 * =========================== */
DROP TABLE IF EXISTS languages;
DROP TABLE IF EXISTS language_settings;
DROP TABLE IF EXISTS i18n_keys;
DROP TABLE IF EXISTS i18n_translations;

SET FOREIGN_KEY_CHECKS=1;

/* ==========================================================
 * I18N / LANGUAGES (CANONICAL BASELINE)
 * ----------------------------------------------------------
 * Purpose:
 * - Provide a clean, kernel-grade internationalization schema
 * - Separate language identity from UI concerns and translations
 * - Support API, Admin UI, Redis caching, and future library extraction
 *
 * Design Principles:
 * - Language identity is stable and minimal
 * - Translations are key-based (NO column-per-language)
 * - No filesystem coupling
 * - No JSON translations
 * - Additive only (no ALTER TABLE per language)
 * ========================================================== */


/* ==========================================================
 * 1) Languages (IDENTITY ONLY)
 * ----------------------------------------------------------
 * Represents a language as a stable identity.
 * MUST NOT contain UI, filesystem, or translation data.
 *
 * Examples:
 * - en
 * - en-US
 * - ar
 * - ar-EG
 * ========================================================== */

CREATE TABLE languages (
                           id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Human-readable display name (UI-level, not used in logic)
                           name VARCHAR(64) NOT NULL,

    -- Canonical language code (BCP 47 / ISO-compatible)
    -- Examples: en, en-US, ar, ar-EG
                           code VARCHAR(16) NOT NULL,

    -- Activation flag (used by application layer)
                           is_active TINYINT(1) NOT NULL DEFAULT 1,

                           created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                           updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    -- Enforce one canonical row per language code
                           UNIQUE KEY uq_languages_code (code)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Language identity table. Stable, minimal, kernel-grade. No UI or translation concerns.';


/* ==========================================================
 * 2) Language Settings (UI / PRESENTATION ONLY)
 * ----------------------------------------------------------
 * Optional table for UI concerns:
 * - text direction
 * - display order
 * - icon / flag
 *
 * MUST NOT be used in authorization, logic, or kernel decisions.
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
    COMMENT='UI-only language settings (direction, icon, ordering). Not part of kernel logic.';


/* ==========================================================
 * 3) Translation Keys (CANONICAL KEYS)
 * ----------------------------------------------------------
 * Defines the universe of translation keys.
 * Keys are language-agnostic and stable.
 *
 * Examples:
 * - auth.login.title
 * - admin.sessions.empty
 * - errors.permission_denied
 * ========================================================== */

CREATE TABLE i18n_keys (
                           id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Canonical translation key (dot-notation recommended)
                           translation_key VARCHAR(191) NOT NULL,

    -- Optional description for admins / developers
                           description VARCHAR(255) NULL,

                           created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Enforce unique canonical keys
                           UNIQUE KEY uq_i18n_keys_key (translation_key)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Canonical translation keys. Language-agnostic. Backbone of the i18n system.';


/* ==========================================================
 * 4) Translations (LANGUAGE + KEY → VALUE)
 * ----------------------------------------------------------
 * Stores the actual translated text.
 *
 * Rules:
 * - One row per (language_id + key_id)
 * - No NULL values
 * - Cascades cleanly on language or key deletion
 * ========================================================== */

CREATE TABLE i18n_translations (
                                   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Reference to canonical key
                                   key_id BIGINT UNSIGNED NOT NULL,

    -- Reference to language
                                   language_id INT UNSIGNED NOT NULL,

    -- Translated value (any length)
                                   value TEXT NOT NULL,

                                   created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                   updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    -- Ensure single translation per language/key
                                   UNIQUE KEY uq_i18n_translation_unique (key_id, language_id),

                                   CONSTRAINT fk_i18n_translation_key
                                       FOREIGN KEY (key_id)
                                           REFERENCES i18n_keys(id)
                                           ON DELETE CASCADE,

                                   CONSTRAINT fk_i18n_translation_language
                                       FOREIGN KEY (language_id)
                                           REFERENCES languages(id)
                                           ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Translated values mapped by (language + key). Additive, cache-friendly, API-ready.';


/* ==========================================================
 * OPTIONAL: Language Fallback (REGIONAL OVERRIDES)
 * ----------------------------------------------------------
 * Allows regional languages to fallback to a base language.
 *
 * Examples:
 * - ar-EG → ar
 * - en-GB → en
 *
 * NOT required for baseline usage.
 * ========================================================== */

ALTER TABLE languages
    ADD COLUMN fallback_language_id INT UNSIGNED NULL,
    ADD CONSTRAINT fk_languages_fallback
        FOREIGN KEY (fallback_language_id)
            REFERENCES languages(id)
            ON DELETE SET NULL;
