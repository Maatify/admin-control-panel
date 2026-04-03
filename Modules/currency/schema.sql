-- ============================================================
--  Maatify Currency Module — Schema
--  Engine : InnoDB | Charset : utf8mb4_unicode_ci
-- ============================================================
--  Dependencies (must exist before running this file):
--    • languages (id INT UNSIGNED PK)
-- ============================================================

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