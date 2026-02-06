SET FOREIGN_KEY_CHECKS=0;
/* ===========================
 * DROP TABLES (Leaf → Root)
 * =========================== */
DROP TABLE IF EXISTS languages;
DROP TABLE IF EXISTS language_settings;
DROP TABLE IF EXISTS i18n_scopes;
DROP TABLE IF EXISTS i18n_domains;
DROP TABLE IF EXISTS i18n_domain_scopes;
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
 * I18N SCOPES (GOVERNANCE)
 * ----------------------------------------------------------
 * Purpose:
 * - Define supported scopes for translation keys
 * - Used for validation, UI dropdowns, and governance
 * - NOT enforced via FK on i18n_keys
 *
 * Examples:
 * - ct   (Customer / Client)
 * - ad   (Admin)
 * - sys  (System / Emails / Background)
 * - api  (API responses)
 * ========================================================== */

CREATE TABLE i18n_scopes (
                             id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Canonical scope code (used in i18n_keys.scope)
                             code VARCHAR(32) NOT NULL,

    -- Human-readable name (UI only)
                             name VARCHAR(64) NOT NULL,

    -- Optional description for admins / developers
                             description TEXT NULL,

    -- Whether this scope is selectable/active
                             is_active TINYINT(1) NOT NULL DEFAULT 1,

    -- UI ordering
                             sort_order INT NOT NULL DEFAULT 0,

                             created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Enforce unique scope codes
                             UNIQUE KEY uq_i18n_scopes_code (code)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Governance table for i18n scopes. Used for validation and UI only. No FK enforcement.';

/* ==========================================================
 * I18N DOMAINS (GOVERNANCE)
 * ----------------------------------------------------------
 * Purpose:
 * - Define allowed translation domains
 * - Used for grouping, UI, caching boundaries, and validation
 * - NOT enforced via FK on i18n_keys
 *
 * Examples:
 * - home
 * - auth
 * - products
 * - errors
 * - emails
 * ========================================================== */

CREATE TABLE i18n_domains (
                              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Canonical domain code (used in i18n_keys.domain)
                              code VARCHAR(64) NOT NULL,

    -- Human-readable name (UI only)
                              name VARCHAR(128) NOT NULL,

    -- Optional description
                              description TEXT NULL,

    -- Whether this domain is selectable/active
                              is_active TINYINT(1) NOT NULL DEFAULT 1,

    -- UI ordering
                              sort_order INT NOT NULL DEFAULT 0,

                              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Enforce unique domain codes
                              UNIQUE KEY uq_i18n_domains_code (code)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Governance table for i18n domains. Used for validation and UI only. No FK enforcement.';

/* ==========================================================
 * I18N DOMAIN ↔ SCOPE RELATION (POLICY)
 * ----------------------------------------------------------
 * Purpose:
 * - Define which domains are allowed under which scopes
 * - Used strictly for validation and UI guidance
 * - NOT enforced on i18n_keys
 *
 * Example:
 * - ct  → home, auth, products
 * - ad  → dashboard, users, roles
 * - sys → emails, errors
 * ========================================================== */

CREATE TABLE i18n_domain_scopes (
                                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Scope code (matches i18n_scopes.code)
                                    scope_code VARCHAR(32) NOT NULL,

    -- Domain code (matches i18n_domains.code)
                                    domain_code VARCHAR(64) NOT NULL,

    -- Whether this mapping is active
                                    is_active TINYINT(1) NOT NULL DEFAULT 1,

                                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Prevent duplicate mappings
                                    UNIQUE KEY uq_i18n_domain_scopes (scope_code, domain_code)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Policy table linking domains to scopes. Used for validation only. No FK enforcement.';


/* ==========================================================
 * I18N KEYS (STRUCTURED / CANONICAL)
 * ----------------------------------------------------------
 * Library-grade translation key identity.
 * No legacy support. No implicit parsing.
 * ========================================================== */

CREATE TABLE i18n_keys (
                           id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Logical consumer scope (ct, ad, sys, api, ...)
                           scope VARCHAR(32) NOT NULL,

    -- Functional domain (auth, home, products, errors, ...)
                           domain VARCHAR(64) NOT NULL,

    -- Leaf key identifier within domain
    -- Can contain dots (e.g. login.title, form.email.label)
                           key_part VARCHAR(128) NOT NULL,

    -- Optional developer / admin description
                           description VARCHAR(255) NULL,

                           created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Enforce canonical identity
                           UNIQUE KEY uq_i18n_keys_identity (scope, domain, key_part)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
    COMMENT='Canonical structured i18n keys (scope + domain + key_part). Library-grade.';



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
