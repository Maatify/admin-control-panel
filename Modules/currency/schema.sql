-- ============================================================
--  Maatify Currency Module — Schema
--  Engine : InnoDB | Charset : utf8mb4_unicode_ci
-- ============================================================
--  Run this file in order — the language tables must be
--  created before the currencies tables.
-- ============================================================


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
 * Examples: en, en-US, ar, ar-EG
 *
 * Rules:
 * - Must remain minimal
 * - Must not contain translation data
 * - Must not include presentation-only logic
 * ========================================================== */

CREATE TABLE IF NOT EXISTS `languages` (
                                           `id`                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                                           `name`                 VARCHAR(64)   NOT NULL COMMENT 'Human-readable display name (UI-level only)',
                                           `code`                 VARCHAR(16)   NOT NULL COMMENT 'Canonical BCP 47 code: en, en-US, ar, ar-EG',
                                           `is_active`            TINYINT(1)    NOT NULL DEFAULT 1,
                                           `fallback_language_id` INT UNSIGNED      NULL COMMENT 'Optional: regional → base, e.g. ar-EG → ar',
                                           `created_at`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                           `updated_at`           DATETIME          NULL ON UPDATE CURRENT_TIMESTAMP,

                                           PRIMARY KEY (`id`),
                                           UNIQUE KEY `uq_languages_code` (`code`),
                                           INDEX      `idx_languages_is_active` (`is_active`),

                                           CONSTRAINT `fk_languages_fallback`
                                               FOREIGN KEY (`fallback_language_id`)
                                                   REFERENCES `languages` (`id`)
                                                   ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
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

CREATE TABLE IF NOT EXISTS `language_settings` (
                                                   `language_id` INT UNSIGNED                 NOT NULL,
                                                   `direction`   ENUM('ltr','rtl')            NOT NULL DEFAULT 'ltr',
                                                   `icon`        VARCHAR(255)                     NULL COMMENT 'Optional flag path or URL',
                                                   `sort_order`  INT                          NOT NULL DEFAULT 0,

                                                   PRIMARY KEY (`language_id`),
                                                   INDEX `idx_language_settings_sort` (`sort_order`),

                                                   CONSTRAINT `fk_language_settings_language`
                                                       FOREIGN KEY (`language_id`)
                                                           REFERENCES `languages` (`id`)
                                                           ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='UI-only language presentation settings. Not part of kernel logic.';


/* ==========================================================
 * CURRENCY MODULE
 * ========================================================== */

CREATE TABLE IF NOT EXISTS `currencies` (
                                            `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
                                            `code`          CHAR(3)         NOT NULL COMMENT 'ISO 4217: USD, QAR, EUR …',
                                            `name`          VARCHAR(50)     NOT NULL COMMENT 'Base name — always English or default locale',
                                            `symbol`        VARCHAR(10)     NOT NULL COMMENT 'ISO glyph — universal, never translated',
                                            `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
                                            `display_order` INT UNSIGNED    NOT NULL DEFAULT 0,
                                            `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                            `updated_at`    DATETIME            NULL ON UPDATE CURRENT_TIMESTAMP,

                                            PRIMARY KEY (`id`),
                                            UNIQUE KEY `uq_currencies_code`         (`code`),
                                            INDEX  `idx_currencies_is_active`       (`is_active`),
                                            INDEX  `idx_currencies_display_order`   (`display_order`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* ==========================================================
 * currency_translations
 * ----------------------------------------------------------
 * Stores the localised name of a currency per language.
 *
 * What IS translated   : name  (e.g. "US Dollar" → "دولار أمريكي")
 * What is NOT translated: code (ISO 4217) · symbol ($ € …)
 *
 * Fallback policy — COALESCE:
 *   When no row exists for (currency_id, language_id) the query
 *   automatically falls back to currencies.name.
 *   The caller always receives a non-null string — no null-checks needed.
 *
 * Listing policy — LEFT JOIN languages:
 *   listTranslationsForCurrency() joins the languages table so every
 *   active language is shown, including those without a translation row.
 *   translatedName is null for untranslated languages.
 * ========================================================== */

CREATE TABLE IF NOT EXISTS `currency_translations` (
                                                       `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                                       `currency_id` INT UNSIGNED NOT NULL,
                                                       `language_id` INT UNSIGNED NOT NULL,
                                                       `name`        VARCHAR(50)  NOT NULL COMMENT 'Localised currency name',
                                                       `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                       `updated_at`  DATETIME         NULL ON UPDATE CURRENT_TIMESTAMP,

                                                       PRIMARY KEY (`id`),
                                                       UNIQUE KEY `uq_currency_translations_pair`     (`currency_id`, `language_id`),
                                                       INDEX       `idx_currency_translations_lang`   (`language_id`),

                                                       CONSTRAINT `fk_ct_currency`
                                                           FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`)
                                                               ON DELETE CASCADE ON UPDATE CASCADE,

                                                       CONSTRAINT `fk_ct_language`
                                                           FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`)
                                                               ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Localised currency names — COALESCE fallback to base name when missing.';