-- ============================================================
--  Maatify Geo Module — Schema
--  Includes: geo_countries, geo_country_translations,
--            geo_cities,    geo_city_translations
--  Engine : InnoDB | Charset : utf8mb4_unicode_ci
-- ============================================================
--  Run this file after the base kernel schema.
--  The module has NO dependency on the `languages` table at
--  the schema level — translation rows reference language_id
--  as a plain INT (no FK to languages) so the module stays
--  100% standalone.
-- ============================================================


/* ==========================================================
 * GEO MODULE
 * ----------------------------------------------------------
 * Purpose:
 * - Manage countries and their cities with translations
 * - Support display ordering and active/inactive status
 * - COALESCE fallback to base name when translation missing
 *
 * Design Principles:
 * - Fully standalone — no FK to kernel tables
 * - language_id is stored as a plain INT (host maps it)
 * - display_order scoped per entity (global for countries,
 *   per country for cities)
 * ========================================================== */


/* ==========================================================
 * 1) geo_countries
 * ========================================================== */

CREATE TABLE IF NOT EXISTS `geo_countries` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `code`          CHAR(2)         NOT NULL COMMENT 'ISO 3166-1 alpha-2: US, EG, GB …',
    `name`          VARCHAR(100)    NOT NULL COMMENT 'Base name — always English or default locale',
    `currency`      VARCHAR(10)         NULL COMMENT 'ISO 4217 currency code: USD, EUR, EGP …',
    `icon`          VARCHAR(512)        NULL COMMENT 'Flag path or URL — purely presentational',
    `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
    `display_order` INT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME            NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_geo_countries_code`          (`code`),
    INDEX      `idx_geo_countries_is_active`    (`is_active`),
    INDEX      `idx_geo_countries_display_order`(`display_order`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Country master list. ISO alpha-2 code. COALESCE translation fallback.';


/* ==========================================================
 * 2) geo_country_translations
 * ----------------------------------------------------------
 * Localised country name per language.
 *
 * Fallback policy — COALESCE:
 *   When no row exists for (country_id, language_id) the query
 *   automatically falls back to geo_countries.name.
 *
 * Note: language_id is a plain INT — no FK to languages table
 *   so this module stays fully standalone. The admin layer
 *   performs the JOIN to the languages table independently.
 * ========================================================== */

CREATE TABLE IF NOT EXISTS `geo_country_translations` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `country_id`  INT UNSIGNED NOT NULL,
    `language_id` INT UNSIGNED NOT NULL,
    `name`        VARCHAR(100) NOT NULL COMMENT 'Localised country name',
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME         NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_geo_country_trans_pair`       (`country_id`, `language_id`),
    INDEX      `idx_geo_country_trans_lang`      (`language_id`),

    CONSTRAINT `fk_geo_ct_country`
        FOREIGN KEY (`country_id`) REFERENCES `geo_countries` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Localised country names. COALESCE fallback to base name when missing. language_id is a plain INT (no FK).';


/* ==========================================================
 * 3) geo_cities
 * ----------------------------------------------------------
 * Cities belong to a country via country_id.
 * display_order is scoped per country.
 * ========================================================== */

CREATE TABLE IF NOT EXISTS `geo_cities` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `country_id`    INT UNSIGNED    NOT NULL,
    `code`          VARCHAR(20)         NULL COMMENT 'Optional short code or IATA/ICAO code',
    `name`          VARCHAR(100)    NOT NULL COMMENT 'Base name — always English or default locale',
    `time_zone`     VARCHAR(100)        NULL COMMENT 'IANA timezone identifier: Africa/Cairo, America/New_York …',
    `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
    `display_order` INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Scoped per country',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME            NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_geo_cities_country_name`    (`country_id`, `name`),
    INDEX `idx_geo_cities_country_id`      (`country_id`),
    INDEX `idx_geo_cities_is_active`       (`is_active`),
    INDEX `idx_geo_cities_display_order`   (`display_order`),
    INDEX `idx_geo_cities_code`            (`code`),

    CONSTRAINT `fk_geo_cities_country`
        FOREIGN KEY (`country_id`) REFERENCES `geo_countries` (`id`)
            ON DELETE RESTRICT ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='City master list per country. display_order scoped per country. COALESCE translation fallback.';


/* ==========================================================
 * 4) geo_city_translations
 * ----------------------------------------------------------
 * Localised city name per language.
 * Same COALESCE + standalone language_id policy as countries.
 * ========================================================== */

CREATE TABLE IF NOT EXISTS `geo_city_translations` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `city_id`     INT UNSIGNED NOT NULL,
    `language_id` INT UNSIGNED NOT NULL,
    `name`        VARCHAR(100) NOT NULL COMMENT 'Localised city name',
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME         NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_geo_city_trans_pair`     (`city_id`, `language_id`),
    INDEX      `idx_geo_city_trans_lang`    (`language_id`),

    CONSTRAINT `fk_geo_city_trans_city`
        FOREIGN KEY (`city_id`) REFERENCES `geo_cities` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Localised city names. COALESCE fallback to base name when missing. language_id is a plain INT (no FK).';

