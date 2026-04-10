
SET FOREIGN_KEY_CHECKS=0;

/* ===========================
 * DROP TABLES (Leaf → Root)
 * =========================== */

DROP TABLE IF EXISTS app_settings;

SET FOREIGN_KEY_CHECKS=1;


-- ============================================================
-- Table: app_settings
-- ------------------------------------------------------------
-- Purpose:
--   Centralized application configuration store (Key-Value).
--
-- Design Goals:
--   - Avoid schema changes when adding new settings
--   - Support future expansion (apps, social, legal, meta, etc.)
--   - Single source of truth for app-wide configuration
--   - Safe alternative to hard delete using soft disable (is_active)
--
-- Usage Pattern:
--   Each setting is identified by:
--     (setting_group + setting_key) => setting_value
--
-- Example:
--   group: 'social' , key: 'facebook'
--   group: 'apps'   , key: 'android'
--   group: 'legal'  , key: 'privacy_policy'
--
-- This table is intended to replace hardcoded config tables
-- like: app_social, app_meta, app_links, etc.
--
-- IMPORTANT:
--   - This table is NOT user-generated content
--   - Writes should be restricted to admin/system only
--   - Validation, normalization, and protection rules
--     MUST be enforced at the application layer
--   - Physical DELETE is forbidden; use is_active instead
--
-- ============================================================

CREATE TABLE app_settings (
    -- Auto-increment primary key
    -- Used internally only (no business meaning)
                              id INT NOT NULL AUTO_INCREMENT,

    -- Logical grouping of settings
    -- Examples:
    --   social, apps, legal, meta, system, feature_flags
                              setting_group VARCHAR(64) NOT NULL,

    -- Unique key inside the group
    -- Examples:
    --   facebook, instagram, android, ios, privacy_policy
                              setting_key VARCHAR(64) NOT NULL,

    -- Type of the setting value
    -- Used for application-level casting and validation
    -- Allowed: string, int, bool, json
                              setting_type ENUM('string', 'int', 'bool', 'json') NOT NULL DEFAULT 'string',

    -- Actual value of the setting
    -- Stored as TEXT to allow:
    --   - URLs
    --   - Long text
    --   - JSON (if needed in future)
                              setting_value TEXT NOT NULL,

    -- Soft activation flag
    -- 1 = active (visible to consumers)
    -- 0 = inactive (disabled, but preserved)
    --
    -- NOTE:
    --   - Consumers (web/app) must read ACTIVE settings only
    --   - Admin/system may toggle this flag
    --   - Protected keys MUST NOT be deactivated
                              is_active TINYINT(1) NOT NULL DEFAULT 1,

    -- Primary key
                              PRIMARY KEY (id),

    -- Ensure no duplicate keys inside the same group
    -- (group + key) must be unique
                              UNIQUE KEY uniq_setting (setting_group, setting_key)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci
    COMMENT='Centralized application settings (grouped key-value store with soft activation)';
