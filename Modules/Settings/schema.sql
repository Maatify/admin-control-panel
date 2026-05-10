-- =============================================================================
-- Schema: settings
-- Package: maatify/settings
-- =============================================================================
--
-- Design principles:
--   - `id`                  is the internal database identity
--   - `setting_key`         is the stable business identifier (UNIQUE)
--   - `setting_value`       stores the current configured value as string
--   - `value_type`          defines how the application should cast/validate value
--   - `is_admin_editable`   controls whether the setting can be changed from admin UI
--   - `admin_note`          is internal documentation for administrators/developers
--   - created_at / updated_at are managed automatically by the DB engine
--
-- Value type examples:
--   bool      => "0" or "1"
--   int       => "15"
--   string    => "ar" or "QAR"
--   date      => "2026-05-11"
--   datetime  => "2026-05-11 12:30:00"
--
-- Notes:
--   - Settings are configuration values, not actions.
--   - Cleanup jobs should read `pre_cart_preparation_ttl_days`
--     and perform deletion in application/CLI code.
-- =============================================================================

CREATE TABLE `settings`
(
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key`       VARCHAR(64)  NOT NULL                    COMMENT 'Stable setting identifier used by application code',
    `setting_value`     VARCHAR(255) NOT NULL DEFAULT ''          COMMENT 'Current setting value stored as string and cast by value_type',
    `value_type`        VARCHAR(16)  NOT NULL                    COMMENT 'bool, int, string, datetime, date',
    `is_admin_editable` TINYINT(1)   NOT NULL DEFAULT 0           COMMENT '1 = editable from admin UI; 0 = system/internal setting',
    `admin_note`        TEXT         DEFAULT NULL                 COMMENT 'Internal note explaining the setting purpose',

    `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_settings_setting_key` (`setting_key`),
    KEY `idx_settings_is_admin_editable` (`is_admin_editable`)

) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci
    COMMENT = 'Application runtime settings — maatify/settings';

-- =============================================================================
-- Seed: base application settings
-- =============================================================================

INSERT INTO `settings`
(`setting_key`, `setting_value`, `value_type`, `is_admin_editable`, `admin_note`)
VALUES
    ('maintenance', '0', 'bool', 1, 'App maintenance mode'),

    ('default_currency', '1', 'int', 1, 'Default currency id'),

    ('default_language', '1', 'int', 1, 'Default language id'),

    ('pre_cart_preparation_ttl_days', '15', 'int', 1, 'Clear pre-cart preparation drafts after X days');
